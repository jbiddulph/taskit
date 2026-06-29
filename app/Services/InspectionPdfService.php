<?php

namespace App\Services;

use App\Models\Inspection;
use App\Support\InspectionTemplates;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InspectionPdfService
{
    public function generate(Inspection $inspection, ?string $industry = null): string
    {
        $inspection->load(['operationalObject.company', 'inspector', 'photos']);

        $industry = $industry ?? $inspection->operationalObject?->company?->industry;
        $template = InspectionTemplates::get($industry, $inspection->template_key) ?? [];
        $items = $template['items'] ?? [];

        $photoData = $inspection->photos->map(function ($photo) {
            $base64 = null;
            if ($photo->is_image && Storage::disk('private')->exists($photo->file_path)) {
                $contents = Storage::disk('private')->get($photo->file_path);
                $base64 = 'data:'.$photo->mime_type.';base64,'.base64_encode($contents);
            }

            return [
                'caption' => $photo->caption ?: $photo->original_filename,
                'item_key' => $photo->item_key,
                'base64' => $base64,
            ];
        });

        $pdf = Pdf::loadView('pdf.inspection-report', [
            'inspection' => $inspection,
            'site' => $inspection->operationalObject,
            'company' => $inspection->operationalObject?->company,
            'inspector' => $inspection->inspector,
            'items' => $items,
            'responses' => $inspection->responses ?? [],
            'photos' => $photoData,
        ])->setPaper('a4');

        $filename = 'inspection-'.$inspection->id.'-'.now()->format('Ymd-His').'.pdf';
        $path = 'inspection-reports/'.$filename;

        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }
}
