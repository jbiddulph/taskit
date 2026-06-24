<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectGroup;
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

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'data' => $group,
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

        $todoCount = $projectGroup->todos()->count();

        DB::transaction(function () use ($projectGroup): void {
            $projectGroup->todos()->delete();
            $projectGroup->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Group and {$todoCount} todos deleted successfully",
        ]);
    }
}
