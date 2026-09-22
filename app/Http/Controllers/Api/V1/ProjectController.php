<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends PlatformController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyId($request);

        $projects = Project::query()
            ->forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'company_id' => $project->company_id,
                'workspace_id' => $project->workspace_id,
                'name' => $project->name,
                'description' => $project->description,
                'key' => $project->key,
                'color' => $project->color,
                'status' => $project->is_active ? 'active' : 'inactive',
                'created_at' => optional($project->created_at)?->toIso8601String(),
                'updated_at' => optional($project->updated_at)?->toIso8601String(),
            ]);

        return $this->ok(['projects' => $projects]);
    }
}
