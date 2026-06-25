<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGroup;
use App\Services\ViewingBoardTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectGroupController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $user = Auth::user();

        if (! $project->canAccess($user->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $groups = $project->groups()
            ->orderBy('viewing_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $user = Auth::user();

        if (! $project->canAccess($user->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'template' => 'nullable|in:viewing,listing',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $group = ProjectGroup::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'viewing_order' => ProjectGroup::getNextViewingOrder($project->id),
            'is_default' => false,
        ]);

        $seeded = 0;
        if (in_array($request->input('template'), ['viewing', 'listing'], true)) {
            $seeded = ViewingBoardTemplate::seed($group, $project, $user);
        }

        return response()->json([
            'success' => true,
            'message' => $seeded > 0
                ? "Board created with {$seeded} listing lifecycle tasks."
                : 'Group created successfully',
            'data' => $group,
            'seeded_tasks' => $seeded,
        ], 201);
    }

    public function update(Request $request, ProjectGroup $projectGroup): JsonResponse
    {
        $user = Auth::user();

        if (! $projectGroup->canAccess($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $projectGroup->update($request->only(['name', 'description', 'color']));

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully',
            'data' => $projectGroup->fresh(),
        ]);
    }

    public function destroy(ProjectGroup $projectGroup): JsonResponse
    {
        $user = Auth::user();

        if (! $projectGroup->canAccess($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($projectGroup->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'The default board cannot be deleted.',
            ], 422);
        }

        $defaultGroup = ProjectGroup::query()
            ->where('project_id', $projectGroup->project_id)
            ->where('is_default', true)
            ->first();

        $todoCount = $projectGroup->todos()->count();

        DB::transaction(function () use ($projectGroup, $defaultGroup): void {
            if ($defaultGroup) {
                $projectGroup->todos()->update(['project_group_id' => $defaultGroup->id]);
            }

            $projectGroup->delete();
        });

        return response()->json([
            'success' => true,
            'message' => $todoCount > 0
                ? "Board deleted. {$todoCount} tasks moved to the default board."
                : 'Board deleted successfully',
        ]);
    }
}
