<?php

namespace App\Services;

use App\Models\OperationalDocument;
use Illuminate\Support\Facades\Storage;

class OperationalDocumentDeletionService
{
    public function delete(OperationalDocument $document): void
    {
        if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
            Storage::disk('private')->delete($document->file_path);
        }

        $document->delete();
    }
}
