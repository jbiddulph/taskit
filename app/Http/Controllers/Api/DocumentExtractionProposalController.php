<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentExtractionProposal;
use App\Services\DocumentExtractionProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DocumentExtractionProposalController extends Controller
{
    public function __construct(
        protected DocumentExtractionProposalService $proposalService,
    ) {}

    public function pending(): JsonResponse
    {
        $proposals = DocumentExtractionProposal::query()
            ->where('user_id', Auth::id())
            ->where('status', DocumentExtractionProposal::STATUS_PENDING)
            ->with(['operationalDocument', 'operationalObject'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($proposal) => $this->serialize($proposal));

        return response()->json(['success' => true, 'data' => $proposals]);
    }

    public function approve(Request $request, DocumentExtractionProposal $proposal): JsonResponse
    {
        $request->validate([
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
        ]);

        try {
            $result = $this->proposalService->approve(
                Auth::user(),
                $proposal,
                $request->input('project_id'),
            );

            $message = $result['task']
                ? 'Document details applied and reminder task created.'
                : 'Document details applied.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'document' => $result['document'],
                    'requirement' => $result['requirement'],
                    'task' => $result['task'] ? [
                        'id' => $result['task']->id,
                        'title' => $result['task']->title,
                        'project_id' => $result['task']->project_id,
                    ] : null,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }

    public function dismiss(DocumentExtractionProposal $proposal): JsonResponse
    {
        $this->proposalService->dismiss(Auth::user(), $proposal);

        return response()->json([
            'success' => true,
            'message' => 'Extraction dismissed.',
        ]);
    }

    protected function serialize(DocumentExtractionProposal $proposal): array
    {
        return [
            'id' => $proposal->id,
            'status' => $proposal->status,
            'extracted_data' => $proposal->extracted_data,
            'summary' => $proposal->summary,
            'site' => $proposal->operationalObject ? [
                'id' => $proposal->operationalObject->id,
                'name' => $proposal->operationalObject->name,
            ] : null,
            'document' => $proposal->operationalDocument ? [
                'id' => $proposal->operationalDocument->id,
                'title' => $proposal->operationalDocument->title,
                'original_filename' => $proposal->operationalDocument->original_filename,
            ] : null,
            'created_at' => $proposal->created_at->toIso8601String(),
        ];
    }
}
