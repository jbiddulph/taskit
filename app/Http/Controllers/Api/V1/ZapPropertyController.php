<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Company;
use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;
use App\Models\User;
use App\Services\OperationalObjectPhotoService;
use App\Support\AssetPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ZapProperty portal API — everything an agent has published with
 * "Show on ZapProperty", across every company, behind one platform key.
 *
 * Reads are cross-company by design. Task endpoints re-enter the company
 * scope of the listing's owner so ZapProperty visitors can raise viewings
 * or enquiries that land in the right agency's ZapTask.
 */
class ZapPropertyController extends PlatformController
{
    public function __construct(
        protected TaskController $tasks,
        protected OperationalObjectPhotoService $photoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->published()
            ->with(['company', 'workspace', 'photos'])
            ->orderBy('company_id')
            ->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('listing_type')) {
            $query->where('listing_type', $request->listing_type);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->company_id);
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->updated_since);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%")
                    ->orWhere('postal_code', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $paginator = $query->paginate($perPage);

        return $this->ok([
            'listings' => collect($paginator->items())
                ->map(fn (OperationalObject $asset) => $this->transform($asset))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                // Active sites that exist but are not ticked — lets the portal
                // explain "nothing here yet" without exposing them.
                'unpublished_total' => OperationalObject::query()
                    ->where('is_active', true)
                    ->where(fn (Builder $q) => $q->whereNull('show_on_zapproperty')->orWhere('show_on_zapproperty', false))
                    ->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $asset = $this->findPublished($id);
        if (! $asset) {
            return $this->fail('Listing not found.', 404);
        }

        $asset->load(['company', 'workspace', 'photos']);

        return $this->ok($this->transform($asset, true));
    }

    public function photo(int $id, int $photoId)
    {
        $asset = $this->findPublished($id);
        if (! $asset) {
            return $this->fail('Listing not found.', 404);
        }

        $photo = $asset->photos()->where('id', $photoId)->first();
        if (! $photo) {
            return $this->fail('Photo not found.', 404);
        }

        try {
            return $this->photoService->inlineResponse($photo);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 404) {
                return $this->fail('Photo not found.', 404);
            }

            throw $e;
        }
    }

    public function tasks(Request $request, int $id): JsonResponse
    {
        $asset = $this->findPublished($id);
        if (! $asset) {
            return $this->fail('Listing not found.', 404);
        }

        if (! $this->enterCompanyScope($request, $asset)) {
            return $this->fail('Listing company has no users.', 403);
        }

        $request->merge(['asset_id' => $asset->id]);

        return $this->tasks->index($request);
    }

    public function storeTask(Request $request, int $id): JsonResponse
    {
        $asset = $this->findPublished($id);
        if (! $asset) {
            return $this->fail('Listing not found.', 404);
        }

        if (! $this->enterCompanyScope($request, $asset)) {
            return $this->fail('Listing company has no users.', 403);
        }

        $request->merge([
            'asset_id' => $asset->id,
            'source' => $request->input('source', 'zapproperty'),
        ]);

        return $this->tasks->store($request);
    }

    private function published(): Builder
    {
        return OperationalObject::query()
            ->where('is_active', true)
            ->where('show_on_zapproperty', true);
    }

    private function findPublished(int $id): ?OperationalObject
    {
        return $this->published()->where('id', $id)->first();
    }

    /**
     * Tasks are always created inside a company. Act as the listing's owner
     * (or the company's first user) so TaskController's scoping and activity
     * logging behave exactly as they do for a company API key.
     */
    private function enterCompanyScope(Request $request, OperationalObject $asset): bool
    {
        $actor = $asset->createdBy;
        if (! $actor instanceof User || (int) $actor->company_id !== (int) $asset->company_id) {
            $actor = User::query()
                ->where('company_id', $asset->company_id)
                ->orderBy('created_at')
                ->first();
        }

        if (! $actor) {
            return false;
        }

        $request->attributes->set('platform_company_id', (int) $asset->company_id);
        $request->attributes->set('platform_user', $actor);
        $request->setUserResolver(fn () => $actor);

        return true;
    }

    private function transform(OperationalObject $asset, bool $detailed = false): array
    {
        $payload = AssetPayload::make(
            $asset,
            fn (OperationalObject $a, OperationalObjectPhoto $photo) => url("/api/v1/zapproperty/listings/{$a->id}/photos/{$photo->id}"),
            $detailed,
        );

        $company = $asset->relationLoaded('company') ? $asset->company : null;
        $payload['agent'] = $company instanceof Company ? [
            'id' => $company->id,
            'name' => $company->name,
            'logo_url' => $company->logo_url,
            'website' => $company->is_public ? $company->subdomain_url : null,
        ] : null;

        return $payload;
    }
}
