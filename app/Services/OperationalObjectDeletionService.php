<?php

namespace App\Services;

use App\Models\OperationalObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OperationalObjectDeletionService
{
    public function deleteWithDescendants(OperationalObject $object): void
    {
        DB::transaction(function () use ($object) {
            $object->load(['children', 'documents', 'inspections.photos']);

            foreach ($object->children as $child) {
                $this->deleteWithDescendants($child);
            }

            $this->deleteStoredFiles($object);
            $object->delete();
        });
    }

    protected function deleteStoredFiles(OperationalObject $object): void
    {
        foreach ($object->documents as $document) {
            if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
        }

        foreach ($object->inspections as $inspection) {
            if ($inspection->pdf_path && Storage::disk('private')->exists($inspection->pdf_path)) {
                Storage::disk('private')->delete($inspection->pdf_path);
            }

            foreach ($inspection->photos as $photo) {
                if ($photo->file_path && Storage::disk('private')->exists($photo->file_path)) {
                    Storage::disk('private')->delete($photo->file_path);
                }
            }
        }
    }
}
