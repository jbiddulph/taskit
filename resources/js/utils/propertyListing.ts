export interface ListingOption {
    value: string;
    label: string;
    group?: string;
}

export interface ListingOptions {
    listingTypes: ListingOption[];
    priceQualifiers: ListingOption[];
    furnishing: ListingOption[];
    councilTax: ListingOption[];
    epc: ListingOption[];
    visibility: ListingOption[];
}

export interface ListingFormState {
    show_on_zapproperty: boolean;
    listing_type: string;
    price_amount: string | number;
    price_qualifier: string;
    bathrooms: string | number;
    receptions: string | number;
    furnishing: string;
    deposit_amount: string | number;
    available_from: string;
    council_tax_band: string;
    epc_rating: string;
    broadband: string;
    key_features_text: string;
    listing_description: string;
    listing_visibility: Record<string, boolean>;
}

export function defaultListingVisibility(options?: ListingOption[]): Record<string, boolean> {
    return Object.fromEntries((options ?? []).map((option) => [option.value, true]));
}

export function emptyListingForm(options?: ListingOptions): ListingFormState {
    return {
        show_on_zapproperty: false,
        listing_type: '',
        price_amount: '',
        price_qualifier: '',
        bathrooms: '',
        receptions: '',
        furnishing: '',
        deposit_amount: '',
        available_from: '',
        council_tax_band: '',
        epc_rating: '',
        broadband: '',
        key_features_text: '',
        listing_description: '',
        listing_visibility: defaultListingVisibility(options?.visibility),
    };
}

function nullableNumber(value: string | number): number | null {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const numeric = Number(value);

    return Number.isFinite(numeric) ? numeric : null;
}

export function listingPayload(data: ListingFormState) {
    return {
        show_on_zapproperty: data.show_on_zapproperty,
        listing_type: data.listing_type || null,
        price_amount: nullableNumber(data.price_amount),
        price_qualifier: data.price_qualifier || null,
        bathrooms: nullableNumber(data.bathrooms),
        receptions: nullableNumber(data.receptions),
        furnishing: data.furnishing || null,
        deposit_amount: nullableNumber(data.deposit_amount),
        available_from: data.available_from || null,
        council_tax_band: data.council_tax_band || null,
        epc_rating: data.epc_rating || null,
        broadband: data.broadband.trim() || null,
        key_features: data.key_features_text
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean),
        listing_description: data.listing_description.trim() || null,
        listing_visibility: data.listing_visibility,
    };
}

export function moneyInput(value: string | number | null | undefined): string | number {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const numeric = Number(value);

    return Number.isFinite(numeric) ? numeric : '';
}
