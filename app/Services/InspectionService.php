<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\OperationalObject;
use App\Models\User;
use App\Support\InspectionTemplates;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InspectionService
{
    public function __construct(
        protected InspectionPdfService $pdfService,
        protected ComplianceRequirementService $complianceRequirementService,
        protected InspectionFollowUpTaskService $followUpTaskService,
        protected OperationalLinkedTodoService $linkedTodoService,
    ) {}

    public function createDraft(
        OperationalObject $object,
        User $inspector,
        string $templateKey,
        ?string $industry = null,
    ): Inspection {
        $template = InspectionTemplates::get($industry ?? $object->company?->industry, $templateKey);

        if (! $template) {
            throw new \InvalidArgumentException('Unknown inspection template.');
        }

        $complianceRequirementId = null;
        if (! empty($template['compliance_type'])) {
            $requirement = ComplianceRequirement::query()
                ->where('operational_object_id', $object->id)
                ->where('requirement_type', $template['compliance_type'])
                ->first();
            $complianceRequirementId = $requirement?->id;
        }

        return Inspection::create([
            'company_id' => $object->company_id,
            'operational_object_id' => $object->id,
            'compliance_requirement_id' => $complianceRequirementId,
            'inspector_user_id' => $inspector->id,
            'template_key' => $templateKey,
            'label' => $template['label'],
            'status' => Inspection::STATUS_DRAFT,
            'responses' => [],
        ]);
    }

    public function saveResponses(Inspection $inspection, array $responses, ?string $summary = null): Inspection
    {
        $inspection->update([
            'responses' => $responses,
            'summary' => $summary,
        ]);

        return $inspection->fresh();
    }

    public function addPhoto(
        Inspection $inspection,
        UploadedFile $file,
        ?string $itemKey = null,
        ?string $caption = null,
    ): InspectionPhoto {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('inspection-photos', $filename, 'private');

        return InspectionPhoto::create([
            'inspection_id' => $inspection->id,
            'item_key' => $itemKey,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'caption' => $caption,
            'sort_order' => $inspection->photos()->count(),
        ]);
    }

    public function complete(Inspection $inspection, ?string $industry = null, ?int $projectId = null): array
    {
        $industry = $industry ?? $inspection->operationalObject?->company?->industry;
        $pdfPath = $this->pdfService->generate($inspection, $industry);

        $inspection->update([
            'status' => Inspection::STATUS_COMPLETED,
            'completed_at' => now(),
            'pdf_path' => $pdfPath,
        ]);

        $hasFailures = $this->followUpTaskService->hasFailedItems($inspection, $industry);
        $followUpTasks = $this->followUpTaskService->createForFailedItems($inspection, $projectId);

        if ($inspection->compliance_requirement_id && ! $hasFailures) {
            $requirement = $inspection->complianceRequirement;
            if ($requirement) {
                $this->complianceRequirementService->markCompleted($requirement, Carbon::now());
            }
        }

        $inspection = $inspection->fresh(['photos', 'operationalObject', 'inspector', 'followUpTasks']);

        return [
            'inspection' => $inspection,
            'follow_up_tasks' => $followUpTasks,
            'has_failures' => $hasFailures,
        ];
    }

    public function delete(Inspection $inspection): int
    {
        $inspection->load('photos');

        $linkedTodoCount = $this->linkedTodoService->deleteForInspection($inspection);

        if ($inspection->pdf_path && Storage::disk('private')->exists($inspection->pdf_path)) {
            Storage::disk('private')->delete($inspection->pdf_path);
        }

        foreach ($inspection->photos as $photo) {
            if ($photo->file_path && Storage::disk('private')->exists($photo->file_path)) {
                Storage::disk('private')->delete($photo->file_path);
            }
        }

        $inspection->delete();

        return $linkedTodoCount;
    }
}
