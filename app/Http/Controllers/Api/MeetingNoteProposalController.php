<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingNoteProposal;
use App\Services\MeetingNoteProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MeetingNoteProposalController extends Controller
{
    public function __construct(
        protected MeetingNoteProposalService $proposalService
    ) {}

    public function pending(): JsonResponse
    {
        $proposals = MeetingNoteProposal::query()
            ->where('user_id', Auth::id())
            ->where('status', MeetingNoteProposal::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $proposals,
        ]);
    }

    public function show(MeetingNoteProposal $proposal): JsonResponse
    {
        if ($proposal->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $proposal,
        ]);
    }

    public function approve(Request $request, MeetingNoteProposal $proposal): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|integer|exists:taskit_projects,id',
            'items' => 'required|array|min:1',
        ]);

        try {
            $todos = $this->proposalService->approve(
                Auth::user(),
                $proposal,
                (int) $request->input('project_id'),
                $request->input('items')
            );

            return response()->json([
                'success' => true,
                'message' => count($todos).' todo(s) created.',
                'data' => [
                    'created_count' => count($todos),
                    'todos' => $todos,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function dismiss(MeetingNoteProposal $proposal): JsonResponse
    {
        $this->proposalService->dismiss(Auth::user(), $proposal);

        return response()->json([
            'success' => true,
            'message' => 'Meeting note proposal dismissed.',
        ]);
    }
}
