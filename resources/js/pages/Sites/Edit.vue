<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import PropertyListingFields from '@/components/PropertyListingFields.vue';
import { defaultListingVisibility, listingPayload, moneyInput, type ListingOptions } from '@/utils/propertyListing';
import { linkedTodoWarning } from '@/utils/linkedTodoWarning';

interface Option {
  value: string;
  label: string;
}

interface ParentOption {
  id: number;
  label: string;
}

interface Site {
  id: number;
  type: string;
  name: string;
  reference?: string;
  parent_id?: number;
  client_id?: number | null;
  address_line_1?: string;
  address_line_2?: string;
  city?: string;
  postal_code?: string;
  country?: string;
  latitude?: number;
  longitude?: number;
  notes?: string;
  property_type?: string | null;
  bedrooms?: number | null;
  tenure?: string | null;
  occupancy_status?: string | null;
  show_on_zapproperty?: boolean;
  listing_type?: string | null;
  price_amount?: string | number | null;
  price_qualifier?: string | null;
  bathrooms?: number | null;
  receptions?: number | null;
  furnishing?: string | null;
  deposit_amount?: string | number | null;
  available_from?: string | null;
  council_tax_band?: string | null;
  epc_rating?: string | null;
  broadband?: string | null;
  key_features?: string[] | null;
  listing_description?: string | null;
  listing_visibility?: Record<string, boolean> | null;
  children_count?: number;
  linked_todo_count?: number;
}

interface Props {
  site: Site;
  objectTypes: Option[];
  propertyTypeOptions?: Option[];
  tenureOptions?: Option[];
  occupancyOptions?: Option[];
  listingOptions: ListingOptions;
  parentOptions: ParentOption[];
  clients?: { id: number; name: string }[];
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();
const { label, input, select, textarea, error, btnPrimary, btnSecondary, btnDanger } = useFormFieldClasses();

const form = useForm({
  type: props.site.type,
  name: props.site.name,
  reference: props.site.reference ?? '',
  parent_id: props.site.parent_id ?? '',
  client_id: props.site.client_id ?? '',
  address_line_1: props.site.address_line_1 ?? '',
  address_line_2: props.site.address_line_2 ?? '',
  city: props.site.city ?? '',
  postal_code: props.site.postal_code ?? '',
  country: props.site.country ?? '',
  latitude: props.site.latitude ?? '',
  longitude: props.site.longitude ?? '',
  notes: props.site.notes ?? '',
  property_type: props.site.property_type ?? '',
  bedrooms: props.site.bedrooms ?? '',
  tenure: props.site.tenure ?? '',
  occupancy_status: props.site.occupancy_status ?? 'occupied',
  show_on_zapproperty: props.site.show_on_zapproperty ?? false,
  listing_type: props.site.listing_type ?? '',
  price_amount: moneyInput(props.site.price_amount),
  price_qualifier: props.site.price_qualifier ?? '',
  bathrooms: props.site.bathrooms ?? '',
  receptions: props.site.receptions ?? '',
  furnishing: props.site.furnishing ?? '',
  deposit_amount: moneyInput(props.site.deposit_amount),
  available_from: (props.site.available_from ?? '').slice(0, 10),
  council_tax_band: props.site.council_tax_band ?? '',
  epc_rating: props.site.epc_rating ?? '',
  broadband: props.site.broadband ?? '',
  key_features_text: (props.site.key_features ?? []).join('\n'),
  listing_description: props.site.listing_description ?? '',
  listing_visibility: {
    ...defaultListingVisibility(props.listingOptions?.visibility),
    ...(props.site.listing_visibility ?? {}),
  },
});

const submit = () => {
  form.transform((data) => {
    const { key_features_text, ...rest } = data;
    void key_features_text;

    return {
      ...rest,
      parent_id: data.parent_id || null,
      client_id: data.client_id || null,
      property_type: data.property_type || null,
      tenure: data.tenure || null,
      bedrooms: data.bedrooms === '' ? null : Number(data.bedrooms),
      ...listingPayload(data),
      latitude: data.latitude === '' ? null : Number(data.latitude),
      longitude: data.longitude === '' ? null : Number(data.longitude),
    };
  }).put(`/sites/${props.site.id}`);
};

const deleteSite = () => {
  const childNote =
    props.site.children_count && props.site.children_count > 0
      ? ` This will also permanently delete ${props.site.children_count} child site(s) and all related documents, compliance items, and inspections.`
      : ' This will permanently delete all related documents, compliance items, and inspections.';

  if (!confirm(`Delete "${props.site.name}"?${childNote}${linkedTodoWarning(props.site.linked_todo_count)} This cannot be undone.`)) {
    return;
  }

  router.delete(`/sites/${props.site.id}`, {
    onSuccess: () => window.dispatchEvent(new CustomEvent('todoChanged')),
  });
};
</script>

<template>
  <SeoHead :title="`Edit ${site.name}`" description="Edit site details in ZapTask." image="/zap_icon.png" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="mb-6">
              <Link :href="`/sites/${site.id}`" class="text-sm flex items-center gap-2 mb-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                <Icon name="ArrowLeft" class="w-4 h-4" />
                Back to site
              </Link>
              <h1 class="text-2xl font-semibold">Edit site</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label :class="label">Type</label>
                <select v-model="form.type" :class="select">
                  <option v-for="option in objectTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div>
                <label :class="label">Name *</label>
                <input v-model="form.name" type="text" :class="input" required />
              </div>

              <div>
                <label :class="label">Reference</label>
                <input v-model="form.reference" type="text" :class="input" />
              </div>

              <div v-if="clients?.length">
                <label :class="label">Client</label>
                <select v-model="form.client_id" :class="select">
                  <option value="">No client</option>
                  <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
              </div>

              <div
                v-if="['property', 'building', 'unit', 'site'].includes(form.type)"
                class="rounded-lg border border-border p-4 space-y-4"
              >
                <p class="text-sm font-medium">Property details</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label :class="label">Property type</label>
                    <select v-model="form.property_type" :class="select">
                      <option value="">Select…</option>
                      <option v-for="option in propertyTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </div>
                  <div>
                    <label :class="label">Bedrooms</label>
                    <input v-model="form.bedrooms" type="number" min="0" max="50" :class="input" />
                  </div>
                  <div>
                    <label :class="label">Tenure</label>
                    <select v-model="form.tenure" :class="select">
                      <option value="">Select…</option>
                      <option v-for="option in tenureOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </div>
                  <div>
                    <label :class="label">Occupancy</label>
                    <select v-model="form.occupancy_status" :class="select">
                      <option v-for="option in occupancyOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                  </div>
                </div>
              </div>

              <PropertyListingFields
                v-if="['property', 'building', 'unit', 'site'].includes(form.type)"
                :form="form"
                :listing-options="listingOptions"
                :label="label"
                :input="input"
                :select="select"
                :textarea="textarea"
                :error="error"
              />

              <div v-if="parentOptions.length">
                <label :class="label">Parent site</label>
                <select v-model="form.parent_id" :class="select">
                  <option value="">None</option>
                  <option v-for="parent in parentOptions" :key="parent.id" :value="parent.id">{{ parent.label }}</option>
                </select>
              </div>

              <div>
                <label :class="label">Address line 1</label>
                <input v-model="form.address_line_1" type="text" :class="input" />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label :class="label">City</label>
                  <input v-model="form.city" type="text" :class="input" />
                </div>
                <div>
                  <label :class="label">Postcode</label>
                  <input v-model="form.postal_code" type="text" :class="input" />
                </div>
              </div>

              <div>
                <label :class="label">Notes</label>
                <textarea v-model="form.notes" rows="3" :class="textarea" />
              </div>

              <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="form.processing" :class="btnPrimary">Save changes</button>
                <Link :href="`/sites/${site.id}`" :class="btnSecondary">Cancel</Link>
              </div>
            </form>

            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
              <h2 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Danger zone</h2>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Deleting this site removes all documents, compliance items, and inspections
                <span v-if="site.children_count">, including {{ site.children_count }} child site(s)</span>.
                <span v-if="site.linked_todo_count"> {{ site.linked_todo_count }} linked Kanban task{{ site.linked_todo_count === 1 ? '' : 's' }} will also be removed.</span>
              </p>
              <button type="button" :class="btnDanger" @click="deleteSite">Delete site</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
