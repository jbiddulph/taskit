<?php

namespace App\Support;

use App\Models\OperationalObject;
use App\Models\OperationalObjectPhoto;

/**
 * Builds the platform Asset payload. Shared by the company-scoped
 * /api/v1/assets endpoints and the cross-company ZapProperty portal, so both
 * return the same shape and only differ in which photo route they link to.
 */
class AssetPayload
{
    /**
     * @param  callable(OperationalObject, OperationalObjectPhoto): string  $photoUrl
     */
    public static function make(OperationalObject $asset, callable $photoUrl, bool $detailed = false): array
    {
        $cover = $asset->relationLoaded('photos')
            ? ($asset->photos->firstWhere('is_cover', true) ?? $asset->photos->first())
            : null;

        $payload = [
            'id' => $asset->id,
            'company_id' => $asset->company_id,
            'client_id' => $asset->client_id,
            'workspace_id' => $asset->workspace_id,
            'type' => $asset->type,
            'name' => $asset->name,
            'reference' => $asset->reference,
            'status' => $asset->status ?? ($asset->is_active ? 'active' : 'inactive'),
            'property' => [
                'property_type' => $asset->property_type,
                'bedrooms' => $asset->bedrooms,
                'bathrooms' => $asset->bathrooms,
                'receptions' => $asset->receptions,
                'tenure' => $asset->tenure,
                'occupancy_status' => $asset->occupancy_status,
                'furnishing' => $asset->furnishing,
                'listing' => [
                    'show_on_zapproperty' => (bool) $asset->show_on_zapproperty,
                    'listing_type' => $asset->listing_type,
                    'price_amount' => $asset->price_amount,
                    'price_qualifier' => $asset->price_qualifier,
                    'price_label' => PropertyListing::formatPrice($asset->price_amount, $asset->price_qualifier),
                    'deposit_amount' => $asset->deposit_amount,
                    'available_from' => $asset->available_from?->toDateString(),
                    'council_tax_band' => $asset->council_tax_band,
                    'epc_rating' => $asset->epc_rating,
                    'broadband' => $asset->broadband,
                    'key_features' => $asset->key_features ?? [],
                    'description' => $asset->listing_description,
                    'visibility' => PropertyListing::normalizeVisibility($asset->listing_visibility),
                ],
            ],
            'photo_count' => $asset->relationLoaded('photos')
                ? $asset->photos->count()
                : $asset->photos()->count(),
            'cover_photo_url' => $cover ? $photoUrl($asset, $cover) : null,
            'metadata' => $asset->metadata,
            'address' => [
                'line_1' => $asset->address_line_1,
                'line_2' => $asset->address_line_2,
                'city' => $asset->city,
                'postal_code' => $asset->postal_code,
                'country' => $asset->country,
            ],
            'created_at' => optional($asset->created_at)?->toIso8601String(),
            'updated_at' => optional($asset->updated_at)?->toIso8601String(),
        ];

        if ($detailed && $asset->relationLoaded('photos')) {
            $payload['photos'] = $asset->photos->map(fn (OperationalObjectPhoto $photo) => [
                'id' => $photo->id,
                'caption' => $photo->caption,
                'is_cover' => (bool) $photo->is_cover,
                'sort_order' => $photo->sort_order,
                'original_filename' => $photo->original_filename,
                'mime_type' => $photo->mime_type,
                'file_size' => $photo->file_size,
                'url' => $photoUrl($asset, $photo),
            ])->values()->all();
        }

        return $payload;
    }
}
