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
            'transcript' => 'required_without:confirm_delete|string|max:2000',
            'project_id' => 'required|integer|exists:taskit_projects,id',
            'confirm_delete' => 'sometimes|boolean',
            'delete_todo_id' => 'required_if:confirm_delete,true|integer|exists:taskit_todos,id',
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
                (int) $request->input('project_id'),
                $request->boolean('confirm_delete'),
                $request->input('delete_todo_id') ? (int) $request->input('delete_todo_id') : null,
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
