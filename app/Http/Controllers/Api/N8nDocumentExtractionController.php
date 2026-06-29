<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class N8nDocumentExtractionController extends Controller
{
    public function __construct(
        protected DocumentExtractionService $extractionService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operational_document_id' => 'required|integer|exists:taskit_operational_documents,id',
            'user_id' => 'required|integer|exists:taskit_users,id',
            'extracted_data' => 'required|array',
            'summary' => 'nullable|string',
        ]);

        try {
            $proposal = $this->extractionService->createFromN8n($validated);

            return response()->json([
                'success' => true,
                'data' => ['proposal_id' => $proposal->id],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
    }
}
