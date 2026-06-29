<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\Todo;
use App\Services\InspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
    ) {}

    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user) || $inspection->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'responses' => 'required|array',
            'summary' => 'nullable|string|max:5000',
        ]);

        $this->inspectionService->saveResponses(
            $inspection,
            $validated['responses'],
            $validated['summary'] ?? null,
        );

        return response()->json(['success' => true, 'message' => 'Inspection saved.']);
    }

    public function storePhoto(Request $request, Inspection $inspection): JsonResponse
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user) || $inspection->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:10240',
            'item_key' => 'nullable|string|max:100',
            'caption' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $photo = $this->inspectionService->addPhoto(
            $inspection,
            $request->file('photo'),
            $request->input('item_key'),
            $request->input('caption'),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $photo->id,
                'original_filename' => $photo->original_filename,
                'url' => route('inspections.photos.show', [$inspection, $photo]),
            ],
        ]);
    }

    public function complete(Request $request, Inspection $inspection): JsonResponse
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user) || $inspection->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
        ]);

        $result = $this->inspectionService->complete(
            $inspection,
            $user->company?->industry,
            $request->input('project_id'),
        );

        $completed = $result['inspection'];
        $followUpCount = count($result['follow_up_tasks']);

        $message = 'Inspection completed and PDF generated.';
        if ($followUpCount > 0) {
            $message .= " {$followUpCount} follow-up task(s) created on your board.";
        }
        if ($result['has_failures'] && $inspection->compliance_requirement_id) {
            $message .= ' Compliance was not marked complete due to failed items.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'pdf_url' => route('inspections.pdf', $completed),
                'redirect' => route('inspections.show', $completed),
                'follow_up_task_count' => $followUpCount,
                'follow_up_tasks' => collect($result['follow_up_tasks'])->map(fn (Todo $todo) => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'project_id' => $todo->project_id,
                ]),
                'has_failures' => $result['has_failures'],
            ],
        ]);
    }
}
