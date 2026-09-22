<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OperationalObject;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetController extends PlatformController
{
    /**
     * Assets are backed by taskit_operational_objects — the existing generic
     * site/vehicle/equipment model — exposed under the platform Asset API.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $query = OperationalObject::query()
            ->forCompany($companyId)
            ->with('workspace')
            ->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->workspace_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        return $this->ok([
            'assets' => collect($paginator->items())->map(fn (OperationalObject $asset) => $this->transform($asset)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $asset = $this->findCompanyAsset($request, $id);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $asset->load('workspace');

        return $this->ok($this->transform($asset));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->platformUser($request);
        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'workspace_id' => 'nullable|integer|exists:taskit_workspaces,id',
            'status' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $workspaceId = null;
        if ($request->filled('workspace_id')) {
            $workspace = Workspace::query()
                ->forCompany($companyId)
                ->where('id', (int) $request->workspace_id)
                ->first();

            if (! $workspace) {
                return $this->fail('Workspace not found in your company.', 404);
            }
            $workspaceId = $workspace->id;
        }

        $asset = OperationalObject::create([
            'company_id' => $companyId,
            'workspace_id' => $workspaceId,
            'type' => $request->input('type'),
            'name' => $request->input('name'),
            'reference' => $request->input('reference'),
            'status' => $request->input('status', 'active'),
            'metadata' => $request->input('metadata'),
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->input('city'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'notes' => $request->input('notes'),
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        return $this->ok($this->transform($asset), 'Asset created.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $asset = $this->findCompanyAsset($request, $id);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $companyId = $this->companyId($request);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'reference' => 'nullable|string|max:255',
            'workspace_id' => 'nullable|integer|exists:taskit_workspaces,id',
            'status' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $data = $request->only([
            'type', 'name', 'reference', 'status', 'metadata', 'is_active',
            'address_line_1', 'address_line_2', 'city', 'postal_code', 'country', 'notes',
        ]);

        if ($request->has('workspace_id')) {
            if ($request->filled('workspace_id')) {
                $workspace = Workspace::query()
                    ->forCompany($companyId)
                    ->where('id', (int) $request->workspace_id)
                    ->first();

                if (! $workspace) {
                    return $this->fail('Workspace not found in your company.', 404);
                }
                $data['workspace_id'] = $workspace->id;
            } else {
                $data['workspace_id'] = null;
            }
        }

        $asset->update($data);

        return $this->ok($this->transform($asset->fresh()), 'Asset updated.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $asset = $this->findCompanyAsset($request, $id);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $asset->update(['is_active' => false, 'status' => 'inactive']);

        return $this->ok(null, 'Asset deactivated.');
    }

    private function findCompanyAsset(Request $request, int $id): ?OperationalObject
    {
        return OperationalObject::query()
            ->where('company_id', $this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function transform(OperationalObject $asset): array
    {
        return [
            'id' => $asset->id,
            'company_id' => $asset->company_id,
            'workspace_id' => $asset->workspace_id,
            'type' => $asset->type,
            'name' => $asset->name,
            'reference' => $asset->reference,
            'status' => $asset->status ?? ($asset->is_active ? 'active' : 'inactive'),
            'metadata' => $asset->metadata,
            'address' => [
                'line_1' => $asset->address_line_1,
                'line_2' => $asset->address_line_2,
                'city' => $asset->city,
                'postal_code' => $asset->postal_code,
                'country' => $asset->country,
            ],
            'created_at' => optional($asset->created_at)?->toIso8601String(),
            'updated_at' => optional($asset->updated_at)?->toIso8601String(),
        ];
    }
}
