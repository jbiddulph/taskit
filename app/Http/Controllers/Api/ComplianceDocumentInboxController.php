<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\OperationalDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk / inbox certificate upload for Property Compliance AI.
 * Files can be dropped without choosing a site — AI extracts the address and matches a property.
 */
class ComplianceDocumentInboxController extends Controller
{
    public function __construct(
        protected OperationalDocumentService $documentService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:20480',
            'files' => 'sometimes|array|max:50',
            'files.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:20480',
            'title' => 'nullable|string|max:255',
            'extract' => 'sometimes|boolean',
            'project_id' => 'nullable|integer|exists:taskit_projects,id',
            'site_id' => 'nullable|integer|exists:taskit_operational_objects,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('project_id')) {
            $projectOk = Project::query()
                ->where('company_id', $user->company_id)
                ->where('id', (int) $request->input('project_id'))
                ->exists();
            if (! $projectOk) {
                return response()->json(['success' => false, 'message' => 'Invalid project.'], 422);
            }
        }

        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files') ?: [];
        }
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        if ($files === []) {
            return response()->json(['success' => false, 'message' => 'No files uploaded.'], 422);
        }

        $results = [];
        $allowanceExceeded = false;
        $extractedCount = 0;
        $company = $user->company;
        $documentAi = $company?->getDocumentAiUsageSummary();
        $wantExtract = $request->boolean('extract', true);

        foreach ($files as $file) {
            $runExtraction = $wantExtract && ! $allowanceExceeded;

            if ($runExtraction && $company && ! $company->canConsumeDocumentAi()) {
                $allowanceExceeded = true;
                $runExtraction = false;
                $documentAi = $company->getDocumentAiUsageSummary();
            }

            $stored = $this->documentService->storeForCompany(
                (int) $user->company_id,
                $user,
                $file,
                [
                    'title' => $request->input('title'),
                    'project_id' => $request->input('project_id'),
                    'site_id' => $request->input('site_id'),
                ],
                $runExtraction,
            );

            if (! empty($stored['allowance_exceeded'])) {
                $allowanceExceeded = true;
                $documentAi = $stored['document_ai'] ?? $documentAi;
            }

            $document = $stored['document'];
            $proposal = $stored['proposal'];
            $match = $stored['match'] ?? null;

            if ($proposal) {
                $extractedCount++;
            }

            $results[] = [
                'document' => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'original_filename' => $document->original_filename,
                    'match_status' => $document->match_status,
                    'match_confidence' => $document->match_confidence,
                    'site_id' => $document->operational_object_id,
                ],
                'proposal_id' => $proposal?->id,
                'extraction_skipped' => $wantExtract && (! $runExtraction || ! empty($stored['allowance_exceeded'])),
                'match' => $match ? [
                    'status' => $match['status'],
                    'confidence' => $match['confidence'],
                    'reason' => $match['reason'],
                    'site' => $match['site'] ? [
                        'id' => $match['site']->id,
                        'name' => $match['site']->name,
                    ] : null,
                ] : null,
            ];
        }

        $matched = collect($results)->filter(fn ($r) => ($r['match']['status'] ?? null) === 'matched')->count();
        $suggested = collect($results)->filter(fn ($r) => ($r['match']['status'] ?? null) === 'suggested')->count();
        $unmatched = count($results) - $matched - $suggested;
        $documentAi = $company?->fresh()?->getDocumentAiUsageSummary() ?? $documentAi;

        $message = count($results) === 1
            ? $this->singleMessage($results[0])
            : sprintf(
                'Uploaded %d files — %d matched, %d suggested, %d need a property.',
                count($results),
                $matched,
                $suggested,
                $unmatched,
            );

        if ($allowanceExceeded) {
            $message .= $extractedCount > 0
                ? ' Some files were stored without AI extraction because your monthly allowance was reached.'
                : ' Document AI allowance reached — files were stored without AI extraction. Upgrade for more reads.';
        }

        $status = ($allowanceExceeded && $extractedCount === 0 && $wantExtract) ? 429 : 200;

        return response()->json([
            'success' => $status === 200,
            'message' => $message,
            'error' => $status === 429 ? 'document_ai_allowance_exceeded' : null,
            'document_ai' => $documentAi,
            'data' => [
                'results' => $results,
                'summary' => [
                    'uploaded' => count($results),
                    'matched' => $matched,
                    'suggested' => $suggested,
                    'unmatched' => $unmatched,
                    'extracted' => $extractedCount,
                    'allowance_exceeded' => $allowanceExceeded,
                ],
            ],
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function singleMessage(array $result): string
    {
        $status = $result['match']['status'] ?? null;
        $siteName = $result['match']['site']['name'] ?? null;

        return match ($status) {
            'matched' => $siteName
                ? "Uploaded and matched to {$siteName}. Review the AI extraction to confirm."
                : 'Uploaded and matched to a property. Review the AI extraction to confirm.',
            'suggested' => $siteName
                ? "Uploaded. Suggested property: {$siteName}. Review and confirm."
                : 'Uploaded. Review the suggested property and extraction.',
            default => 'Uploaded to the inbox. Choose a property when reviewing the extraction.',
        };
    }
}
