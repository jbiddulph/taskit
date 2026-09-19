<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import { operationalSiteApi } from '@/services/operationalSiteApi';

interface SiteRef {
  id: number;
  name: string;
}

interface Requirement {
  id: number;
  label: string;
  requirement_type: string;
  type_label: string;
  status: string;
  next_due_date?: string;
  next_due_display?: string;
  last_completed_at?: string;
  has_document?: boolean;
  has_linked_task?: boolean;
  site?: SiteRef | null;
  client?: SiteRef | null;
}

interface ComplianceDocument {
  id: number;
  title: string;
  document_type?: string;
  type_label: string;
  status: string;
  expires_at?: string;
  expires_display?: string;
  original_filename: string;
  site?: SiteRef | null;
  client?: SiteRef | null;
}

interface PendingProposal {
  id: number;
  summary?: string;
  extracted_data: Record<string, unknown>;
  document_title?: string;
  site?: SiteRef | null;
  client?: SiteRef | null;
}

interface SiteOption {
  id: number;
  name: string;
  address?: string;
  client_name?: string | null;
}

interface ProjectOption {
  id: number;
  name: string;
  key: string;
}

interface CertificateType {
  type: string;
  label: string;
  short: string;
}

interface Props {
  summary: {
    overdue: number;
    due_soon: number;
    compliant: number;
    missing: number;
    total: number;
    documents: number;
    pending_extractions: number;
  };
  requirements: Requirement[];
  documents: ComplianceDocument[];
  pendingProposals: PendingProposal[];
  sites: SiteOption[];
  projects: ProjectOption[];
  certificateTypes: CertificateType[];
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();
const { btnPrimary, btnSecondary, label, select } = useFormFieldClasses();

const uploadSiteId = ref<number | ''>(props.sites[0]?.id ?? '');
const uploadProjectId = ref<number | ''>(props.projects[0]?.id ?? '');
const uploadFile = ref<File | null>(null);
const extractWithAi = ref(true);
const uploading = ref(false);
const uploadMessage = ref<string | null>(null);
const uploadError = ref<string | null>(null);
const isDraggingFile = ref(false);

const acceptedExtensions = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.webp'];
const acceptedMimeTypes = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'image/jpeg',
  'image/png',
  'image/webp',
];

function isActiveRequirement(item: Requirement): boolean {
  return Boolean(item.next_due_date || item.last_completed_at || item.has_document || item.has_linked_task);
}

const visibleRequirements = computed(() => props.requirements.filter(isActiveRequirement));

const actionNeeded = computed(() =>
  visibleRequirements.value.filter((item) => item.status === 'overdue' || item.status === 'due_soon'),
);

const selectedSite = computed(() =>
  props.sites.find((site) => site.id === uploadSiteId.value) ?? null,
);

const fileAcceptLabel = 'PDF, DOC, DOCX, JPG, PNG, or WebP up to 20MB';

function statusBadge(status: string): string {
  const map: Record<string, string> = {
    overdue: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    expired: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    due_soon: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    compliant: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    active: 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
    missing: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  };
  return map[status] ?? map.missing;
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    overdue: 'Overdue',
    expired: 'Expired',
    due_soon: 'Due soon',
    compliant: 'Compliant',
    active: 'In date',
    missing: 'Missing date',
  };
  return map[status] ?? status;
}

function openProposalReview(proposalId: number) {
  window.dispatchEvent(new CustomEvent('open-document-extraction', { detail: { proposalId } }));
}

function expiryValue(data: Record<string, unknown>): string {
  const value = data.expiry_date ?? data.expiresOn;
  return typeof value === 'string' && value ? value : '—';
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function isAcceptedFile(file: File): boolean {
  const name = file.name.toLowerCase();
  const hasExtension = acceptedExtensions.some((ext) => name.endsWith(ext));
  const hasMime = !file.type || acceptedMimeTypes.includes(file.type);
  return hasExtension && hasMime;
}

function assignUploadFile(file: File | null) {
  uploadMessage.value = null;
  uploadError.value = null;

  if (!file) {
    uploadFile.value = null;
    return;
  }

  if (!isAcceptedFile(file)) {
    uploadFile.value = null;
    uploadError.value = `That file type is not supported. Use ${fileAcceptLabel}.`;
    return;
  }

  if (file.size > 20 * 1024 * 1024) {
    uploadFile.value = null;
    uploadError.value = 'File is too large. Maximum size is 20MB.';
    return;
  }

  uploadFile.value = file;
}

function onFileChange(event: Event) {
  const inputEl = event.target as HTMLInputElement;
  assignUploadFile(inputEl.files?.[0] ?? null);
}

function onDrop(event: DragEvent) {
  event.preventDefault();
  isDraggingFile.value = false;
  const file = event.dataTransfer?.files?.[0] ?? null;
  assignUploadFile(file);

  const fileInput = document.getElementById('compliance-upload-file') as HTMLInputElement | null;
  if (fileInput && file) {
    // Keep the native input in sync when possible (browsers limit programmatic FileList writes).
    fileInput.value = '';
  }
}

function clearUploadFile() {
  uploadFile.value = null;
  const fileInput = document.getElementById('compliance-upload-file') as HTMLInputElement | null;
  if (fileInput) fileInput.value = '';
}

async function uploadDocument() {
  if (!uploadFile.value || !uploadSiteId.value) {
    uploadError.value = props.sites.length
      ? 'Choose a site and a certificate file to upload.'
      : 'Add a site first, then upload certificates here.';
    return;
  }

  uploading.value = true;
  uploadError.value = null;
  uploadMessage.value = null;

  try {
    const result = await operationalSiteApi.uploadDocument(Number(uploadSiteId.value), uploadFile.value, {
      extract: extractWithAi.value,
      project_id: uploadProjectId.value ? Number(uploadProjectId.value) : undefined,
    });

    clearUploadFile();

    uploadMessage.value = result.message
      || (result.data?.proposal_id
        ? 'Uploaded. Review the AI extraction to confirm dates and create reminder todos.'
        : 'Document uploaded.');

    if (result.data?.proposal_id) {
      openProposalReview(result.data.proposal_id);
    }

    router.reload({ only: ['summary', 'requirements', 'documents', 'pendingProposals'] });
  } catch {
    uploadError.value = `Could not upload that document. Try ${fileAcceptLabel}.`;
  } finally {
    uploading.value = false;
  }
}

function onExtractionReviewed() {
  router.reload({ only: ['summary', 'requirements', 'documents', 'pendingProposals'] });
}

onMounted(() => {
  window.addEventListener('document-extraction-reviewed', onExtractionReviewed);
});

onUnmounted(() => {
  window.removeEventListener('document-extraction-reviewed', onExtractionReviewed);
});
</script>

<template>
  <SeoHead
    title="Property Compliance Manager"
    description="Upload PDF, DOC, or DOCX certificates for any site. AI extracts dates and creates expiry reminder todos."
    image="/zap_icon.png"
  />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
              <div>
                <div class="flex items-center gap-3 mb-2">
                  <Link href="/dashboard" class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100 flex items-center gap-2">
                    <Icon name="ArrowLeft" class="w-4 h-4" />
                    <span class="text-sm font-medium">Dashboard</span>
                  </Link>
                </div>
                <h1 class="text-2xl font-semibold">Property Compliance Manager</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1 max-w-2xl">
                  Upload certificates for any site. AI extracts the dates and details, then creates board todos and reminders before renewals are due.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Link href="/clients" :class="btnSecondary">Clients</Link>
                <Link href="/sites" :class="btnSecondary">Sites</Link>
                <Link
                  href="/sites/create"
                  class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100"
                >
                  <Icon name="Plus" class="w-4 h-4" />
                  Add Site
                </Link>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-6">
              <span
                v-for="cert in certificateTypes"
                :key="cert.type"
                class="inline-flex items-center rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300"
              >
                {{ cert.short }}
              </span>
              <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                + fire safety, PAT, legionella, and more
              </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
              <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-red-700 dark:text-red-300">Overdue</div>
                <div class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ summary.overdue }}</div>
              </div>
              <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Due soon</div>
                <div class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ summary.due_soon }}</div>
              </div>
              <div class="rounded-lg border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Compliant</div>
                <div class="text-2xl font-semibold text-green-700 dark:text-green-300">{{ summary.compliant }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">Documents</div>
                <div class="text-2xl font-semibold text-gray-700 dark:text-gray-300">{{ summary.documents }}</div>
              </div>
            </div>

            <section class="mb-8">
              <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                <div>
                  <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Upload certificate</h2>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    PDF, DOC, or DOCX for any site — AI extracts the details and turns renewals into todos.
                  </p>
                </div>
                <ol class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                  <li class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">1</span> Site
                  </li>
                  <li aria-hidden="true" class="text-gray-300">→</li>
                  <li class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">2</span> Upload
                  </li>
                  <li aria-hidden="true" class="text-gray-300">→</li>
                  <li class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">3</span> AI extract
                  </li>
                  <li aria-hidden="true" class="text-gray-300">→</li>
                  <li class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">4</span> Todos
                  </li>
                </ol>
              </div>

              <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-slate-50 to-white dark:from-gray-900/60 dark:to-gray-800 p-5 md:p-6">
                <div v-if="!sites.length" class="rounded-md border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/20 p-4 text-sm">
                  <p class="font-medium text-amber-900 dark:text-amber-200">Add a site first</p>
                  <p class="text-amber-800 dark:text-amber-300 mt-1">
                    Certificates attach to a site so reminders know which address needs renewing.
                  </p>
                  <Link
                    href="/sites/create"
                    class="mt-3 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 dark:bg-white dark:text-black"
                  >
                    <Icon name="Plus" class="w-4 h-4" />
                    Add Site
                  </Link>
                </div>

                <div v-else class="space-y-5">
                  <div class="grid gap-4 md:grid-cols-2">
                    <div>
                      <label :class="label" for="compliance-upload-site">Site</label>
                      <select id="compliance-upload-site" v-model="uploadSiteId" :class="select">
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                          {{ site.name }}<template v-if="site.client_name"> — {{ site.client_name }}</template>
                        </option>
                      </select>
                      <p v-if="selectedSite?.address" class="mt-1 text-xs text-gray-500 truncate">{{ selectedSite.address }}</p>
                    </div>
                    <div v-if="projects.length">
                      <label :class="label" for="compliance-upload-project">Project for reminder todos</label>
                      <select id="compliance-upload-project" v-model="uploadProjectId" :class="select">
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                          {{ project.key }} — {{ project.name }}
                        </option>
                      </select>
                      <p class="mt-1 text-xs text-gray-500">Approved extractions create todos on this board.</p>
                    </div>
                  </div>

                  <div>
                    <label :class="label" for="compliance-upload-file">Certificate file</label>
                    <label
                      for="compliance-upload-file"
                      class="mt-1 flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors"
                      :class="isDraggingFile
                        ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/30'
                        : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 bg-white/70 dark:bg-gray-900/30'"
                      @dragenter.prevent="isDraggingFile = true"
                      @dragover.prevent="isDraggingFile = true"
                      @dragleave.prevent="isDraggingFile = false"
                      @drop="onDrop"
                    >
                      <input
                        id="compliance-upload-file"
                        type="file"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*"
                        class="sr-only"
                        @change="onFileChange"
                      />
                      <div class="rounded-full bg-gray-100 dark:bg-gray-800 p-3">
                        <Icon name="Upload" class="w-6 h-6 text-gray-700 dark:text-gray-200" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                          Drag & drop a certificate here, or click to browse
                        </p>
                        <p class="mt-1 text-xs text-gray-500">{{ fileAcceptLabel }}</p>
                      </div>
                      <div class="flex flex-wrap justify-center gap-1.5">
                        <span
                          v-for="ext in ['PDF', 'DOC', 'DOCX', 'JPG', 'PNG']"
                          :key="ext"
                          class="rounded border border-gray-200 dark:border-gray-700 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-gray-600 dark:text-gray-300"
                        >
                          {{ ext }}
                        </span>
                      </div>
                    </label>

                    <div
                      v-if="uploadFile"
                      class="mt-3 flex items-center justify-between gap-3 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2"
                    >
                      <div class="min-w-0 flex items-center gap-2">
                        <Icon name="FileText" class="w-4 h-4 shrink-0 text-gray-500" />
                        <div class="min-w-0">
                          <p class="truncate text-sm font-medium">{{ uploadFile.name }}</p>
                          <p class="text-xs text-gray-500">{{ formatFileSize(uploadFile.size) }}</p>
                        </div>
                      </div>
                      <button
                        type="button"
                        class="text-xs font-medium text-gray-500 hover:text-red-600"
                        @click="clearUploadFile"
                      >
                        Remove
                      </button>
                    </div>
                  </div>

                  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                      <input
                        v-model="extractWithAi"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span>
                        <span class="font-medium">Extract with AI and create todos</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                          Reads certificate type, address, and expiry, then opens a review so you can confirm before todos are created.
                        </span>
                      </span>
                    </label>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 disabled:opacity-60 disabled:cursor-not-allowed dark:bg-white dark:text-black dark:hover:bg-gray-100"
                      :disabled="!uploadFile || uploading || !uploadSiteId"
                      @click="uploadDocument"
                    >
                      <Icon :name="uploading ? 'Loader2' : 'Sparkles'" class="w-4 h-4" :class="{ 'animate-spin': uploading }" />
                      {{ uploading ? 'Uploading & extracting…' : (extractWithAi ? 'Upload & extract' : 'Upload document') }}
                    </button>
                  </div>
                </div>

                <p v-if="uploadMessage" class="mt-4 text-sm text-green-700 dark:text-green-300">{{ uploadMessage }}</p>
                <p v-if="uploadError" class="mt-4 text-sm text-red-600 dark:text-red-400">{{ uploadError }}</p>
              </div>
            </section>

            <section v-if="pendingProposals.length" class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">
                Waiting for review
                <span class="normal-case font-normal text-gray-400">({{ pendingProposals.length }})</span>
              </h2>
              <div class="space-y-3">
                <div
                  v-for="proposal in pendingProposals"
                  :key="proposal.id"
                  class="rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20 p-4 flex items-center justify-between gap-4"
                >
                  <div>
                    <div class="font-medium text-sm">{{ proposal.document_title || 'Uploaded document' }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                      {{ proposal.site?.name }}
                      <span v-if="expiryValue(proposal.extracted_data) !== '—'"> · Expiry {{ expiryValue(proposal.extracted_data) }}</span>
                    </div>
                    <p v-if="proposal.summary" class="text-sm mt-1">{{ proposal.summary }}</p>
                  </div>
                  <button type="button" :class="btnPrimary" @click="openProposalReview(proposal.id)">Review</button>
                </div>
              </div>
            </section>

            <section v-if="actionNeeded.length" class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Needs attention</h2>
              <div class="space-y-2">
                <div
                  v-for="item in actionNeeded"
                  :key="`action-${item.id}`"
                  class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                >
                  <div>
                    <div class="font-medium text-sm">{{ item.label }}</div>
                    <div class="text-xs text-gray-500">
                      <Link v-if="item.site" :href="`/sites/${item.site.id}`" class="hover:underline">{{ item.site.name }}</Link>
                      <span v-if="item.next_due_display"> · Due {{ item.next_due_display }}</span>
                    </div>
                  </div>
                  <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </div>
              </div>
            </section>

            <section class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Tracked certificates</h2>
              <div v-if="visibleRequirements.length" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b border-gray-200 dark:border-gray-700">
                      <th class="py-2 pr-4">Item</th>
                      <th class="py-2 pr-4">Site</th>
                      <th class="py-2 pr-4">Client</th>
                      <th class="py-2 pr-4">Due</th>
                      <th class="py-2">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in visibleRequirements" :key="item.id" class="border-b border-gray-100 dark:border-gray-800">
                      <td class="py-3 pr-4">
                        <div class="font-medium">{{ item.label }}</div>
                        <div class="text-xs text-gray-500">{{ item.type_label }}</div>
                      </td>
                      <td class="py-3 pr-4">
                        <Link v-if="item.site" :href="`/sites/${item.site.id}`" class="hover:underline">{{ item.site.name }}</Link>
                        <span v-else>—</span>
                      </td>
                      <td class="py-3 pr-4">
                        <Link v-if="item.client" :href="`/clients/${item.client.id}`" class="hover:underline">{{ item.client.name }}</Link>
                        <span v-else>—</span>
                      </td>
                      <td class="py-3 pr-4">{{ item.next_due_display || 'Not set' }}</td>
                      <td class="py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(item.status)">
                          {{ statusLabel(item.status) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="text-sm text-gray-500">
                No dated certificates yet. Upload a gas certificate, EICR, EPC, insurance schedule, or boiler service record above — or apply an industry checklist on a site.
              </p>
            </section>

            <section>
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Uploaded documents</h2>
              <div v-if="documents.length" class="space-y-2">
                <div
                  v-for="doc in documents"
                  :key="doc.id"
                  class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3"
                >
                  <div>
                    <div class="font-medium text-sm">{{ doc.title }}</div>
                    <div class="text-xs text-gray-500">
                      {{ doc.type_label }}
                      <span v-if="doc.site"> · {{ doc.site.name }}</span>
                      <span v-if="doc.client"> · {{ doc.client.name }}</span>
                      <span v-if="doc.expires_display"> · Expires {{ doc.expires_display }}</span>
                    </div>
                  </div>
                  <Link v-if="doc.site" :href="`/sites/${doc.site.id}`" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Open site</Link>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">
                No certificates uploaded yet. Use the upload area above to add gas, EICR, EPC, insurance, or boiler documents.
              </p>
            </section>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
