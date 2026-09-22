<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AutomationSettingsController extends Controller
{
    private const TRIGGERS = [
        Automation::TRIGGER_DATE_REACHED,
        Automation::TRIGGER_TASK_CREATED,
        Automation::TRIGGER_TASK_COMPLETED,
        Automation::TRIGGER_TASK_OVERDUE,
        Automation::TRIGGER_COMPLIANCE_EXPIRING,
    ];

    private const ACTIONS = [
        Automation::ACTION_CREATE_TASK,
        Automation::ACTION_CREATE_FUTURE_TASK,
        Automation::ACTION_SEND_NOTIFICATION,
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'A company is required to manage automations.');
        }

        $automations = Automation::query()
            ->forCompany($company->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Automation $automation) => [
                'id' => $automation->id,
                'name' => $automation->name,
                'trigger_type' => $automation->trigger_type,
                'trigger_config' => $automation->trigger_config,
                'action_type' => $automation->action_type,
                'action_config' => $automation->action_config,
                'enabled' => $automation->enabled,
                'last_run_at' => optional($automation->last_run_at)?->toIso8601String(),
            ]);

        return Inertia::render('settings/Automations', [
            'automations' => $automations,
            'triggerOptions' => [
                ['value' => Automation::TRIGGER_DATE_REACHED, 'label' => 'Date reached'],
                ['value' => Automation::TRIGGER_TASK_CREATED, 'label' => 'Task created'],
                ['value' => Automation::TRIGGER_TASK_COMPLETED, 'label' => 'Task completed'],
                ['value' => Automation::TRIGGER_TASK_OVERDUE, 'label' => 'Task overdue'],
                ['value' => Automation::TRIGGER_COMPLIANCE_EXPIRING, 'label' => 'Compliance expiring'],
            ],
            'actionOptions' => [
                ['value' => Automation::ACTION_CREATE_TASK, 'label' => 'Create task'],
                ['value' => Automation::ACTION_CREATE_FUTURE_TASK, 'label' => 'Create future task'],
                ['value' => Automation::ACTION_SEND_NOTIFICATION, 'label' => 'Send notification'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => ['required', Rule::in(self::TRIGGERS)],
            'action_type' => ['required', Rule::in(self::ACTIONS)],
            'trigger_date' => 'nullable|date',
            'task_title' => 'nullable|string|max:255',
            'enabled' => 'sometimes|boolean',
        ]);

        Automation::create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_config' => array_filter([
                'date' => $validated['trigger_date'] ?? null,
            ]),
            'action_type' => $validated['action_type'],
            'action_config' => array_filter([
                'title' => $validated['task_title'] ?? $validated['name'],
            ]),
            'enabled' => $request->boolean('enabled', true),
        ]);

        return redirect()->route('automations.settings')->with('success', 'Automation created.');
    }

    public function update(Request $request, Automation $automation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAutomation($automation, $user);

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $automation->update(['enabled' => $validated['enabled']]);

        return redirect()->route('automations.settings')->with('success', 'Automation updated.');
    }

    public function destroy(Request $request, Automation $automation): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeAutomation($automation, $user);
        $automation->delete();

        return redirect()->route('automations.settings')->with('success', 'Automation deleted.');
    }

    private function authorizeAutomation(Automation $automation, $user): void
    {
        if (! $user->company_id || (int) $automation->company_id !== (int) $user->company_id) {
            abort(404);
        }
    }
}
