<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
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
  address_line_1?: string;
  address_line_2?: string;
  city?: string;
  postal_code?: string;
  country?: string;
  latitude?: number;
  longitude?: number;
  notes?: string;
  children_count?: number;
  linked_todo_count?: number;
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
const { label, input, select, textarea, btnPrimary, btnSecondary, btnDanger } = useFormFieldClasses();

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
