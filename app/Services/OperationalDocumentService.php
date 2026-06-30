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
    ) {}

    public function store(
        OperationalObject $object,
        User $user,
        UploadedFile $file,
        array $attributes = [],
        bool $runExtraction = true,
    ): array {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('operational-documents', $filename, 'private');

        $document = OperationalDocument::create([
            'company_id' => $object->company_id,
            'operational_object_id' => $object->id,
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
        ]);

        $proposal = null;
        if ($runExtraction) {
            $proposal = $this->extractionService->extractFromDocument(
                $document,
                $user,
                isset($attributes['project_id']) ? (int) $attributes['project_id'] : null,
            );
        }

        return ['document' => $document, 'proposal' => $proposal];
    }
}
