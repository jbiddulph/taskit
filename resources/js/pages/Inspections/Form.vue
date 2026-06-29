<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import OperationsTips from '@/components/OperationsTips.vue';
import SeoHead from '@/components/SeoHead.vue';
import axios from 'axios';
import { computed, ref } from 'vue';

interface TemplateItem {
  key: string;
  label: string;
  type: 'pass_fail_na' | 'text' | 'photos';
}

interface InspectionPhoto {
  id: number;
  item_key?: string;
  caption?: string;
  original_filename: string;
  url: string;
}

interface FollowUpTask {
  id: number;
  title: string;
  status: string;
  due_date?: string;
  project_key?: string;
}

interface ProjectOption {
  id: number;
  name: string;
  key: string;
}

interface Props {
  inspection: {
    id: number;
    label: string;
    status: string;
    responses: Record<string, string>;
    summary?: string;
    completed_at?: string;
    pdf_url?: string | null;
    site: { id: number; name: string; full_address: string };
    photos: InspectionPhoto[];
    follow_up_tasks?: FollowUpTask[];
  };
  templateItems: TemplateItem[];
  readOnly: boolean;
  projects?: ProjectOption[];
  company?: { id: number; name: string; code: string; subscription_type: string } | null;
}

const props = defineProps<Props>();

const responses = ref<Record<string, string>>({ ...props.inspection.responses });
const summary = ref(props.inspection.summary ?? '');
const photos = ref<InspectionPhoto[]>([...props.inspection.photos]);
const selectedProjectId = ref<number | null>(props.projects?.[0]?.id ?? null);
const saving = ref(false);
const completing = ref(false);
const uploading = ref(false);

const checklistItems = computed(() => props.templateItems.filter((i) => i.type !== 'photos'));
const photoItem = computed(() => props.templateItems.find((i) => i.type === 'photos'));

const failedCount = computed(() =>
  checklistItems.value.filter(
    (item) => item.type === 'pass_fail_na' && responses.value[item.key] === 'fail',
  ).length,
);

const notify = (type: 'success' | 'error', title: string, message: string) => {
  if ((window as any).$notify) {
    (window as any).$notify({ type, title, message });
  }
};

async function saveDraft() {
  saving.value = true;
  try {
    await axios.patch(`/inspections/${props.inspection.id}`, {
      responses: responses.value,
      summary: summary.value,
    });
    notify('success', 'Saved', 'Inspection draft saved.');
  } catch {
    notify('error', 'Error', 'Could not save inspection.');
  } finally {
    saving.value = false;
  }
}

async function uploadPhoto(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file || props.readOnly) return;

  uploading.value = true;
  const formData = new FormData();
  formData.append('photo', file);
  if (photoItem.value) formData.append('item_key', photoItem.value.key);

  try {
    const { data } = await axios.post(`/inspections/${props.inspection.id}/photos`, formData);
    photos.value.push(data.data);
    input.value = '';
    notify('success', 'Photo added', 'Photo attached to inspection.');
  } catch {
    notify('error', 'Upload failed', 'Could not upload photo.');
  } finally {
    uploading.value = false;
  }
}

async function completeInspection() {
  completing.value = true;
  try {
    await axios.patch(`/inspections/${props.inspection.id}`, {
      responses: responses.value,
      summary: summary.value,
    });
    const { data } = await axios.post(`/inspections/${props.inspection.id}/complete`, {
      project_id: selectedProjectId.value,
    });
    notify('success', 'Complete', data.message || 'PDF report generated.');
    if (data.data?.redirect) {
      router.visit(data.data.redirect);
    }
  } catch {
    notify('error', 'Error', 'Could not complete inspection.');
  } finally {
    completing.value = false;
  }
}
</script>

<template>
  <SeoHead :title="inspection.label" description="Site inspection checklist in ZapTask." image="/zap_icon.png" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <OperationsTips :context="readOnly ? 'inspection_show' : 'inspection_create'" :default-open="!readOnly" />

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <Link :href="`/sites/${inspection.site.id}`" class="text-sm flex items-center gap-2 mb-4">
              <Icon name="ArrowLeft" class="w-4 h-4" />
              Back to {{ inspection.site.name }}
            </Link>

            <h1 class="text-2xl font-semibold">{{ inspection.label }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ inspection.site.full_address }}</p>
            <p v-if="inspection.completed_at" class="text-sm text-green-600 dark:text-green-400 mt-2">
              Completed {{ inspection.completed_at }}
            </p>

            <div
              v-if="!readOnly && failedCount > 0"
              class="mt-4 rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/20 p-3 text-sm text-amber-900 dark:text-amber-200"
            >
              {{ failedCount }} failed item(s) — completing will create high-priority follow-up tasks on your board (due in 3 days). Linked compliance will not be marked complete until failures are resolved.
            </div>

            <div class="mt-6 space-y-4">
              <div
                v-for="item in checklistItems"
                :key="item.key"
                class="rounded-lg border p-4"
                :class="responses[item.key] === 'fail'
                  ? 'border-red-300 dark:border-red-900 bg-red-50/50 dark:bg-red-950/20'
                  : 'border-gray-200 dark:border-gray-700'"
              >
                <div class="font-medium text-sm mb-2">{{ item.label }}</div>

                <div v-if="item.type === 'pass_fail_na'" class="flex flex-wrap gap-2">
                  <label
                    v-for="option in ['pass', 'fail', 'na']"
                    :key="option"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs cursor-pointer capitalize"
                    :class="responses[item.key] === option
                      ? option === 'fail'
                        ? 'border-red-500 bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300'
                        : 'border-blue-500 bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300'
                      : 'border-gray-300 dark:border-gray-600'"
                  >
                    <input
                      v-model="responses[item.key]"
                      type="radio"
                      :value="option"
                      :name="item.key"
                      :disabled="readOnly"
                      class="sr-only"
                    />
                    {{ option === 'na' ? 'N/A' : option }}
                  </label>
                </div>

                <textarea
                  v-else-if="item.type === 'text'"
                  v-model="responses[item.key]"
                  :disabled="readOnly"
                  rows="3"
                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"
                  :placeholder="item.label"
                />
              </div>

              <div>
                <label class="block text-sm font-medium mb-1">Summary notes</label>
                <textarea
                  v-model="summary"
                  :disabled="readOnly"
                  rows="3"
                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"
                  placeholder="Overall findings, follow-up actions..."
                />
              </div>

              <div v-if="photoItem" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="font-medium text-sm mb-2">{{ photoItem.label }}</div>
                <div v-if="!readOnly" class="mb-3">
                  <input type="file" accept="image/*" capture="environment" :disabled="uploading" @change="uploadPhoto" />
                </div>
                <div v-if="photos.length" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                  <a
                    v-for="photo in photos"
                    :key="photo.id"
                    :href="photo.url"
                    target="_blank"
                    class="block rounded border border-gray-200 dark:border-gray-700 overflow-hidden hover:opacity-90"
                  >
                    <img :src="photo.url" :alt="photo.caption || photo.original_filename" class="w-full h-24 object-cover" />
                    <p class="text-xs p-1 truncate text-gray-500">{{ photo.original_filename }}</p>
                  </a>
                </div>
                <p v-else class="text-xs text-gray-500">No photos yet.</p>
              </div>
            </div>

            <div
              v-if="inspection.follow_up_tasks?.length"
              class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
            >
              <h2 class="text-sm font-semibold mb-3">Follow-up tasks created</h2>
              <ul class="space-y-2">
                <li
                  v-for="task in inspection.follow_up_tasks"
                  :key="task.id"
                  class="text-sm flex justify-between gap-3"
                >
                  <span>{{ task.title }}</span>
                  <span class="text-gray-500 shrink-0">{{ task.project_key }} · {{ task.status }}</span>
                </li>
              </ul>
              <Link href="/dashboard" class="inline-block mt-3 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                View on board →
              </Link>
            </div>

            <div v-if="!readOnly && projects?.length" class="mt-6">
              <label class="block text-sm font-medium mb-1">Create follow-up tasks on board</label>
              <select
                v-model="selectedProjectId"
                class="w-full max-w-md rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"
              >
                <option v-for="project in projects" :key="project.id" :value="project.id">
                  {{ project.key }} — {{ project.name }}
                </option>
              </select>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
              <template v-if="!readOnly">
                <button
                  type="button"
                  class="rounded-md px-4 py-2 text-sm border"
                  :disabled="saving"
                  @click="saveDraft"
                >
                  {{ saving ? 'Saving…' : 'Save draft' }}
                </button>
                <button
                  type="button"
                  class="rounded-md px-4 py-2 text-sm bg-black text-white dark:bg-white dark:text-black disabled:opacity-50"
                  :disabled="completing"
                  @click="completeInspection"
                >
                  {{ completing ? 'Generating PDF…' : 'Complete & download PDF' }}
                </button>
              </template>
              <a
                v-if="inspection.pdf_url"
                :href="inspection.pdf_url"
                class="rounded-md px-4 py-2 text-sm border border-green-300 text-green-700 dark:text-green-300 inline-flex items-center gap-2"
              >
                <Icon name="FileDown" class="w-4 h-4" />
                Download PDF report
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
