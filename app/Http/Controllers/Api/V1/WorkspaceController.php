<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WorkspaceController extends PlatformController
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = Workspace::query()
            ->forCompany($this->companyId($request))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => $this->transform($workspace));

        return $this->ok(['workspaces' => $workspaces]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $workspace = $this->findCompanyWorkspace($request, $id);
        if (! $workspace) {
            return $this->fail('Workspace not found.', 404);
        }

        return $this->ok($this->transform($workspace));
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taskit_workspaces', 'name')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        if ($request->boolean('is_default')) {
            Workspace::query()->forCompany($companyId)->update(['is_default' => false]);
        }

        $workspace = Workspace::create([
            'company_id' => $companyId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'type' => $request->input('type', 'general'),
            'is_default' => $request->boolean('is_default'),
        ]);

        return $this->ok($this->transform($workspace), 'Workspace created.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $workspace = $this->findCompanyWorkspace($request, $id);
        if (! $workspace) {
            return $this->fail('Workspace not found.', 404);
        }

        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('taskit_workspaces', 'name')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($workspace->id),
            ],
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        if ($request->boolean('is_default')) {
            Workspace::query()
                ->forCompany($companyId)
                ->where('id', '!=', $workspace->id)
                ->update(['is_default' => false]);
        }

        $workspace->update($request->only(['name', 'description', 'type', 'is_default']));

        return $this->ok($this->transform($workspace->fresh()), 'Workspace updated.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $workspace = $this->findCompanyWorkspace($request, $id);
        if (! $workspace) {
            return $this->fail('Workspace not found.', 404);
        }

        if ($workspace->is_default) {
            return $this->fail('Cannot delete the default workspace.', 422);
        }

        $workspace->delete();

        return $this->ok(null, 'Workspace deleted.');
    }

    private function findCompanyWorkspace(Request $request, int $id): ?Workspace
    {
        return Workspace::query()
            ->forCompany($this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function transform(Workspace $workspace): array
    {
        return [
            'id' => $workspace->id,
            'company_id' => $workspace->company_id,
            'name' => $workspace->name,
            'description' => $workspace->description,
            'type' => $workspace->type,
            'is_default' => $workspace->is_default,
            'created_at' => optional($workspace->created_at)?->toIso8601String(),
            'updated_at' => optional($workspace->updated_at)?->toIso8601String(),
        ];
    }
}
