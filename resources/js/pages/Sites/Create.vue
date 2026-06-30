<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import OperationsTips from '@/components/OperationsTips.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';

interface Option {
  value: string;
  label: string;
}

interface ParentOption {
  id: number;
  label: string;
}

interface ProjectOption {
  id: number;
  name: string;
  key: string;
}

interface Props {
  objectTypes: Option[];
  parentOptions: ParentOption[];
  projects: ProjectOption[];
  hasComplianceTemplates: boolean;
  defaultParentId?: number | null;
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();
const { label, input, select, textarea, error, btnPrimary, btnSecondary } = useFormFieldClasses();

const form = useForm({
  type: 'property',
  name: '',
  reference: '',
  parent_id: (props.defaultParentId ?? '') as string | number,
  address_line_1: '',
  address_line_2: '',
  city: '',
  postal_code: '',
  country: 'United Kingdom',
  latitude: '' as string | number,
  longitude: '' as string | number,
  notes: '',
  apply_compliance_template: true,
  default_project_id: '' as string | number,
});

const submit = () => {
  form.transform((data) => ({
    ...data,
    parent_id: data.parent_id || null,
    default_project_id: data.default_project_id || null,
    latitude: data.latitude === '' ? null : Number(data.latitude),
    longitude: data.longitude === '' ? null : Number(data.longitude),
  })).post('/sites');
};
</script>

<template>
  <SeoHead title="Add site" description="Add a property, site, or asset to ZapTask." image="/zap_icon.png" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="mb-6">
              <Link href="/sites" class="text-sm flex items-center gap-2 mb-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                <Icon name="ArrowLeft" class="w-4 h-4" />
                Back to sites
              </Link>
              <h1 class="text-2xl font-semibold">Add site</h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">Add a property, building, or asset to track compliance.</p>
            </div>

            <OperationsTips context="sites_create" class="mb-6" :default-open="true" />

            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label :class="label">Type</label>
                <select v-model="form.type" :class="select">
                  <option v-for="option in objectTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>

              <div>
                <label :class="label">Name *</label>
                <input v-model="form.name" type="text" :class="input" placeholder="24 High Street" required />
                <p v-if="form.errors.name" :class="error">{{ form.errors.name }}</p>
              </div>

              <div>
                <label :class="label">Reference (optional)</label>
                <input v-model="form.reference" type="text" :class="input" placeholder="Unit 4B" />
              </div>

              <div v-if="parentOptions.length">
                <label :class="label">Parent site (optional)</label>
                <select v-model="form.parent_id" :class="select">
                  <option value="">None</option>
                  <option v-for="parent in parentOptions" :key="parent.id" :value="parent.id">{{ parent.label }}</option>
                </select>
              </div>

              <div class="space-y-4">
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
              </div>

              <div v-if="hasComplianceTemplates" class="rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20 p-4 space-y-3">
                <label class="flex items-start gap-3">
                  <input v-model="form.apply_compliance_template" type="checkbox" class="mt-1" />
                  <span>
                    <span class="font-medium block">Apply industry compliance template</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Adds gas safety, inspections, and other items for your industry.</span>
                  </span>
                </label>
                <div v-if="form.apply_compliance_template && projects.length">
                  <label :class="label">Create reminder tasks on board</label>
                  <select v-model="form.default_project_id" :class="select">
                    <option value="">First available board</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.key }} — {{ project.name }}</option>
                  </select>
                </div>
              </div>

              <div>
                <label :class="label">Notes</label>
                <textarea v-model="form.notes" rows="3" :class="textarea" />
              </div>

              <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="form.processing" :class="btnPrimary">Create site</button>
                <Link href="/sites" :class="btnSecondary">Cancel</Link>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
