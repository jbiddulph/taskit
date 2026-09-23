<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ComplianceRequirement;
use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;
use App\Models\Project;
use App\Services\ComplianceRequirementService;
use App\Services\InspectionService;
use App\Services\MapboxService;
use App\Services\OperationalDocumentDeletionService;
use App\Services\OperationalLinkedTodoService;
use App\Services\OperationalObjectDeletionService;
use App\Services\OperationalObjectPhotoService;
use App\Support\ComplianceTemplates;
use App\Support\InspectionTemplates;
use App\Support\OperationalObjectTypes;
use App\Support\PropertyListing;
use App\Support\PropertyOccupancy;
use App\Support\PropertyTenure;
use App\Support\PropertyTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OperationalObjectController extends Controller
{
    public function __construct(
        protected ComplianceRequirementService $complianceService,
        protected OperationalObjectDeletionService $deletionService,
        protected OperationalDocumentDeletionService $documentDeletionService,
        protected InspectionService $inspectionService,
        protected OperationalLinkedTodoService $linkedTodoService,
        protected MapboxService $mapboxService,
        protected OperationalObjectPhotoService $photoService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $this->requireCompanyUser();
        $clientId = $request->integer('client_id') ?: null;

        $objects = OperationalObject::forCompany($user->company_id)
            ->with(['parent', 'client', 'complianceRequirements.documents', 'complianceRequirements.todos', 'photos'])
            ->withCount('children')
            ->whereNull('parent_id')
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderBy('name')
            ->get()
            ->map(fn ($object) => $this->serializeObject($object));

        return Inertia::render('Sites/Index', [
            'sites' => $objects,
            'clients' => $this->clientOptions($user->company_id),
            'selectedClientId' => $clientId,
            'complianceSummary' => $this->complianceSummary($user->company_id, $clientId),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'propertyTypeOptions' => PropertyTypes::choices(),
            'occupancyOptions' => PropertyOccupancy::choices(),
            'company' => $this->companyPayload($user),
            'hierarchyHint' => 'Company → Clients → Compliance → Sites → Projects → Tasks',
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->requireCompanyUser();

        return Inertia::render('Sites/Create', [
            'objectTypes' => OperationalObjectTypes::choices(),
            'propertyTypeOptions' => PropertyTypes::choices(),
            'tenureOptions' => PropertyTenure::choices(),
            'occupancyOptions' => PropertyOccupancy::choices(),
            'listingOptions' => PropertyListing::formOptions(),
            'parentOptions' => $this->parentOptions($user->company_id),
            'clients' => $this->clientOptions($user->company_id),
            'projects' => $this->projectOptions($user->company_id),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'defaultParentId' => $request->integer('parent_id') ?: null,
            'defaultClientId' => $request->integer('client_id') ?: null,
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
            'client_id' => 'nullable|exists:taskit_clients,id',
            'property_type' => PropertyTypes::validationRule(),
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'tenure' => PropertyTenure::validationRule(),
            'occupancy_status' => 'nullable|'.PropertyOccupancy::validationRule(),
            'apply_compliance_template' => 'sometimes|boolean',
            'default_project_id' => 'nullable|exists:taskit_projects,id',
            ...PropertyListing::validationRules(),
        ]);

        if (! empty($validated['parent_id'])) {
            $parent = OperationalObject::findOrFail($validated['parent_id']);
            if ($parent->company_id !== $user->company_id) {
                abort(403);
            }
            if (empty($validated['client_id']) && $parent->client_id) {
                $validated['client_id'] = $parent->client_id;
            }
        }

        $this->assertClientBelongsToCompany($validated['client_id'] ?? null, $user->company_id);

        $validated = $this->ensureCoordinates($validated);
        $validated['occupancy_status'] = $validated['occupancy_status'] ?? PropertyOccupancy::OCCUPIED;

        $object = OperationalObject::create([
            ...collect($validated)->except([
                'apply_compliance_template',
                'default_project_id',
                ...PropertyListing::requestKeys(),
            ])->all(),
            ...PropertyListing::attributes($validated),
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

        $site->load([
            'parent',
            'children',
            'client',
            'complianceRequirements.todos',
            'complianceRequirements.documents',
            'createdBy',
            'documents',
            'photos',
            'inspections.inspector',
        ]);

        return Inertia::render('Sites/Show', [
            'site' => $this->serializeObjectDetail($site),
            'projects' => $this->projectOptions($user->company_id),
            'hasComplianceTemplates' => ComplianceTemplates::hasTemplates($user->company?->industry),
            'inspectionTemplates' => InspectionTemplates::choices($user->company?->industry),
            'pendingDocumentProposals' => DocumentExtractionProposal::query()
                ->where('company_id', $user->company_id)
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
            'site' => array_merge($site->toArray(), [
                'children_count' => $site->children()->count(),
                'linked_todo_count' => $this->linkedTodoService->countForOperationalObjectTree($site),
            ]),
            'objectTypes' => OperationalObjectTypes::choices(),
            'propertyTypeOptions' => PropertyTypes::choices(),
            'tenureOptions' => PropertyTenure::choices(),
            'occupancyOptions' => PropertyOccupancy::choices(),
            'listingOptions' => PropertyListing::formOptions(),
            'parentOptions' => $this->parentOptions($user->company_id, $site->id),
            'clients' => $this->clientOptions($user->company_id),
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
            'client_id' => 'nullable|exists:taskit_clients,id',
            'notes' => 'nullable|string|max:2000',
            'property_type' => PropertyTypes::validationRule(),
            'bedrooms' => 'nullable|integer|min:0|max:50',
            'tenure' => PropertyTenure::validationRule(),
            'occupancy_status' => 'nullable|'.PropertyOccupancy::validationRule(),
            ...PropertyListing::validationRules(),
        ]);

        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $site->id) {
            return back()->withErrors(['parent_id' => 'A site cannot be its own parent.']);
        }

        $this->assertClientBelongsToCompany($validated['client_id'] ?? null, $user->company_id);

        $validated = $this->ensureCoordinates($validated);

        $site->update([
            ...collect($validated)->except(PropertyListing::requestKeys())->all(),
            ...PropertyListing::attributes($validated),
        ]);
        $site->syncLocationToTodos();

        return redirect()->route('sites.show', $site)->with('success', 'Site updated successfully!');
    }

    public function destroy(OperationalObject $site)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $childCount = $site->children()->count();
        $linkedTodoCount = $this->linkedTodoService->countForOperationalObjectTree($site);
        $this->linkedTodoService->deleteForOperationalObjectTree($site);
        $this->deletionService->deleteWithDescendants($site);

        $message = $childCount > 0
            ? "Site and {$childCount} child site(s) deleted."
            : 'Site deleted successfully.';

        if ($linkedTodoCount > 0) {
            $message .= " {$linkedTodoCount} linked Kanban task(s) removed.";
        }

        return redirect()->route('sites.index')->with('success', $message);
    }

    public function destroyComplianceRequirement(OperationalObject $site, ComplianceRequirement $requirement)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($requirement->operational_object_id !== $site->id) {
            abort(404);
        }

        $linkedTodoCount = $this->linkedTodoService->countForComplianceRequirement($requirement);
        $this->linkedTodoService->deleteForComplianceRequirement($requirement);
        $requirement->delete();

        $message = 'Compliance item deleted.';
        if ($linkedTodoCount > 0) {
            $message .= " {$linkedTodoCount} linked Kanban task(s) removed.";
        }

        return back()->with('success', $message);
    }

    public function destroyDocument(OperationalObject $site, OperationalDocument $document)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($document->operational_object_id !== $site->id || ! $document->canAccess($user)) {
            abort(403);
        }

        $this->documentDeletionService->delete($document);

        return back()->with('success', 'Document deleted.');
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
            'issued_at' => 'nullable|date',
            'assignee' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'provider' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:taskit_projects,id',
            'auto_create_tasks' => 'sometimes|boolean',
        ]);

        $requirement->update($validated);
        $requirement->refreshStatus();

        if (array_key_exists('assignee', $validated)) {
            $requirement->todos()->where('status', '!=', 'done')->update([
                'assignee' => $validated['assignee'] ?: null,
            ]);
        }

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

    public function storePhotos(Request $request, OperationalObject $site)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        $validated = $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|file|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'caption' => 'nullable|string|max:255',
            'as_cover' => 'sometimes|boolean',
        ]);

        $uploaded = 0;
        $asCover = $request->boolean('as_cover');

        try {
            foreach ($request->file('photos', []) as $index => $file) {
                $this->photoService->add(
                    $site,
                    $file,
                    $user,
                    $index === 0 ? ($validated['caption'] ?? null) : null,
                    $asCover && $index === 0,
                );
                $uploaded++;
            }
        } catch (\RuntimeException $e) {
            return back()->withErrors(['photos' => $e->getMessage()]);
        }

        $label = $uploaded === 1 ? 'photo' : 'photos';

        return back()->with('success', "{$uploaded} {$label} added.");
    }

    public function showPhoto(OperationalObject $site, OperationalObjectPhoto $photo)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ((int) $photo->operational_object_id !== (int) $site->id) {
            abort(404);
        }

        if (! Storage::disk('private')->exists($photo->file_path)) {
            abort(404, 'Photo file missing.');
        }

        $mime = $photo->mime_type ?: 'image/jpeg';
        $filename = str_replace(['"', "\r", "\n"], '', $photo->original_filename ?: 'photo.jpg');

        // Stream inline so <img> / lightbox can display (not download).
        return Storage::disk('private')->response($photo->file_path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function updatePhoto(Request $request, OperationalObject $site, OperationalObjectPhoto $photo)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($photo->operational_object_id !== $site->id) {
            abort(404);
        }

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
            'is_cover' => 'sometimes|boolean',
        ]);

        if (array_key_exists('caption', $validated)) {
            $this->photoService->updateCaption($photo, $validated['caption']);
        }

        if ($request->boolean('is_cover')) {
            $this->photoService->setCover($site, $photo);
        }

        return back()->with('success', 'Photo updated.');
    }

    public function destroyPhoto(OperationalObject $site, OperationalObjectPhoto $photo)
    {
        $user = Auth::user();
        $this->authorizeObject($site, $user);

        if ($photo->operational_object_id !== $site->id) {
            abort(404);
        }

        $this->photoService->delete($photo);

        return back()->with('success', 'Photo removed.');
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

    protected function clientOptions(int $companyId): array
    {
        return Client::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
            ])
            ->all();
    }

    protected function assertClientBelongsToCompany(?int $clientId, int $companyId): void
    {
        if (! $clientId) {
            return;
        }

        $client = Client::findOrFail($clientId);
        if ($client->company_id !== $companyId) {
            abort(403, 'Access denied.');
        }
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

    /**
     * Geocode address fields when lat/lng are missing so site selection can pin todos on the map.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function ensureCoordinates(array $validated): array
    {
        $hasCoords = isset($validated['latitude'], $validated['longitude'])
            && $validated['latitude'] !== null
            && $validated['latitude'] !== ''
            && $validated['longitude'] !== null
            && $validated['longitude'] !== '';

        if ($hasCoords || ! $this->mapboxService->isConfigured()) {
            return $validated;
        }

        $query = collect([
            $validated['address_line_1'] ?? null,
            $validated['address_line_2'] ?? null,
            $validated['city'] ?? null,
            $validated['postal_code'] ?? null,
            $validated['country'] ?? null,
        ])->filter()->implode(', ');

        if ($query === '' && ! empty($validated['name'])) {
            $query = (string) $validated['name'];
        }

        if (strlen($query) < 2) {
            return $validated;
        }

        $match = $this->mapboxService->geocode($query)[0] ?? null;
        if (! $match) {
            return $validated;
        }

        $validated['latitude'] = $match['latitude'];
        $validated['longitude'] = $match['longitude'];

        return $validated;
    }

    protected function serializeObject(OperationalObject $object): array
    {
        $trackedRequirements = $object->complianceRequirements
            ->filter(fn ($req) => $req->isActiveOnSitePage());

        return [
            'id' => $object->id,
            'type' => $object->type,
            'type_label' => OperationalObjectTypes::label($object->type),
            'property_type' => $object->property_type,
            'property_type_label' => PropertyTypes::label($object->property_type),
            'bedrooms' => $object->bedrooms,
            'tenure' => $object->tenure,
            'tenure_label' => PropertyTenure::label($object->tenure),
            'occupancy_status' => $object->occupancy_status,
            'occupancy_label' => PropertyOccupancy::label($object->occupancy_status),
            'show_on_zapproperty' => (bool) $object->show_on_zapproperty,
            'listing_type_label' => PropertyListing::listingTypeLabel($object->listing_type),
            'price_label' => PropertyListing::formatPrice($object->price_amount, $object->price_qualifier),
            'name' => $object->name,
            'reference' => $object->reference,
            'full_address' => $object->full_address,
            'parent_name' => $object->parent?->name,
            'client' => $object->client ? [
                'id' => $object->client->id,
                'name' => $object->client->name,
            ] : null,
            'children_count' => $object->children_count ?? $object->children()->count(),
            'linked_todo_count' => $this->linkedTodoService->countForOperationalObjectTree($object),
            'photo_count' => $object->relationLoaded('photos')
                ? $object->photos->count()
                : $object->photos()->count(),
            'cover_photo_url' => $this->coverPhotoUrl($object),
            'compliance_counts' => [
                'overdue' => $trackedRequirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
                'due_soon' => $trackedRequirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
                'compliant' => $trackedRequirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
                'missing' => $trackedRequirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
            ],
        ];
    }

    protected function serializeListing(OperationalObject $object): array
    {
        $visibility = PropertyListing::normalizeVisibility($object->listing_visibility);
        $available = $object->available_from;

        return [
            'show_on_zapproperty' => (bool) $object->show_on_zapproperty,
            'listing_type' => $object->listing_type,
            'listing_type_label' => PropertyListing::listingTypeLabel($object->listing_type),
            'price_amount' => $object->price_amount,
            'price_qualifier' => $object->price_qualifier,
            'price_qualifier_label' => PropertyListing::priceQualifierLabel($object->price_qualifier),
            'price_label' => PropertyListing::formatPrice($object->price_amount, $object->price_qualifier),
            'bathrooms' => $object->bathrooms,
            'receptions' => $object->receptions,
            'furnishing' => $object->furnishing,
            'furnishing_label' => PropertyListing::furnishingLabel($object->furnishing),
            'deposit_amount' => $object->deposit_amount,
            'deposit_label' => PropertyListing::formatMoney($object->deposit_amount),
            'available_from' => $available?->format('Y-m-d'),
            'available_from_label' => $available ? 'from '.$available->format('j F Y') : null,
            'council_tax_band' => $object->council_tax_band,
            'council_tax_label' => PropertyListing::councilTaxLabel($object->council_tax_band),
            'epc_rating' => $object->epc_rating,
            'epc_label' => $object->epc_rating ? 'EPC '.$object->epc_rating : null,
            'broadband' => $object->broadband,
            'key_features' => $object->key_features ?? [],
            'listing_description' => $object->listing_description,
            'listing_visibility' => $visibility,
        ];
    }

    protected function coverPhotoUrl(OperationalObject $object): ?string
    {
        $cover = $object->relationLoaded('photos')
            ? ($object->photos->firstWhere('is_cover', true) ?? $object->photos->first())
            : $object->photos()->where('is_cover', true)->first() ?? $object->photos()->orderBy('sort_order')->first();

        if (! $cover) {
            return null;
        }

        // Relative path so <img> works even when APP_URL differs from the browser host.
        return "/sites/{$object->id}/photos/{$cover->id}";
    }

    protected function serializePhotos(OperationalObject $object): array
    {
        return $object->photos->map(fn (OperationalObjectPhoto $photo) => [
            'id' => $photo->id,
            'caption' => $photo->caption,
            'is_cover' => (bool) $photo->is_cover,
            'sort_order' => $photo->sort_order,
            'original_filename' => $photo->original_filename,
            'mime_type' => $photo->mime_type,
            'file_size' => $photo->file_size,
            'url' => "/sites/{$object->id}/photos/{$photo->id}",
        ])->values()->all();
    }

    protected function serializeObjectDetail(OperationalObject $object): array
    {
        return [
            'id' => $object->id,
            'linked_todo_count' => $this->linkedTodoService->countForOperationalObjectTree($object),
            'type' => $object->type,
            'type_label' => OperationalObjectTypes::label($object->type),
            'property_type' => $object->property_type,
            'property_type_label' => PropertyTypes::label($object->property_type),
            'bedrooms' => $object->bedrooms,
            'tenure' => $object->tenure,
            'tenure_label' => PropertyTenure::label($object->tenure),
            'occupancy_status' => $object->occupancy_status,
            'occupancy_label' => PropertyOccupancy::label($object->occupancy_status),
            'listing' => $this->serializeListing($object),
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
            'client' => $object->client ? [
                'id' => $object->client->id,
                'name' => $object->client->name,
            ] : null,
            'client_id' => $object->client_id,
            'children' => $object->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'type_label' => OperationalObjectTypes::label($child->type),
            ]),
            'unscheduled_compliance_count' => $object->complianceRequirements
                ->filter(fn ($req) => ! $req->isActiveOnSitePage())
                ->count(),
            'compliance_requirements' => $object->complianceRequirements
                ->filter(fn ($req) => $req->isActiveOnSitePage())
                ->sortBy('label')
                ->values()
                ->map(fn ($req) => $this->serializeSiteRequirement($req)),
            'unscheduled_compliance_requirements' => $object->complianceRequirements
                ->filter(fn ($req) => ! $req->isActiveOnSitePage())
                ->sortBy('label')
                ->values()
                ->map(fn ($req) => $this->serializeSiteRequirement($req)),
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
            'photos' => $this->serializePhotos($object),
            'cover_photo_url' => $this->coverPhotoUrl($object),
            'inspections' => $object->inspections->take(10)->map(fn ($insp) => [
                'id' => $insp->id,
                'label' => $insp->label,
                'status' => $insp->status,
                'completed_at' => $insp->completed_at?->format('j M Y'),
                'inspector' => $insp->inspector?->name,
                'url' => route('inspections.show', $insp),
                'pdf_url' => $insp->pdf_path ? route('inspections.pdf', $insp) : null,
                'linked_todo_count' => $this->linkedTodoService->countForInspection($insp),
            ]),
            'created_by' => $object->createdBy?->name,
            'created_at' => $object->created_at->format('M j, Y'),
        ];
    }

    protected function serializeSiteRequirement(ComplianceRequirement $req): array
    {
        $openTodo = $req->latestOpenTodo();

        return [
            'id' => $req->id,
            'requirement_type' => $req->requirement_type,
            'label' => $req->label,
            'frequency' => $req->frequency,
            'lead_time_days' => $req->lead_time_days,
            'next_due_date' => $req->next_due_date?->format('Y-m-d'),
            'next_due_display' => $req->next_due_date?->format('j M Y'),
            'last_completed_at' => $req->last_completed_at?->format('j M Y'),
            'issued_at' => $req->issued_at?->format('Y-m-d'),
            'issued_display' => $req->issued_at?->format('j M Y'),
            'assignee' => $openTodo?->assignee ?: $req->assignee,
            'status' => $req->status,
            'notes' => $req->notes,
            'provider' => $req->provider,
            'auto_create_tasks' => $req->auto_create_tasks,
            'project_id' => $req->project_id,
            'has_open_task' => $openTodo !== null,
            'open_todo' => $openTodo ? [
                'id' => $openTodo->id,
                'url' => $openTodo->dashboardUrl(),
                'assignee' => $openTodo->assignee,
                'project_id' => $openTodo->project_id,
            ] : null,
            'linked_todo_count' => $this->linkedTodoService->countForComplianceRequirement($req),
        ];
    }

    protected function complianceSummary(int $companyId, ?int $clientId = null): array
    {
        $requirementsQuery = fn () => ComplianceRequirement::forCompany($companyId)
            ->when($clientId, function ($query) use ($clientId) {
                $query->whereHas('operationalObject', fn ($q) => $q->where('client_id', $clientId));
            });

        foreach ($requirementsQuery()->with(['documents', 'todos'])->get() as $requirement) {
            $requirement->refreshStatus();
        }

        $requirements = $requirementsQuery()
            ->with(['documents', 'todos'])
            ->get()
            ->filter(fn ($requirement) => $requirement->isActiveOnSitePage());

        return [
            'overdue' => $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count(),
            'due_soon' => $requirements->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count(),
            'compliant' => $requirements->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count(),
            'missing' => $requirements->where('status', ComplianceRequirement::STATUS_MISSING)->count(),
            'total' => $requirements->count(),
        ];
    }
}
