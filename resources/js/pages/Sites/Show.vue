<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import OperationsTips from '@/components/OperationsTips.vue';
import SeoHead from '@/components/SeoHead.vue';
import { operationalSiteApi } from '@/services/operationalSiteApi';
import { onMounted, onUnmounted, ref } from 'vue';

interface ComplianceRequirement {
  id: number;
  label: string;
  status: string;
  next_due_date?: string;
  next_due_display?: string;
  last_completed_at?: string;
  assignee?: string;
  notes?: string;
  has_open_task: boolean;
}

interface SiteDocument {
  id: number;
  title: string;
  original_filename: string;
  expires_at?: string;
  expires_display?: string;
  status: string;
  document_type?: string;
  download_url: string;
}

interface PendingProposal {
  id: number;
  extracted_data: Record<string, string | null>;
  summary?: string;
  document_title?: string;
}

interface InspectionTemplate {
  key: string;
  label: string;
}

interface SiteInspection {
  id: number;
  label: string;
  status: string;
  completed_at?: string;
  inspector?: string;
  url: string;
  pdf_url?: string | null;
}

interface Site {
  id: number;
  type_label: string;
  name: string;
  reference?: string;
  full_address: string;
  notes?: string;
  parent?: { id: number; name: string };
  children: Array<{ id: number; name: string; type_label: string }>;
  compliance_requirements: ComplianceRequirement[];
  documents: SiteDocument[];
  inspections: SiteInspection[];
}

interface ProjectOption {
  id: number;
  name: string;
  key: string;
}

interface Props {
  site: Site;
  projects: ProjectOption[];
  hasComplianceTemplates: boolean;
  inspectionTemplates?: InspectionTemplate[];
  pendingDocumentProposals?: PendingProposal[];
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();

const editingId = ref<number | null>(null);
const uploading = ref(false);
const uploadFile = ref<File | null>(null);
const manualExpiry = ref('');
const extractWithAi = ref(true);

const editForm = useForm({
  next_due_date: '',
  assignee: '',
  notes: '',
  project_id: '' as string | number,
});

function statusBadge(status: string): string {
  const map: Record<string, string> = {
    overdue: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    due_soon: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    compliant: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    missing: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  };
  return map[status] ?? map.missing;
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    overdue: 'Overdue',
    due_soon: 'Due soon',
    compliant: 'Compliant',
    missing: 'Missing date',
  };
  return map[status] ?? status;
}

function startEdit(requirement: ComplianceRequirement) {
  editingId.value = requirement.id;
  editForm.next_due_date = requirement.next_due_date ?? '';
  editForm.assignee = requirement.assignee ?? '';
  editForm.notes = requirement.notes ?? '';
}

function saveRequirement(requirementId: number) {
  editForm.patch(`/sites/${props.site.id}/compliance/${requirementId}`, {
    onSuccess: () => {
      editingId.value = null;
    },
  });
}

function completeRequirement(requirementId: number) {
  router.post(`/sites/${props.site.id}/compliance/${requirementId}/complete`);
}

function applyTemplate() {
  router.post(`/sites/${props.site.id}/compliance-template`);
}

function openProposalReview(proposalId: number) {
  window.dispatchEvent(new CustomEvent('open-document-extraction', { detail: { proposalId } }));
}

async function uploadDocument() {
  if (!uploadFile.value) return;
  uploading.value = true;
  try {
    const result = await operationalSiteApi.uploadDocument(props.site.id, uploadFile.value, {
      expires_at: manualExpiry.value || undefined,
      extract: extractWithAi.value,
    });
    uploadFile.value = null;
    manualExpiry.value = '';
    if (result.data?.proposal_id) {
      openProposalReview(result.data.proposal_id);
    }
    router.reload({ only: ['site', 'pendingDocumentProposals'] });
  } catch {
    if ((window as any).$notify) {
      (window as any).$notify({ type: 'error', title: 'Upload failed', message: 'Could not upload document.' });
    }
  } finally {
    uploading.value = false;
  }
}

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement;
  uploadFile.value = input.files?.[0] ?? null;
}

const onExtractionReviewed = () => {
  router.reload({ only: ['site', 'pendingDocumentProposals'] });
};

onMounted(() => {
  window.addEventListener('document-extraction-reviewed', onExtractionReviewed);
});

onUnmounted(() => {
  window.removeEventListener('document-extraction-reviewed', onExtractionReviewed);
});
</script>

<template>
  <SeoHead :title="site.name" description="Site details and compliance tracking in ZapTask." image="/zap_icon.png" />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex justify-between items-start gap-4 mb-8">
              <div>
                <Link href="/sites" class="text-sm flex items-center gap-2 mb-2">
                  <Icon name="ArrowLeft" class="w-4 h-4" />
                  All sites
                </Link>
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ site.type_label }}</div>
                <h1 class="text-2xl font-semibold">{{ site.name }}</h1>
                <p v-if="site.full_address" class="text-gray-600 dark:text-gray-400 mt-1">{{ site.full_address }}</p>
                <p v-if="site.parent" class="text-sm text-gray-500 mt-1">Inside {{ site.parent.name }}</p>
              </div>
              <Link :href="`/sites/${site.id}/edit`" class="rounded-md px-3 py-2 text-sm border border-gray-300 dark:border-gray-600">Edit</Link>
            </div>

            <div v-if="site.children.length" class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Child sites</h2>
              <div class="flex flex-wrap gap-2">
                <Link
                  v-for="child in site.children"
                  :key="child.id"
                  :href="`/sites/${child.id}`"
                  class="rounded-full border border-gray-200 dark:border-gray-700 px-3 py-1 text-sm hover:border-gray-400"
                >
                  {{ child.type_label }}: {{ child.name }}
                </Link>
              </div>
            </div>

            <OperationsTips context="sites_show" class="mb-8" />

            <div class="mt-2 mb-8">
              <h2 class="text-lg font-semibold mb-4">Documents</h2>

              <div
                v-for="proposal in pendingDocumentProposals ?? []"
                :key="proposal.id"
                class="mb-3 rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20 p-4 flex items-center justify-between gap-4"
              >
                <div>
                  <div class="font-medium text-sm">AI extraction ready</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">{{ proposal.document_title }}</div>
                </div>
                <button
                  type="button"
                  class="text-sm rounded-md px-3 py-1.5 bg-black text-white dark:bg-white dark:text-black"
                  @click="openProposalReview(proposal.id)"
                >
                  Review
                </button>
              </div>

              <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-4">
                <div class="grid gap-3 md:grid-cols-2">
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Upload certificate or document</label>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="text-sm w-full" @change="onFileChange" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Expiry date (optional)</label>
                    <input v-model="manualExpiry" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" />
                  </div>
                  <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                      <input v-model="extractWithAi" type="checkbox" />
                      Extract details with AI
                    </label>
                  </div>
                </div>
                <button
                  type="button"
                  class="mt-3 text-sm rounded-md px-4 py-2 bg-black text-white dark:bg-white dark:text-black disabled:opacity-50"
                  :disabled="!uploadFile || uploading"
                  @click="uploadDocument"
                >
                  {{ uploading ? 'Uploading…' : 'Upload' }}
                </button>
              </div>

              <div v-if="site.documents?.length" class="space-y-2">
                <div
                  v-for="doc in site.documents"
                  :key="doc.id"
                  class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                >
                  <div>
                    <div class="font-medium text-sm">{{ doc.title }}</div>
                    <div class="text-xs text-gray-500">
                      {{ doc.original_filename }}
                      <span v-if="doc.expires_display"> · Expires {{ doc.expires_display }}</span>
                    </div>
                  </div>
                  <a :href="doc.download_url" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Download</a>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No documents uploaded yet.</p>
            </div>

            <div class="mb-8">
              <h2 class="text-lg font-semibold mb-4">Inspections</h2>
              <div v-if="inspectionTemplates?.length" class="flex flex-wrap gap-2 mb-4">
                <Link
                  v-for="template in inspectionTemplates"
                  :key="template.key"
                  :href="`/sites/${site.id}/inspections/create?template=${template.key}`"
                  class="text-sm rounded-md px-3 py-1.5 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-900"
                >
                  Start: {{ template.label }}
                </Link>
              </div>
              <div v-if="site.inspections?.length" class="space-y-2">
                <div
                  v-for="insp in site.inspections"
                  :key="insp.id"
                  class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                >
                  <div>
                    <Link :href="insp.url" class="font-medium text-sm hover:underline">{{ insp.label }}</Link>
                    <div class="text-xs text-gray-500">
                      {{ insp.status === 'completed' ? `Completed ${insp.completed_at}` : 'Draft' }}
                      <span v-if="insp.inspector"> · {{ insp.inspector }}</span>
                    </div>
                  </div>
                  <a v-if="insp.pdf_url" :href="insp.pdf_url" class="text-sm text-blue-600 dark:text-blue-400">PDF</a>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No inspections yet. Start one using the buttons above.</p>
            </div>

            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold">Compliance</h2>
              <button
                v-if="hasComplianceTemplates"
                type="button"
                class="text-sm rounded-md px-3 py-1.5 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-900"
                @click="applyTemplate"
              >
                Apply industry template
              </button>
            </div>

            <div v-if="site.compliance_requirements.length" class="space-y-3">
              <div
                v-for="requirement in site.compliance_requirements"
                :key="requirement.id"
                class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
              >
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <div class="font-medium">{{ requirement.label }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      <span v-if="requirement.next_due_display">Next due: {{ requirement.next_due_display }}</span>
                      <span v-else>No due date set</span>
                      <span v-if="requirement.last_completed_at"> · Last completed {{ requirement.last_completed_at }}</span>
                    </div>
                    <div v-if="requirement.assignee" class="text-sm text-gray-500 mt-1">Assigned: {{ requirement.assignee }}</div>
                    <div v-if="requirement.has_open_task" class="text-xs text-blue-600 dark:text-blue-400 mt-1">Open task on board</div>
                  </div>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(requirement.status)">
                    {{ statusLabel(requirement.status) }}
                  </span>
                </div>

                <div v-if="editingId === requirement.id" class="mt-4 grid gap-3 md:grid-cols-2">
                  <div>
                    <label class="block text-xs font-medium mb-1">Due date</label>
                    <input v-model="editForm.next_due_date" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium mb-1">Assignee</label>
                    <input v-model="editForm.assignee" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" />
                  </div>
                  <div class="md:col-span-2 flex gap-2">
                    <button type="button" class="text-sm rounded-md px-3 py-1.5 bg-black text-white dark:bg-white dark:text-black" @click="saveRequirement(requirement.id)">Save</button>
                    <button type="button" class="text-sm rounded-md px-3 py-1.5 border" @click="editingId = null">Cancel</button>
                  </div>
                </div>

                <div v-else class="mt-3 flex flex-wrap gap-2">
                  <button type="button" class="text-xs rounded-md px-2.5 py-1 border" @click="startEdit(requirement)">Set due date</button>
                  <button
                    v-if="requirement.next_due_date"
                    type="button"
                    class="text-xs rounded-md px-2.5 py-1 border border-green-300 text-green-700 dark:text-green-300"
                    @click="completeRequirement(requirement.id)"
                  >
                    Mark complete
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
              <p class="text-gray-600 dark:text-gray-400 mb-4">No compliance items yet.</p>
              <button
                v-if="hasComplianceTemplates"
                type="button"
                class="rounded-md px-4 py-2 text-sm bg-black text-white dark:bg-white dark:text-black"
                @click="applyTemplate"
              >
                Apply industry template
              </button>
            </div>

            <p v-if="site.notes" class="mt-8 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-6">{{ site.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
