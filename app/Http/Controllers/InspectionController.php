<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionPhoto;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Services\InspectionService;
use App\Support\InspectionTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
    ) {}

    public function create(Request $request, OperationalObject $site): Response
    {
        $user = $this->authorizeSite($site);

        $templateKey = $request->query('template', 'general_inspection');
        $industry = $user->company?->industry;
        $template = InspectionTemplates::get($industry, $templateKey);

        if (! $template) {
            abort(404, 'Inspection template not found.');
        }

        $inspection = $this->inspectionService->createDraft($site, $user, $templateKey, $industry);

        return $this->renderForm($inspection, $template, $user);
    }

    public function show(Inspection $inspection): Response
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user)) {
            abort(403);
        }

        $industry = $user->company?->industry;
        $template = InspectionTemplates::get($industry, $inspection->template_key) ?? ['items' => []];

        return $this->renderForm($inspection, $template, $user, $inspection->isCompleted());
    }

    public function downloadPdf(Inspection $inspection)
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user) || ! $inspection->pdf_path) {
            abort(404);
        }

        if (! Storage::disk('private')->exists($inspection->pdf_path)) {
            abort(404);
        }

        $filename = Str::slug($inspection->label).'-'.($inspection->completed_at?->format('Y-m-d') ?? 'report').'.pdf';

        return Storage::disk('private')->download($inspection->pdf_path, $filename);
    }

    public function showPhoto(Inspection $inspection, InspectionPhoto $photo)
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user) || $photo->inspection_id !== $inspection->id) {
            abort(403);
        }

        if (! Storage::disk('private')->exists($photo->file_path)) {
            abort(404);
        }

        return Storage::disk('private')->response($photo->file_path, $photo->original_filename, [
            'Content-Type' => $photo->mime_type,
        ]);
    }

    protected function renderForm(Inspection $inspection, array $template, $user, bool $readOnly = false): Response
    {
        $inspection->load(['operationalObject', 'photos', 'inspector', 'followUpTasks.project']);

        return Inertia::render('Inspections/Form', [
            'inspection' => [
                'id' => $inspection->id,
                'label' => $inspection->label,
                'template_key' => $inspection->template_key,
                'status' => $inspection->status,
                'responses' => $inspection->responses ?? [],
                'summary' => $inspection->summary,
                'completed_at' => $inspection->completed_at?->format('j M Y H:i'),
                'pdf_url' => $inspection->pdf_path
                    ? route('inspections.pdf', $inspection)
                    : null,
                'site' => [
                    'id' => $inspection->operationalObject->id,
                    'name' => $inspection->operationalObject->name,
                    'full_address' => $inspection->operationalObject->full_address,
                ],
                'photos' => $inspection->photos->map(fn ($p) => [
                    'id' => $p->id,
                    'item_key' => $p->item_key,
                    'caption' => $p->caption,
                    'original_filename' => $p->original_filename,
                    'url' => route('inspections.photos.show', [$inspection, $p]),
                ]),
                'follow_up_tasks' => $inspection->followUpTasks->map(fn ($todo) => [
                    'id' => $todo->id,
                    'title' => $todo->title,
                    'status' => $todo->status,
                    'due_date' => $todo->due_date?->format('j M Y'),
                    'project_key' => $todo->project?->key,
                ]),
            ],
            'templateItems' => $template['items'] ?? [],
            'readOnly' => $readOnly,
            'projects' => Project::query()
                ->where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('viewing_order')
                ->get(['id', 'name', 'key'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'key' => $p->key]),
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'code' => $user->company->code,
                'subscription_type' => $user->company->subscription_type,
            ] : null,
        ]);
    }

    protected function authorizeSite(OperationalObject $site)
    {
        $user = Auth::user();
        if (! $user->company_id || ! $site->canAccess($user)) {
            abort(403);
        }

        return $user;
    }

    public function destroy(Inspection $inspection)
    {
        $user = Auth::user();
        if (! $inspection->canAccess($user)) {
            abort(403);
        }

        $siteId = $inspection->operational_object_id;
        $this->inspectionService->delete($inspection);

        return redirect()->route('sites.show', $siteId)->with('success', 'Inspection deleted.');
    }
}
