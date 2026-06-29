<?php

namespace App\Http\Controllers;

use App\Models\ComplianceRequirement;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\Project;
use App\Services\ComplianceRequirementService;
use App\Support\ComplianceTemplates;
use App\Support\InspectionTemplates;
use App\Support\OperationalObjectTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OperationalObjectController extends Controller
{
    public function __construct(
        protected ComplianceRequirementService $complianceService,
    ) {}

    public function index(): Response
    {
        $user = $this->requireCompanyUser();

        $objects = OperationalObject::forCompany($user->company_id)
            ->with(['parent', 'complianceRequirements'])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($object) => $this->serializeObject($object));

        return Inertia::render('Sites/Index', [
            'sites' => $objects,
            'complianceSummary' => $this->complianceSummary($user->company_id),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'company' => $this->companyPayload($user),
        ]);
    }

    public function create(): Response
    {
        $user = $this->requireCompanyUser();

        return Inertia::render('Sites/Create', [
            'objectTypes' => OperationalObjectTypes::choices(),
            'parentOptions' => $this->parentOptions($user->company_id),
            'projects' => $this->projectOptions($user->company_id),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'company' => $this->companyPayload($user),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->requireCompanyUser();

        $validated = $request->validate([
            'type' => OperationalObjectTypes::validationRule(),
            'name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:taskit_operational_objects,id',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
            'apply_compliance_template' => 'sometimes|boolean',
            'default_project_id' => 'nullable|exists:taskit_projects,id',
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = OperationalObject::findOrFail($validated['parent_id']);
            if ($parent->company_id !== $user->company_id) {
                abort(403);
            }
        }

        $object = OperationalObject::create([
            ...collect($validated)->except(['apply_compliance_template', 'default_project_id'])->all(),
            'company_id' => $user->company_id,
            'created_by_user_id' => $user->id,
        ]);

        if ($request->boolean('apply_compliance_template')) {
            $this->complianceService->applyIndustryTemplate(
                $object,
                $validated['default_project_id'] ?? null,
            );
        }

        return redirect()->route('sites.show', $object)->with('success', 'Site created successfully!');
    }

    public function show(OperationalObject $site): Response
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $site->load(['parent', 'children', 'complianceRequirements', 'createdBy', 'documents', 'inspections.inspector']);

        return Inertia::render('Sites/Show', [
            'site' => $this->serializeObjectDetail($site),
            'projects' => $this->projectOptions($user->company_id),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'inspectionTemplates' => InspectionTemplates::choices($user->company?->industry),
            'pendingDocumentProposals' => DocumentExtractionProposal::query()
                ->where('user_id', $user->id)
                ->where('operational_object_id', $site->id)
                ->where('status', DocumentExtractionProposal::STATUS_PENDING)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'extracted_data' => $p->extracted_data,
                    'summary' => $p->summary,
                    'document_title' => $p->operationalDocument?->original_filename,
                ]),
            'company' => $this->companyPayload($user),
        ]);
    }

    public function edit(OperationalObject $site): Response
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        return Inertia::render('Sites/Edit', [
            'site' => $site,
            'objectTypes' => OperationalObjectTypes::choices(),
            'parentOptions' => $this->parentOptions($user->company_id, $site->id),
            'company' => $this->companyPayload($user),
        ]);
    }

    public function update(Request $request, OperationalObject $site)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $validated = $request->validate([
            'type' => OperationalObjectTypes::validationRule(),
            'name' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:taskit_operational_objects,id',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $site->id) {
            return back()->withErrors(['parent_id' => 'A site cannot be its own parent.']);
        }

        $site->update($validated);

        return redirect()->route('sites.show', $site)->with('success', 'Site updated successfully!');
    }

    public function destroy(OperationalObject $site)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $site->update(['is_active' => false]);

        return redirect()->route('sites.index')->with('success', 'Site archived successfully!');
    }

    public function applyComplianceTemplate(Request $request, OperationalObject $site)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $validated = $request->validate([
            'project_id' => 'nullable|exists:taskit_projects,id',
        ]);

        $count = $this->complianceService->applyIndustryTemplate(
            $site,
            $validated['project_id'] ?? null,
        );

        return back()->with('success', "Applied {$count} compliance requirements.");
    }

    public function updateComplianceRequirement(Request $request, OperationalObject $site, ComplianceRequirement $requirement)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($requirement->operational_object_id !== $site->id) {
            abort(404);
        }

        $validated = $request->validate([
            'next_due_date' => 'nullable|date',
            'assignee' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'project_id' => 'nullable|exists:taskit_projects,id',
            'auto_create_tasks' => 'sometimes|boolean',
        ]);

        $requirement->update($validated);
        $requirement->refreshStatus();

        return back()->with('success', 'Compliance item updated.');
    }

    public function completeComplianceRequirement(OperationalObject $site, ComplianceRequirement $requirement)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($requirement->operational_object_id !== $site->id) {
            abort(404);
        }

        $this->complianceService->markCompleted($requirement);

        return back()->with('success', 'Compliance item marked complete. Next due date updated.');
    }

    public function downloadDocument(OperationalObject $site, OperationalDocument $document)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($document->operational_object_id !== $site->id || ! $document->canAccess($user)) {
            abort(403);
        }

        if (! Storage::disk('private')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('private')->download($document->file_path, $document->original_filename);
    }

    protected function requireCompanyUser()
    {
        $user = Auth::user();

        if (! $user->company_id) {
            abort(403, 'Access denied. Only company users can manage sites.');
        }

        return $user;
    }

    protected function authorizeObject(OperationalObject $object, $user): void
    {
        if (! $object->canAccess($user)) {
            abort(403, 'Access denied.');
        }
    }

    protected function companyPayload($user): ?array
    {
        if (! $user->company) {
            return null;
        }

        return [
            'id' => $user->company->id,
            'name' => $user->company->name,
            'code' => $user->company->code,
            'subscription_type' => $user->company->subscription_type,
            'industry' => $user->company->industry,
        ];
    }

    protected function parentOptions(int $companyId, ?int $excludeId = null): array
    {
        return OperationalObject::forCompany($companyId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn ($object) => [
                'id' => $object->id,
                'name' => $object->name,
                'type' => $object->type,
                'label' => OperationalObjectTypes::label($object->type).' — '.$object->name,
            ])
            ->all();
    }

    protected function projectOptions(int $companyId): array
    {
        return Project::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('viewing_order')
            ->get(['id', 'name', 'key'])
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'key' => $project->key,
            ])
            ->all();
    }

    protected function serializeObject(OperationalObject $object): array
    {
        $requirements = $object->complianceRequirements;

        return [
            'id' => $object->id,
            'type' => $object->type,
            'type_label' => OperationalObjectTypes::label($object->type),
            'name' => $object->name,
            'reference' => $object->reference,
            'full_address' => $object->full_address,
            'parent_name' => $object->parent?->name,
            'compliance_counts' => [
                'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
                'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
                'compliant' => $requirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
                'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
            ],
        ];
    }

    protected function serializeObjectDetail(OperationalObject $object): array
    {
        return [
            'id' => $object->id,
            'type' => $object->type,
            'type_label' => OperationalObjectTypes::label($object->type),
            'name' => $object->name,
            'reference' => $object->reference,
            'address_line_1' => $object->address_line_1,
            'address_line_2' => $object->address_line_2,
            'city' => $object->city,
            'postal_code' => $object->postal_code,
            'country' => $object->country,
            'full_address' => $object->full_address,
            'latitude' => $object->latitude,
            'longitude' => $object->longitude,
            'notes' => $object->notes,
            'parent' => $object->parent ? [
                'id' => $object->parent->id,
                'name' => $object->parent->name,
            ] : null,
            'children' => $object->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'type_label' => OperationalObjectTypes::label($child->type),
            ]),
            'compliance_requirements' => $object->complianceRequirements
                ->sortBy('label')
                ->values()
                ->map(fn ($req) => [
                    'id' => $req->id,
                    'requirement_type' => $req->requirement_type,
                    'label' => $req->label,
                    'frequency' => $req->frequency,
                    'lead_time_days' => $req->lead_time_days,
                    'next_due_date' => $req->next_due_date?->format('Y-m-d'),
                    'next_due_display' => $req->next_due_date?->format('j M Y'),
                    'last_completed_at' => $req->last_completed_at?->format('j M Y'),
                    'assignee' => $req->assignee,
                    'status' => $req->status,
                    'notes' => $req->notes,
                    'auto_create_tasks' => $req->auto_create_tasks,
                    'project_id' => $req->project_id,
                    'has_open_task' => $req->hasOpenTask(),
                ]),
            'documents' => $object->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'original_filename' => $doc->original_filename,
                'expires_at' => $doc->expires_at?->format('Y-m-d'),
                'expires_display' => $doc->expires_at?->format('j M Y'),
                'status' => $doc->status,
                'document_type' => $doc->document_type,
                'extracted_data' => $doc->extracted_data,
                'download_url' => route('sites.documents.download', [$object->id, $doc->id]),
            ]),
            'inspections' => $object->inspections->take(10)->map(fn ($insp) => [
                'id' => $insp->id,
                'label' => $insp->label,
                'status' => $insp->status,
                'completed_at' => $insp->completed_at?->format('j M Y'),
                'inspector' => $insp->inspector?->name,
                'url' => route('inspections.show', $insp),
                'pdf_url' => $insp->pdf_path ? route('inspections.pdf', $insp) : null,
            ]),
            'created_by' => $object->createdBy?->name,
            'created_at' => $object->created_at->format('M j, Y'),
        ];
    }

    protected function complianceSummary(int $companyId): array
    {
        $requirements = ComplianceRequirement::forCompany($companyId)->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();
        }

        $requirements = ComplianceRequirement::forCompany($companyId)->get();

        return [
            'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
            'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
            'compliant' => $requirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
            'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
            'total' => $requirements->count(),
        ];
    }
}
