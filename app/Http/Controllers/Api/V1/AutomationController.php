<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Automation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AutomationController extends PlatformController
{
    private const TRIGGERS = [
        Automation::TRIGGER_DATE_REACHED,
        Automation::TRIGGER_TASK_CREATED,
        Automation::TRIGGER_TASK_COMPLETED,
        Automation::TRIGGER_TASK_OVERDUE,
        Automation::TRIGGER_ASSET_CREATED,
        Automation::TRIGGER_DOCUMENT_UPLOADED,
        Automation::TRIGGER_COMPLIANCE_EXPIRING,
    ];

    private const ACTIONS = [
        Automation::ACTION_CREATE_TASK,
        Automation::ACTION_SEND_NOTIFICATION,
        Automation::ACTION_SEND_EMAIL,
        Automation::ACTION_ASSIGN_USER,
        Automation::ACTION_CHANGE_STATUS,
        Automation::ACTION_CREATE_FUTURE_TASK,
    ];

    public function index(Request $request): JsonResponse
    {
        $automations = Automation::query()
            ->forCompany($this->companyId($request))
            ->orderBy('name')
            ->get()
            ->map(fn (Automation $automation) => $this->transform($automation));

        return $this->ok(['automations' => $automations]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->platformUser($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'trigger_type' => ['required', Rule::in(self::TRIGGERS)],
            'trigger_config' => 'nullable|array',
            'action_type' => ['required', Rule::in(self::ACTIONS)],
            'action_config' => 'nullable|array',
            'enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $automation = Automation::create([
            'company_id' => $this->companyId($request),
            'created_by' => $user->id,
            'name' => $request->input('name'),
            'trigger_type' => $request->input('trigger_type'),
            'trigger_config' => $request->input('trigger_config'),
            'action_type' => $request->input('action_type'),
            'action_config' => $request->input('action_config'),
            'enabled' => $request->boolean('enabled', true),
        ]);

        return $this->ok($this->transform($automation), 'Automation created.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $automation = $this->findCompanyAutomation($request, $id);
        if (! $automation) {
            return $this->fail('Automation not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'trigger_type' => ['sometimes', Rule::in(self::TRIGGERS)],
            'trigger_config' => 'nullable|array',
            'action_type' => ['sometimes', Rule::in(self::ACTIONS)],
            'action_config' => 'nullable|array',
            'enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $automation->update($request->only([
            'name', 'trigger_type', 'trigger_config', 'action_type', 'action_config', 'enabled',
        ]));

        return $this->ok($this->transform($automation->fresh()), 'Automation updated.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $automation = $this->findCompanyAutomation($request, $id);
        if (! $automation) {
            return $this->fail('Automation not found.', 404);
        }

        $automation->delete();

        return $this->ok(null, 'Automation deleted.');
    }

    private function findCompanyAutomation(Request $request, int $id): ?Automation
    {
        return Automation::query()
            ->forCompany($this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function transform(Automation $automation): array
    {
        return [
            'id' => $automation->id,
            'company_id' => $automation->company_id,
            'name' => $automation->name,
            'trigger_type' => $automation->trigger_type,
            'trigger_config' => $automation->trigger_config,
            'action_type' => $automation->action_type,
            'action_config' => $automation->action_config,
            'enabled' => $automation->enabled,
            'last_run_at' => optional($automation->last_run_at)?->toIso8601String(),
            'created_at' => optional($automation->created_at)?->toIso8601String(),
            'updated_at' => optional($automation->updated_at)?->toIso8601String(),
        ];
    }
}
