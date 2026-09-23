<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;
use App\Services\OperationalObjectPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetPhotoController extends PlatformController
{
    public function __construct(
        protected OperationalObjectPhotoService $photoService,
    ) {}

    public function index(Request $request, int $assetId): JsonResponse
    {
        $asset = $this->findAsset($request, $assetId);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $asset->load('photos');

        return $this->ok([
            'photos' => $asset->photos->map(fn (OperationalObjectPhoto $photo) => $this->transform($asset, $photo)),
        ]);
    }

    public function store(Request $request, int $assetId): JsonResponse
    {
        $asset = $this->findAsset($request, $assetId);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required_without:photos|file|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'photos' => 'required_without:photo|array|min:1|max:10',
            'photos.*' => 'file|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'caption' => 'nullable|string|max:255',
            'as_cover' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        $user = $this->platformUser($request);
        $files = $request->file('photos') ?? array_filter([$request->file('photo')]);
        $created = [];
        $asCover = $request->boolean('as_cover');

        try {
            foreach ($files as $index => $file) {
                $created[] = $this->photoService->add(
                    $asset,
                    $file,
                    $user,
                    $index === 0 ? $request->input('caption') : null,
                    $asCover && $index === 0,
                );
            }
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok([
            'photos' => collect($created)->map(fn (OperationalObjectPhoto $photo) => $this->transform($asset, $photo)),
        ], 'Photo(s) uploaded.', 201);
    }

    public function show(Request $request, int $assetId, int $photoId)
    {
        $asset = $this->findAsset($request, $assetId);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
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

    public function update(Request $request, int $assetId, int $photoId): JsonResponse
    {
        $asset = $this->findAsset($request, $assetId);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $photo = $asset->photos()->where('id', $photoId)->first();
        if (! $photo) {
            return $this->fail('Photo not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            'is_cover' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed.', 422, $validator->errors());
        }

        if ($request->has('caption')) {
            $this->photoService->updateCaption($photo, $request->input('caption'));
        }

        if ($request->boolean('is_cover')) {
            $this->photoService->setCover($asset, $photo->fresh());
        }

        return $this->ok($this->transform($asset, $photo->fresh()), 'Photo updated.');
    }

    public function destroy(Request $request, int $assetId, int $photoId): JsonResponse
    {
        $asset = $this->findAsset($request, $assetId);
        if (! $asset) {
            return $this->fail('Asset not found.', 404);
        }

        $photo = $asset->photos()->where('id', $photoId)->first();
        if (! $photo) {
            return $this->fail('Photo not found.', 404);
        }

        $this->photoService->delete($photo);

        return $this->ok(null, 'Photo deleted.');
    }

    private function findAsset(Request $request, int $id): ?OperationalObject
    {
        return OperationalObject::query()
            ->where('company_id', $this->companyId($request))
            ->where('id', $id)
            ->first();
    }

    private function transform(OperationalObject $asset, OperationalObjectPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'asset_id' => $asset->id,
            'caption' => $photo->caption,
            'is_cover' => (bool) $photo->is_cover,
            'sort_order' => $photo->sort_order,
            'original_filename' => $photo->original_filename,
            'mime_type' => $photo->mime_type,
            'file_size' => $photo->file_size,
            'url' => url("/api/v1/assets/{$asset->id}/photos/{$photo->id}"),
            'created_at' => optional($photo->created_at)?->toIso8601String(),
        ];
    }
}
