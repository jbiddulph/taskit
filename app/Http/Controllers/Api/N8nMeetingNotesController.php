<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingNoteProposal;
use App\Services\MeetingNoteProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class N8nMeetingNotesController extends Controller
{
    public function __construct(
        protected MeetingNoteProposalService $proposalService
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $proposal = $this->proposalService->createFromN8n($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Meeting note proposal created.',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'action_items_count' => count($proposal->action_items),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            logger()->error('N8N meeting note proposal failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create meeting note proposal.',
            ], 500);
        }
    }
}
