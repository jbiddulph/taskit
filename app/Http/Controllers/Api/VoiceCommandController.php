<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoiceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VoiceCommandController extends Controller
{
    public function __construct(
        protected VoiceCommandService $voiceCommandService,
    ) {}

    public function process(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transcript' => 'required_without_all:confirm_delete,confirm_task_id|string|max:2000',
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
            'confirm_delete' => 'sometimes|boolean',
            'delete_todo_id' => 'required_if:confirm_delete,true|integer|exists:taskit_todos,id',
            'confirm_task_id' => 'sometimes|integer|exists:taskit_todos,id',
            'confirmed_intent' => 'required_with:confirm_task_id|in:update,delete,move_board',
            'confirmed_payload' => 'required_with:confirm_task_id|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            $result = $this->voiceCommandService->process(
                $user,
                (string) $request->input('transcript', ''),
                $request->filled('project_id') ? (int) $request->input('project_id') : null,
                $request->boolean('confirm_delete'),
                $request->input('delete_todo_id') ? (int) $request->input('delete_todo_id') : null,
                $request->input('confirm_task_id') ? (int) $request->input('confirm_task_id') : null,
                $request->input('confirmed_intent'),
                $request->input('confirmed_payload'),
            );

            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
