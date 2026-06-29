<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import SeoHead from '@/components/SeoHead.vue';

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
  address_line_1?: string;
  address_line_2?: string;
  city?: string;
  postal_code?: string;
  country?: string;
  latitude?: number;
  longitude?: number;
  notes?: string;
}

interface Props {
  site: Site;
  objectTypes: Option[];
  parentOptions: ParentOption[];
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();

const form = useForm({
  type: props.site.type,
  name: props.site.name,
  reference: props.site.reference ?? '',
  parent_id: props.site.parent_id ?? '',
  address_line_1: props.site.address_line_1 ?? '',
  address_line_2: props.site.address_line_2 ?? '',
  city: props.site.city ?? '',
  postal_code: props.site.postal_code ?? '',
  country: props.site.country ?? '',
  latitude: props.site.latitude ?? '',
  longitude: props.site.longitude ?? '',
  notes: props.site.notes ?? '',
});

const submit = () => {
  form.transform((data) => ({
    ...data,
    parent_id: data.parent_id || null,
    latitude: data.latitude === '' ? null : Number(data.latitude),
    longitude: data.longitude === '' ? null : Number(data.longitude),
  })).put(`/sites/${props.site.id}`);
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
              <Link :href="`/sites/${site.id}`" class="text-sm flex items-center gap-2 mb-2">
                <Icon name="ArrowLeft" class="w-4 h-4" />
                Back to site
              </Link>
              <h1 class="text-2xl font-semibold">Edit site</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
              <div>
                <label class="block text-sm font-medium mb-1">Type</label>
                <select v-model="form.type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                  <option v-for="option in objectTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" required />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Reference</label>
                <input v-model="form.reference" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" />
              </div>

              <div v-if="parentOptions.length">
                <label class="block text-sm font-medium mb-1">Parent site</label>
                <select v-model="form.parent_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                  <option value="">None</option>
                  <option v-for="parent in parentOptions" :key="parent.id" :value="parent.id">{{ parent.label }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Address line 1</label>
                <input v-model="form.address_line_1" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">City</label>
                  <input v-model="form.city" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Postcode</label>
                  <input v-model="form.postal_code" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea v-model="form.notes" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900" />
              </div>

              <div class="flex gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-md px-4 py-2 text-sm font-medium bg-black text-white dark:bg-white dark:text-black disabled:opacity-50">
                  Save changes
                </button>
                <Link :href="`/sites/${site.id}`" class="rounded-md px-4 py-2 text-sm font-medium border">Cancel</Link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
