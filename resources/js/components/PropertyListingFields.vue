<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- Parent owns the Inertia form and this section updates it in place. */
import { computed, watch } from 'vue';
import type { ListingFormState, ListingOptions } from '@/utils/propertyListing';

const props = defineProps<{
    form: ListingFormState & { errors: Record<string, string> };
    listingOptions: ListingOptions;
    label: string;
    input: string;
    select: string;
    textarea: string;
    error: string;
}>();

const rentQualifiers = ['pcm', 'pw', 'pa'];
const saleQualifiers = ['asking', 'guide', 'offers_over', 'offers_in_excess', 'poa'];

watch(
    () => props.form.listing_type,
    (type) => {
        if (type === 'rent' && !rentQualifiers.includes(props.form.price_qualifier)) {
            props.form.price_qualifier = 'pcm';
        }
        if (type === 'sale' && !saleQualifiers.includes(props.form.price_qualifier)) {
            props.form.price_qualifier = 'asking';
        }
    },
);

const visibilityGroups = computed(() => {
    const groups: { name: string; options: ListingOptions['visibility'] }[] = [];

    for (const option of props.listingOptions.visibility ?? []) {
        const name = option.group ?? 'Details';
        const existing = groups.find((group) => group.name === name);
        if (existing) {
            existing.options.push(option);
        } else {
            groups.push({ name, options: [option] });
        }
    }

    return groups;
});

const showDeposit = computed(() => props.form.listing_type !== 'sale');
</script>

<template>
    <div class="rounded-lg border border-border p-4 space-y-4">
        <div>
            <p class="text-sm font-medium">ZapProperty listing</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Publish this property on ZapProperty, then choose which details visitors see. The fields follow a typical
                UK listing: price, key information, features, and description.
            </p>
        </div>

        <label class="flex items-start gap-3">
            <input v-model="form.show_on_zapproperty" type="checkbox" class="mt-1" />
            <span>
                <span class="font-medium block">Display on ZapProperty</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    People searching ZapProperty can see this property. Leave this off to keep the details for your team only.
                </span>
            </span>
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label :class="label">Listing</label>
                <select v-model="form.listing_type" :class="select">
                    <option value="">Select…</option>
                    <option v-for="option in listingOptions.listingTypes" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
            <div>
                <label :class="label">Price</label>
                <input v-model="form.price_amount" type="number" min="0" step="0.01" :class="input" placeholder="1750" />
                <p v-if="form.errors.price_amount" :class="error">{{ form.errors.price_amount }}</p>
            </div>
            <div>
                <label :class="label">Price shown as</label>
                <select v-model="form.price_qualifier" :class="select">
                    <option value="">Select…</option>
                    <option v-for="option in listingOptions.priceQualifiers" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
            <div>
                <label :class="label">Bathrooms</label>
                <input v-model="form.bathrooms" type="number" min="0" max="50" :class="input" />
            </div>
            <div>
                <label :class="label">Reception rooms</label>
                <input v-model="form.receptions" type="number" min="0" max="50" :class="input" />
            </div>
            <div>
                <label :class="label">Furnishing</label>
                <select v-model="form.furnishing" :class="select">
                    <option value="">Select…</option>
                    <option v-for="option in listingOptions.furnishing" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
            <div v-if="showDeposit">
                <label :class="label">Deposit</label>
                <input v-model="form.deposit_amount" type="number" min="0" step="0.01" :class="input" placeholder="2019" />
            </div>
            <div>
                <label :class="label">Available from</label>
                <input v-model="form.available_from" type="date" :class="input" />
            </div>
            <div>
                <label :class="label">Council tax band</label>
                <select v-model="form.council_tax_band" :class="select">
                    <option value="">Select…</option>
                    <option v-for="option in listingOptions.councilTax" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
            <div>
                <label :class="label">EPC rating</label>
                <select v-model="form.epc_rating" :class="select">
                    <option value="">Select…</option>
                    <option v-for="option in listingOptions.epc" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label :class="label">Broadband</label>
                <input v-model="form.broadband" type="text" :class="input" placeholder="up to 1000Mbps" />
            </div>
        </div>

        <div>
            <label :class="label">Key features</label>
            <textarea
                v-model="form.key_features_text"
                rows="5"
                :class="textarea"
                placeholder="One feature per line, for example:&#10;Private entrance&#10;Freshly decorated throughout&#10;Local shops"
            />
            <p class="text-xs text-gray-500 mt-1">One feature per line. These appear as the listing's key features.</p>
        </div>

        <div>
            <label :class="label">Description</label>
            <textarea
                v-model="form.listing_description"
                rows="6"
                :class="textarea"
                placeholder="The public description visitors read on ZapProperty."
            />
            <p v-if="form.errors.listing_description" :class="error">{{ form.errors.listing_description }}</p>
        </div>

        <div class="space-y-3">
            <div>
                <p class="text-sm font-medium">Information to show on ZapProperty</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Untick anything you want to keep off the public listing. Bedrooms, property type, and tenure use the
                    details above.
                    <span v-if="!form.show_on_zapproperty"> These choices apply once you display the property on ZapProperty.</span>
                </p>
            </div>
            <div v-for="group in visibilityGroups" :key="group.name" class="space-y-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ group.name }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label v-for="option in group.options" :key="option.value" class="flex items-center gap-2 text-sm">
                        <input v-model="form.listing_visibility[option.value]" type="checkbox" />
                        <span>{{ option.label }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
