<?php

namespace App\Support;

class PropertyListing
{
    /**
     * Public listing sections an agent can show or hide on ZapProperty.
     * Mirrors a typical UK estate-agent page: headline facts, key information, then copy.
     *
     * @return list<string>
     */
    public static function visibilityKeys(): array
    {
        return array_column(self::visibilityOptions(), 'value');
    }

    /**
     * @return list<array{value: string, label: string, group: string}>
     */
    public static function visibilityOptions(): array
    {
        return [
            ['value' => 'price', 'label' => 'Price', 'group' => 'Headline'],
            ['value' => 'property_type', 'label' => 'Property type', 'group' => 'Headline'],
            ['value' => 'bedrooms', 'label' => 'Bedrooms', 'group' => 'Headline'],
            ['value' => 'bathrooms', 'label' => 'Bathrooms', 'group' => 'Headline'],
            ['value' => 'receptions', 'label' => 'Reception rooms', 'group' => 'Headline'],
            ['value' => 'tenure', 'label' => 'Tenure', 'group' => 'Headline'],
            ['value' => 'furnishing', 'label' => 'Furnishing', 'group' => 'Headline'],
            ['value' => 'deposit', 'label' => 'Deposit', 'group' => 'Key information'],
            ['value' => 'available_from', 'label' => 'Available from', 'group' => 'Key information'],
            ['value' => 'council_tax', 'label' => 'Council tax band', 'group' => 'Key information'],
            ['value' => 'epc', 'label' => 'EPC rating', 'group' => 'Key information'],
            ['value' => 'broadband', 'label' => 'Broadband', 'group' => 'Key information'],
            ['value' => 'features', 'label' => 'Key features', 'group' => 'Listing copy'],
            ['value' => 'description', 'label' => 'Description', 'group' => 'Listing copy'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function listingTypeChoices(): array
    {
        return [
            ['value' => 'rent', 'label' => 'To rent'],
            ['value' => 'sale', 'label' => 'For sale'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function priceQualifierChoices(): array
    {
        return [
            ['value' => 'pcm', 'label' => 'Per calendar month (pcm)'],
            ['value' => 'pw', 'label' => 'Per week (pw)'],
            ['value' => 'pa', 'label' => 'Per year (pa)'],
            ['value' => 'asking', 'label' => 'Asking price'],
            ['value' => 'guide', 'label' => 'Guide price'],
            ['value' => 'offers_over', 'label' => 'Offers over'],
            ['value' => 'offers_in_excess', 'label' => 'Offers in excess of'],
            ['value' => 'poa', 'label' => 'Price on application'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function furnishingChoices(): array
    {
        return [
            ['value' => 'furnished', 'label' => 'Furnished'],
            ['value' => 'part_furnished', 'label' => 'Part furnished'],
            ['value' => 'unfurnished', 'label' => 'Unfurnished'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function councilTaxChoices(): array
    {
        $bands = [];
        foreach (range('A', 'H') as $band) {
            $bands[] = ['value' => $band, 'label' => 'Band '.$band];
        }

        $bands[] = ['value' => 'not_available', 'label' => 'Not available'];
        $bands[] = ['value' => 'exempt', 'label' => 'Exempt'];

        return $bands;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function epcChoices(): array
    {
        $ratings = [];
        foreach (range('A', 'G') as $rating) {
            $ratings[] = ['value' => $rating, 'label' => $rating];
        }

        return $ratings;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formOptions(): array
    {
        return [
            'listingTypes' => self::listingTypeChoices(),
            'priceQualifiers' => self::priceQualifierChoices(),
            'furnishing' => self::furnishingChoices(),
            'councilTax' => self::councilTaxChoices(),
            'epc' => self::epcChoices(),
            'visibility' => self::visibilityOptions(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultVisibility(): array
    {
        return array_fill_keys(self::visibilityKeys(), true);
    }

    /**
     * @param  array<string, mixed>|null  $visibility
     * @return array<string, bool>
     */
    public static function normalizeVisibility(?array $visibility): array
    {
        $normalized = self::defaultVisibility();

        if ($visibility === null) {
            return $normalized;
        }

        foreach (self::visibilityKeys() as $key) {
            if (array_key_exists($key, $visibility)) {
                $normalized[$key] = filter_var($visibility[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        return [
            'show_on_zapproperty' => 'sometimes|boolean',
            'listing_type' => self::inRule(self::listingTypeChoices()),
            'price_amount' => 'nullable|numeric|min:0|max:999999999',
            'price_qualifier' => self::inRule(self::priceQualifierChoices()),
            'bathrooms' => 'nullable|integer|min:0|max:50',
            'receptions' => 'nullable|integer|min:0|max:50',
            'furnishing' => self::inRule(self::furnishingChoices()),
            'deposit_amount' => 'nullable|numeric|min:0|max:999999999',
            'available_from' => 'nullable|date',
            'council_tax_band' => self::inRule(self::councilTaxChoices()),
            'epc_rating' => self::inRule(self::epcChoices()),
            'broadband' => 'nullable|string|max:120',
            'key_features' => 'nullable|array|max:30',
            'key_features.*' => 'string|max:160',
            'listing_description' => 'nullable|string|max:8000',
            'listing_visibility' => 'nullable|array',
            'listing_visibility.*' => 'boolean',
        ];
    }

    /**
     * Input names that should be normalised before they are stored.
     *
     * @return list<string>
     */
    public static function requestKeys(): array
    {
        return [
            'show_on_zapproperty',
            'listing_type',
            'price_amount',
            'price_qualifier',
            'bathrooms',
            'receptions',
            'furnishing',
            'deposit_amount',
            'available_from',
            'council_tax_band',
            'epc_rating',
            'broadband',
            'key_features',
            'listing_description',
            'listing_visibility',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function attributes(array $validated): array
    {
        $features = collect($validated['key_features'] ?? [])
            ->map(fn ($feature) => trim((string) $feature))
            ->filter()
            ->values()
            ->all();

        return [
            'show_on_zapproperty' => (bool) ($validated['show_on_zapproperty'] ?? false),
            'listing_type' => self::blankToNull($validated['listing_type'] ?? null),
            'price_amount' => self::blankToNull($validated['price_amount'] ?? null),
            'price_qualifier' => self::blankToNull($validated['price_qualifier'] ?? null),
            'bathrooms' => self::blankToNull($validated['bathrooms'] ?? null),
            'receptions' => self::blankToNull($validated['receptions'] ?? null),
            'furnishing' => self::blankToNull($validated['furnishing'] ?? null),
            'deposit_amount' => self::blankToNull($validated['deposit_amount'] ?? null),
            'available_from' => self::blankToNull($validated['available_from'] ?? null),
            'council_tax_band' => self::blankToNull($validated['council_tax_band'] ?? null),
            'epc_rating' => self::blankToNull($validated['epc_rating'] ?? null),
            'broadband' => self::blankToNull($validated['broadband'] ?? null),
            'key_features' => $features === [] ? null : $features,
            'listing_description' => self::blankToNull($validated['listing_description'] ?? null),
            'listing_visibility' => self::normalizeVisibility(
                isset($validated['listing_visibility']) && is_array($validated['listing_visibility'])
                    ? $validated['listing_visibility']
                    : null
            ),
        ];
    }

    /**
     * Only the listing keys present on a partial API update.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function partialAttributes(array $input): array
    {
        $full = self::attributes($input);

        return array_intersect_key($full, $input);
    }

    public static function listingTypeLabel(?string $type): ?string
    {
        return self::choiceLabel(self::listingTypeChoices(), $type);
    }

    public static function priceQualifierLabel(?string $qualifier): ?string
    {
        return self::choiceLabel(self::priceQualifierChoices(), $qualifier);
    }

    public static function furnishingLabel(?string $furnishing): ?string
    {
        return self::choiceLabel(self::furnishingChoices(), $furnishing);
    }

    public static function councilTaxLabel(?string $band): ?string
    {
        return self::choiceLabel(self::councilTaxChoices(), $band);
    }

    public static function formatPrice(mixed $amount, ?string $qualifier): ?string
    {
        if ($qualifier === 'poa') {
            return 'POA';
        }

        if ($amount === null || $amount === '') {
            return null;
        }

        $numeric = (float) $amount;
        $formatted = '£'.number_format($numeric, fmod($numeric, 1.0) === 0.0 ? 0 : 2);

        return match ($qualifier) {
            'pcm' => $formatted.' pcm',
            'pw' => $formatted.' pw',
            'pa' => $formatted.' pa',
            'guide' => 'Guide price '.$formatted,
            'offers_over' => 'Offers over '.$formatted,
            'offers_in_excess' => 'Offers in excess of '.$formatted,
            default => $formatted,
        };
    }

    public static function formatMoney(mixed $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $numeric = (float) $amount;

        return '£'.number_format($numeric, fmod($numeric, 1.0) === 0.0 ? 0 : 2);
    }

    /**
     * @param  list<array{value: string, label: string}>  $choices
     */
    private static function inRule(array $choices): string
    {
        return 'nullable|in:'.implode(',', array_column($choices, 'value'));
    }

    /**
     * @param  list<array{value: string, label: string}>  $choices
     */
    private static function choiceLabel(array $choices, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        foreach ($choices as $choice) {
            if ($choice['value'] === $value) {
                return $choice['label'];
            }
        }

        return $value;
    }

    private static function blankToNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }
}
