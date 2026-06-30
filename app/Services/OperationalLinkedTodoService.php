<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\Inspection;
use App\Models\OperationalObject;
use App\Models\Todo;
use Illuminate\Support\Collection;

class OperationalLinkedTodoService
{
    public function countForOperationalObjectTree(OperationalObject $object): int
    {
        return $this->queryForOperationalObjectTree($object)->count();
    }

    public function countForComplianceRequirement(ComplianceRequirement $requirement): int
    {
        return Todo::query()
            ->where('compliance_requirement_id', $requirement->id)
            ->count();
    }

    public function countForInspection(Inspection $inspection): int
    {
        return Todo::query()
            ->where('inspection_id', $inspection->id)
            ->count();
    }

    public function deleteForOperationalObjectTree(OperationalObject $object): int
    {
        return $this->deleteTodos($this->queryForOperationalObjectTree($object)->get(), $object->company_id);
    }

    public function deleteForComplianceRequirement(ComplianceRequirement $requirement): int
    {
        $todos = Todo::query()
            ->where('compliance_requirement_id', $requirement->id)
            ->get();

        return $this->deleteTodos($todos, $requirement->company_id);
    }

    public function deleteForInspection(Inspection $inspection): int
    {
        $todos = Todo::query()
            ->where('inspection_id', $inspection->id)
            ->get();

        return $this->deleteTodos($todos, $inspection->company_id);
    }

    protected function queryForOperationalObjectTree(OperationalObject $object)
    {
        $objectIds = $this->collectObjectIds($object);
        $requirementIds = ComplianceRequirement::query()
            ->whereIn('operational_object_id', $objectIds)
            ->pluck('id');
        $inspectionIds = Inspection::query()
            ->whereIn('operational_object_id', $objectIds)
            ->pluck('id');

        return Todo::query()->where(function ($query) use ($objectIds, $requirementIds, $inspectionIds) {
            $query->whereIn('operational_object_id', $objectIds);

            if ($requirementIds->isNotEmpty()) {
                $query->orWhereIn('compliance_requirement_id', $requirementIds);
            }

            if ($inspectionIds->isNotEmpty()) {
                $query->orWhereIn('inspection_id', $inspectionIds);
            }
        });
    }

    /**
     * @return array<int, int>
     */
    protected function collectObjectIds(OperationalObject $object): array
    {
        $ids = collect([$object->id]);
        $pending = collect([$object->id]);

        while ($pending->isNotEmpty()) {
            $childIds = OperationalObject::query()
                ->whereIn('parent_id', $pending)
                ->pluck('id');

            $ids = $ids->merge($childIds);
            $pending = $childIds;
        }

        return $ids->unique()->values()->all();
    }

    protected function deleteTodos(Collection $todos, int $companyId): int
    {
        if ($todos->isEmpty()) {
            return 0;
        }

        $todoIds = $todos->pluck('id');
        $projectIds = $todos->pluck('project_id')->filter()->unique();
        $userIds = $todos->pluck('user_id')->filter()->unique();

        $deleted = Todo::query()->whereIn('id', $todoIds)->delete();

        foreach ($userIds as $userId) {
            CacheService::invalidateUserCaches((int) $userId, $companyId);
        }

        foreach ($projectIds as $projectId) {
            CacheService::invalidateProjectCaches((int) $projectId, $companyId);
        }

        return $deleted;
    }
}
