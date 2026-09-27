<?php

namespace App\Services;

use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class OperationalDocumentService
{
    public function __construct(
        protected DocumentExtractionService $extractionService,
        protected PropertyAddressMatcher $addressMatcher,
    ) {}

    public function store(
        OperationalObject $object,
        User $user,
        UploadedFile $file,
        array $attributes = [],
        bool $runExtraction = true,
    ): array {
        $document = $this->createDocument(
            (int) $object->company_id,
            $user,
            $file,
            $attributes,
            $object->id,
        );

        $document->update([
            'match_status' => PropertyAddressMatcher::STATUS_MANUAL,
            'match_confidence' => 100,
        ]);

        $proposal = null;
        $match = null;
        if ($runExtraction) {
            $proposal = $this->extractionService->extractFromDocument(
                $document,
                $user,
                isset($attributes['project_id']) ? (int) $attributes['project_id'] : null,
            );
            $document->refresh();
            $match = [
                'status' => PropertyAddressMatcher::STATUS_MANUAL,
                'confidence' => 100,
                'site' => $object,
                'reason' => 'Selected on upload',
                'score' => 1.0,
            ];
        }

        return ['document' => $document, 'proposal' => $proposal, 'match' => $match];
    }

    /**
     * Upload into the company inbox. Optionally pin to a site; otherwise AI address-matching assigns one.
     */
    public function storeForCompany(
        int $companyId,
        User $user,
        UploadedFile $file,
        array $attributes = [],
        bool $runExtraction = true,
    ): array {
        $siteId = isset($attributes['site_id']) ? (int) $attributes['site_id'] : null;
        if ($siteId) {
            $site = OperationalObject::forCompany($companyId)->where('id', $siteId)->first();
            if (! $site) {
                $siteId = null;
            }
        }

        $document = $this->createDocument(
            $companyId,
            $user,
            $file,
            $attributes,
            $siteId,
        );

        if ($siteId) {
            $document->update([
                'match_status' => PropertyAddressMatcher::STATUS_MANUAL,
                'match_confidence' => 100,
            ]);
        }

        $proposal = null;
        $match = null;
        if ($runExtraction) {
            $proposal = $this->extractionService->extractFromDocument(
                $document,
                $user,
                isset($attributes['project_id']) ? (int) $attributes['project_id'] : null,
            );
            $document->refresh();

            if ($document->operational_object_id) {
                $site = OperationalObject::find($document->operational_object_id);
                $match = [
                    'status' => $document->match_status ?? PropertyAddressMatcher::STATUS_MATCHED,
                    'confidence' => $document->match_confidence ?? 0,
                    'site' => $site,
                    'reason' => $proposal?->metadata['match_reason'] ?? null,
                    'score' => ($document->match_confidence ?? 0) / 100,
                ];
            } else {
                $match = $this->addressMatcher->match(
                    $companyId,
                    null,
                    $file->getClientOriginalName(),
                );
                $this->applyMatchToDocument($document, $match, $proposal);
                $document->refresh();
            }
        } elseif (! $siteId) {
            $match = $this->addressMatcher->match($companyId, null, $file->getClientOriginalName());
            $this->applyMatchToDocument($document, $match, null);
            $document->refresh();
        }

        return ['document' => $document->fresh(), 'proposal' => $proposal?->fresh(), 'match' => $match];
    }

    /**
     * @param  array{status: string, confidence: int, site: OperationalObject|null, score: float, reason: string|null}  $match
     */
    public function applyMatchToDocument(
        OperationalDocument $document,
        array $match,
        $proposal = null,
    ): void {
        $site = $match['site'] ?? null;
        $status = $match['status'];

        $updates = [
            'match_status' => $status,
            'match_confidence' => $match['confidence'] ?? null,
        ];

        if ($status === PropertyAddressMatcher::STATUS_MATCHED && $site) {
            $updates['operational_object_id'] = $site->id;
        }

        $document->update($updates);

        if ($proposal) {
            $meta = $proposal->metadata ?? [];
            $meta['match_status'] = $status;
            $meta['match_confidence'] = $match['confidence'] ?? null;
            $meta['match_reason'] = $match['reason'] ?? null;
            $proposal->update([
                'operational_object_id' => $document->fresh()->operational_object_id,
                'suggested_operational_object_id' => $site?->id,
                'metadata' => $meta,
            ]);
        }
    }

    private function createDocument(
        int $companyId,
        User $user,
        UploadedFile $file,
        array $attributes,
        ?int $siteId,
    ): OperationalDocument {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('operational-documents', $filename, 'private');

        return OperationalDocument::create([
            'company_id' => $companyId,
            'operational_object_id' => $siteId,
            'uploaded_by_user_id' => $user->id,
            'title' => $attributes['title'] ?? $file->getClientOriginalName(),
            'document_type' => $attributes['document_type'] ?? null,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'expires_at' => $attributes['expires_at'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'match_status' => $siteId ? PropertyAddressMatcher::STATUS_MANUAL : PropertyAddressMatcher::STATUS_UNMATCHED,
        ]);
    }
}
