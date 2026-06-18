<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MeetingNotesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MeetingNotesController extends Controller
{
    public function __construct(
        protected MeetingNotesService $meetingNotesService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audio' => 'required|file|mimes:webm,ogg,mp3,wav,m4a,mp4,mpeg|max:25600',
            'duration_seconds' => 'required|integer|min:1|max:1800',
            'stopped_reason' => 'required|in:manual,timeout',
            'recorded_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $audio = $request->file('audio');

        try {
            $transcript = $this->meetingNotesService->transcribe($audio);

            $this->meetingNotesService->sendToN8n($user, $transcript, [
                'duration_seconds' => (int) $request->input('duration_seconds'),
                'stopped_reason' => $request->input('stopped_reason'),
                'recorded_at' => $request->input('recorded_at') ?? now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meeting notes recorded and sent successfully.',
                'data' => [
                    'transcript' => $transcript,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            logger()->error('Meeting notes processing failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process meeting notes recording.',
            ], 500);
        }
    }
}
