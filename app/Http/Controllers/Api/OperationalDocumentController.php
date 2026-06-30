<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Services\OperationalDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OperationalDocumentController extends Controller
{
    public function __construct(
        protected OperationalDocumentService $documentService,
    ) {}

    public function store(Request $request, OperationalObject $site): JsonResponse
    {
        $user = Auth::user();

        if (! $site->canAccess($user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'title' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'extract' => 'sometimes|boolean',
            'project_id' => 'nullable|exists:taskit_projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->documentService->store(
            $site,
            $user,
            $request->file('file'),
            [
                'title' => $request->input('title'),
                'expires_at' => $request->input('expires_at'),
                'notes' => $request->input('notes'),
                'project_id' => $request->input('project_id'),
            ],
            $request->boolean('extract', true),
        );

        return response()->json([
            'success' => true,
            'message' => $result['proposal']
                ? 'Document uploaded. Review the AI extraction.'
                : 'Document uploaded.',
            'data' => [
                'document' => $this->serializeDocument($result['document']),
                'proposal_id' => $result['proposal']?->id,
            ],
        ]);
    }

    public function download(OperationalObject $site, OperationalDocument $document)
    {
        $user = Auth::user();

        if (! $document->canAccess($user) || $document->operational_object_id !== $site->id) {
            abort(403);
        }

        if (! Storage::disk('private')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('private')->download($document->file_path, $document->original_filename);
    }

    protected function serializeDocument(OperationalDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'document_type' => $document->document_type,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'expires_at' => $document->expires_at?->format('Y-m-d'),
            'status' => $document->status,
            'extracted_data' => $document->extracted_data,
            'created_at' => $document->created_at->toIso8601String(),
        ];
    }
}
