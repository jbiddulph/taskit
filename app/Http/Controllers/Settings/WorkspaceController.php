<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            abort(403, 'A company is required to manage workspaces.');
        }

        Workspace::ensureDefaultForCompany($company);

        $workspaces = Workspace::query()
            ->forCompany($company->id)
            ->withCount(['projects', 'todos', 'assets'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'type' => $workspace->type,
                'is_default' => $workspace->is_default,
                'projects_count' => $workspace->projects_count,
                'todos_count' => $workspace->todos_count,
                'assets_count' => $workspace->assets_count,
                'created_at' => optional($workspace->created_at)?->toIso8601String(),
            ]);

        return Inertia::render('settings/Workspaces', [
            'workspaces' => $workspaces,
            'workspaceTypes' => [
                ['value' => 'general', 'label' => 'General'],
                ['value' => 'property', 'label' => 'Property'],
                ['value' => 'fleet', 'label' => 'Fleet'],
                ['value' => 'compliance', 'label' => 'Compliance'],
                ['value' => 'hr', 'label' => 'HR'],
                ['value' => 'personal', 'label' => 'Personal'],
                ['value' => 'other', 'label' => 'Other'],
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taskit_workspaces', 'name')->where(fn ($q) => $q->where('company_id', $company->id)),
            ],
            'description' => 'nullable|string|max:2000',
            'type' => 'nullable|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($request->boolean('is_default')) {
            Workspace::query()->forCompany($company->id)->update(['is_default' => false]);
        }

        Workspace::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'general',
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('workspaces.settings')->with('success', 'Workspace created.');
    }

    public function update(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeWorkspace($workspace, $user);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taskit_workspaces', 'name')
                    ->where(fn ($q) => $q->where('company_id', $user->company_id))
                    ->ignore($workspace->id),
            ],
            'description' => 'nullable|string|max:2000',
            'type' => 'nullable|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($request->boolean('is_default')) {
            Workspace::query()
                ->forCompany($user->company_id)
                ->where('id', '!=', $workspace->id)
                ->update(['is_default' => false]);
        }

        $workspace->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? $workspace->type,
            'is_default' => $request->boolean('is_default') || $workspace->is_default,
        ]);

        return redirect()->route('workspaces.settings')->with('success', 'Workspace updated.');
    }

    public function destroy(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeWorkspace($workspace, $user);

        if ($workspace->is_default) {
            return back()->withErrors(['workspace' => 'Cannot delete the default workspace.']);
        }

        $workspace->delete();

        return redirect()->route('workspaces.settings')->with('success', 'Workspace deleted.');
    }

    private function authorizeWorkspace(Workspace $workspace, $user): void
    {
        if (! $user->company_id || (int) $workspace->company_id !== (int) $user->company_id) {
            abort(404);
        }
    }
}
