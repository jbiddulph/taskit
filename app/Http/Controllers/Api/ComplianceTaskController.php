<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceRequirement;
use App\Services\ComplianceTaskGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ComplianceTaskController extends Controller
{
    public function __construct(
        protected ComplianceTaskGeneratorService $taskGenerator,
    ) {}

    /**
     * Create (or return) a ZapTask todo for a compliance requirement that needs attention.
     */
    public function store(ComplianceRequirement $requirement): JsonResponse
    {
        $user = Auth::user();

        if (! $user->company_id || (int) $requirement->company_id !== (int) $user->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $requirement->loadMissing(['operationalObject', 'company', 'todos']);
        $requirement->refreshStatus();

        $todo = $this->taskGenerator->createOrReturnTaskForRequirement($requirement);

        if (! $todo) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create a task. Ensure a project board exists for this company.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $todo->wasRecentlyCreated ? 'Task created on your board.' : 'Open task already exists.',
            'task' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'due_date' => optional($todo->due_date)?->toDateString(),
                'status' => $todo->status,
                'project_id' => $todo->project_id,
                'asset_id' => $todo->operational_object_id,
                'requirement_id' => $requirement->id,
            ],
        ], $todo->wasRecentlyCreated ? 201 : 200);
    }
}
