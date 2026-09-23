<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import OperationsTips from '@/components/OperationsTips.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import { operationalSiteApi } from '@/services/operationalSiteApi';
import { linkedTodoWarning } from '@/utils/linkedTodoWarning';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface OpenTodo {
  id: number;
  url: string;
  assignee?: string | null;
  project_id?: number | null;
}

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
  open_todo?: OpenTodo | null;
  linked_todo_count?: number;
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

interface SitePhoto {
  id: number;
  caption?: string | null;
  is_cover: boolean;
  sort_order: number;
  original_filename: string;
  mime_type: string;
  file_size: number;
  url: string;
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
  linked_todo_count?: number;
}

interface SiteListing {
  show_on_zapproperty: boolean;
  listing_type_label?: string | null;
  price_label?: string | null;
  bathrooms?: number | null;
  receptions?: number | null;
  furnishing_label?: string | null;
  deposit_label?: string | null;
  available_from_label?: string | null;
  council_tax_label?: string | null;
  epc_label?: string | null;
  broadband?: string | null;
  key_features?: string[];
  listing_description?: string | null;
  listing_visibility?: Record<string, boolean>;
}

interface Site {
  id: number;
  type_label: string;
  property_type_label?: string;
  bedrooms?: number | null;
  tenure_label?: string;
  occupancy_label?: string;
  listing?: SiteListing;
  name: string;
  reference?: string;
  full_address: string;
  notes?: string;
  linked_todo_count?: number;
  parent?: { id: number; name: string };
  client?: { id: number; name: string } | null;
  children: Array<{ id: number; name: string; type_label: string }>;
  unscheduled_compliance_count?: number;
  compliance_requirements: ComplianceRequirement[];
  unscheduled_compliance_requirements?: ComplianceRequirement[];
  documents: SiteDocument[];
  photos?: SitePhoto[];
  cover_photo_url?: string | null;
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
const { label, input, select, card, sectionTitle, btnPrimary, btnSecondary, btnDangerSm } = useFormFieldClasses();

const editingId = ref<number | null>(null);
const uploading = ref(false);
const uploadFile = ref<File | null>(null);
const manualExpiry = ref('');
const extractWithAi = ref(true);
const uploadProjectId = ref<number | ''>(props.projects[0]?.id ?? '');
const isDraggingFile = ref(false);
const uploadError = ref<string | null>(null);

const photoUploading = ref(false);
const photoFiles = ref<File[]>([]);
const photoCaption = ref('');
const photoError = ref<string | null>(null);
const lightboxUrl = ref<string | null>(null);
const lightboxCaption = ref('');
const editingCaptionId = ref<number | null>(null);
const editingCaptionValue = ref('');

const acceptedPhotoMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const acceptedPhotoExtensions = ['.jpg', '.jpeg', '.png', '.webp', '.gif'];

const acceptedExtensions = ['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png', '.webp'];
const acceptedMimeTypes = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'image/jpeg',
  'image/png',
  'image/webp',
];
const fileAcceptLabel = 'PDF, DOC, DOCX, JPG, PNG, or WebP up to 20MB';

function isAcceptedPhoto(file: File): boolean {
  const name = file.name.toLowerCase();
  const hasExtension = acceptedPhotoExtensions.some((ext) => name.endsWith(ext));
  const hasMime = !file.type || acceptedPhotoMimes.includes(file.type);
  return hasExtension && hasMime;
}

function onPhotoFilesChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  photoError.value = null;
  const accepted = files.filter(isAcceptedPhoto);
  if (accepted.length !== files.length) {
    photoError.value = 'Only JPG, PNG, WebP, or GIF images up to 10MB.';
  }
  photoFiles.value = accepted.slice(0, 10);
}

function uploadPhotos() {
  if (!photoFiles.value.length || photoUploading.value) return;
  photoUploading.value = true;
  photoError.value = null;

  const form = new FormData();
  photoFiles.value.forEach((file) => form.append('photos[]', file));
  if (photoCaption.value.trim()) {
    form.append('caption', photoCaption.value.trim());
  }

  router.post(`/sites/${props.site.id}/photos`, form, {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      photoUploading.value = false;
    },
    onSuccess: () => {
      photoFiles.value = [];
      photoCaption.value = '';
      const input = document.getElementById('site-photo-upload') as HTMLInputElement | null;
      if (input) input.value = '';
    },
    onError: (errors) => {
      photoError.value = (errors.photos as string) || 'Upload failed.';
    },
  });
}

function setCoverPhoto(photo: SitePhoto) {
  router.patch(
    `/sites/${props.site.id}/photos/${photo.id}`,
    { is_cover: true },
    { preserveScroll: true },
  );
}

function savePhotoCaption(photo: SitePhoto, caption: string) {
  router.patch(
    `/sites/${props.site.id}/photos/${photo.id}`,
    { caption },
    { preserveScroll: true },
  );
}

function deletePhoto(photo: SitePhoto) {
  if (!confirm('Remove this photo?')) return;
  router.delete(`/sites/${props.site.id}/photos/${photo.id}`, { preserveScroll: true });
}

function openLightbox(photo: SitePhoto) {
  lightboxUrl.value = photo.url;
  lightboxCaption.value = photo.caption || photo.original_filename;
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

function clearUploadFile() {
  uploadFile.value = null;
  const fileInput = document.getElementById('site-upload-file') as HTMLInputElement | null;
  if (fileInput) fileInput.value = '';
}

function assignUploadFile(file: File | null) {
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
  assignUploadFile(event.dataTransfer?.files?.[0] ?? null);
}

const editForm = useForm({
  next_due_date: '',
  assignee: '',
  notes: '',
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

function statusLabel(status: string, quiet = false): string {
  if (quiet) {
    return 'Not yet dated';
  }
  const map: Record<string, string> = {
    overdue: 'Overdue',
    due_soon: 'Due soon',
    compliant: 'Compliant',
    missing: 'Missing date',
  };
  return map[status] ?? status;
}

function requirementCardClass(quiet = false): string {
  return quiet
    ? 'rounded-lg border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40 p-4 md:p-5'
    : card;
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

function deleteRequirement(requirement: ComplianceRequirement) {
  if (!confirm(`Delete compliance item "${requirement.label}"?${linkedTodoWarning(requirement.linked_todo_count)} This cannot be undone.`)) return;
  router.delete(`/sites/${props.site.id}/compliance/${requirement.id}`, {
    onSuccess: () => window.dispatchEvent(new CustomEvent('todoChanged')),
  });
}

function applyTemplate() {
  if (!confirm('Apply the industry compliance template to this site? New items will be added.')) return;
  router.post(`/sites/${props.site.id}/compliance-template`);
}

function openProposalReview(proposalId: number) {
  window.dispatchEvent(new CustomEvent('open-document-extraction', { detail: { proposalId } }));
}

async function uploadDocument() {
  if (!uploadFile.value) return;
  uploading.value = true;
  uploadError.value = null;
  try {
    const result = await operationalSiteApi.uploadDocument(props.site.id, uploadFile.value, {
      expires_at: manualExpiry.value || undefined,
      extract: extractWithAi.value,
      project_id: uploadProjectId.value ? Number(uploadProjectId.value) : undefined,
    });
    clearUploadFile();
    manualExpiry.value = '';
    if (result.data?.proposal_id) {
      openProposalReview(result.data.proposal_id);
    }
    router.reload({ only: ['site', 'pendingDocumentProposals'] });
  } catch {
    uploadError.value = `Could not upload that document. Try ${fileAcceptLabel}.`;
    if ((window as any).$notify) {
      (window as any).$notify({ type: 'error', title: 'Upload failed', message: 'Could not upload document.' });
    }
  } finally {
    uploading.value = false;
  }
}

function deleteDocument(doc: SiteDocument) {
  if (!confirm(`Delete document "${doc.title}"? The file will be permanently removed.`)) return;
  router.delete(`/sites/${props.site.id}/documents/${doc.id}`);
}

function deleteInspection(insp: SiteInspection) {
  if (!confirm(`Delete inspection "${insp.label}"?${linkedTodoWarning(insp.linked_todo_count)} This cannot be undone.`)) return;
  router.delete(`/inspections/${insp.id}`, {
    onSuccess: () => window.dispatchEvent(new CustomEvent('todoChanged')),
  });
}

const listingFacts = computed(() => {
  const listing = props.site.listing;
  if (!listing) return [];

  const visible = (key: string) => listing.listing_visibility?.[key] !== false;
  const rows = [
    { key: 'price', label: 'Price', value: listing.price_label },
    { key: 'property_type', label: 'Property type', value: props.site.property_type_label && props.site.property_type_label !== '—' ? props.site.property_type_label : null },
    { key: 'bedrooms', label: 'Bedrooms', value: props.site.bedrooms != null ? String(props.site.bedrooms) : null },
    { key: 'bathrooms', label: 'Bathrooms', value: listing.bathrooms != null ? String(listing.bathrooms) : null },
    { key: 'receptions', label: 'Reception rooms', value: listing.receptions != null ? String(listing.receptions) : null },
    { key: 'tenure', label: 'Tenure', value: props.site.tenure_label && props.site.tenure_label !== '—' ? props.site.tenure_label : null },
    { key: 'furnishing', label: 'Furnishing', value: listing.furnishing_label },
    { key: 'deposit', label: 'Deposit', value: listing.deposit_label },
    { key: 'available_from', label: 'Available', value: listing.available_from_label },
    { key: 'council_tax', label: 'Council tax band', value: listing.council_tax_label },
    { key: 'epc', label: 'EPC', value: listing.epc_label },
    { key: 'broadband', label: 'Broadband', value: listing.broadband },
  ];

  return rows
    .filter((row) => row.value)
    .map((row) => ({ ...row, shown: visible(row.key) }));
});

const listingHasCopy = computed(() => {
  const listing = props.site.listing;
  if (!listing) return false;
  return Boolean(listing.listing_description) || (listing.key_features?.length ?? 0) > 0 || listingHasFacts(listing);
});

function listingHasFacts(listing: SiteListing): boolean {
  return Boolean(
    listing.price_label
    || listing.listing_type_label
    || listing.bathrooms != null
    || listing.deposit_label
    || listing.available_from_label
    || listing.council_tax_label
    || listing.epc_label
    || listing.broadband
    || listing.furnishing_label,
  );
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
                <Link href="/sites" class="text-sm flex items-center gap-2 mb-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                  <Icon name="ArrowLeft" class="w-4 h-4" />
                  All sites
                </Link>
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ site.type_label }}</div>
                <h1 class="text-2xl font-semibold">{{ site.name }}</h1>
                <p v-if="site.full_address" class="text-gray-600 dark:text-gray-400 mt-1">{{ site.full_address }}</p>
                <p
                  v-if="site.property_type_label || site.bedrooms != null || site.tenure_label || site.occupancy_label"
                  class="text-sm text-gray-600 dark:text-gray-400 mt-2 flex flex-wrap gap-x-4 gap-y-1"
                >
                  <span v-if="site.property_type_label">{{ site.property_type_label }}</span>
                  <span v-if="site.bedrooms != null">{{ site.bedrooms }} bedrooms</span>
                  <span v-if="site.tenure_label">{{ site.tenure_label }}</span>
                  <span v-if="site.occupancy_label">{{ site.occupancy_label }}</span>
                </p>
                <p v-if="site.client" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Client:
                  <Link :href="`/clients/${site.client.id}`" class="hover:underline">{{ site.client.name }}</Link>
                </p>
                <p v-if="site.parent" class="text-sm text-gray-500 mt-1">Inside {{ site.parent.name }}</p>
              </div>
              <div class="flex flex-wrap gap-2 shrink-0">
                <Link href="/compliance" :class="btnSecondary" title="Compliance sits between Clients and Sites — portfolio overview">
                  Compliance overview
                </Link>
                <Link :href="`/sites/create?parent_id=${site.id}`" :class="btnSecondary">Add child site</Link>
                <Link :href="`/sites/${site.id}/edit`" :class="btnSecondary">Edit</Link>
              </div>
            </div>

            <div class="mb-8 rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20 p-4">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">You're on one site</p>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Hierarchy: Company → Clients →
                <Link href="/compliance" class="underline hover:no-underline">Compliance</Link>
                → Sites → Projects → Tasks.
                Everything below is for <span class="font-medium">{{ site.name }}</span> only.
              </p>
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

            <section v-if="site.listing && (site.listing.show_on_zapproperty || listingHasCopy)" class="mb-8">
              <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <h2 :class="sectionTitle">ZapProperty listing</h2>
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="site.listing.show_on_zapproperty
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300'"
                >
                  {{ site.listing.show_on_zapproperty ? 'Displayed on ZapProperty' : 'Not displayed on ZapProperty' }}
                </span>
              </div>
              <p v-if="site.listing.listing_type_label" class="text-sm text-gray-500 mb-3">{{ site.listing.listing_type_label }}</p>
              <p v-if="site.listing.price_label" class="text-xl font-semibold mb-4">{{ site.listing.price_label }}</p>
              <dl v-if="listingFacts.length" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-4 text-sm">
                <div v-for="fact in listingFacts" :key="fact.key" class="flex justify-between gap-3 border-b border-gray-100 dark:border-gray-800 py-1">
                  <dt class="text-gray-500">{{ fact.label }}</dt>
                  <dd class="text-right">
                    {{ fact.value }}
                    <span v-if="site.listing.show_on_zapproperty && !fact.shown" class="ml-2 text-xs text-gray-400">Hidden</span>
                  </dd>
                </div>
              </dl>
              <div v-if="site.listing.key_features?.length" class="mb-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-2">
                  Key features
                  <span v-if="site.listing.show_on_zapproperty && site.listing.listing_visibility?.features === false" class="normal-case tracking-normal text-gray-400">· hidden on ZapProperty</span>
                </p>
                <ul class="list-disc pl-5 text-sm space-y-1">
                  <li v-for="feature in site.listing.key_features" :key="feature">{{ feature }}</li>
                </ul>
              </div>
              <div v-if="site.listing.listing_description">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-2">
                  Description
                  <span v-if="site.listing.show_on_zapproperty && site.listing.listing_visibility?.description === false" class="normal-case tracking-normal text-gray-400">· hidden on ZapProperty</span>
                </p>
                <p class="text-sm whitespace-pre-line text-gray-700 dark:text-gray-300">{{ site.listing.listing_description }}</p>
              </div>
            </section>

            <section class="mb-8">
              <h2 :class="sectionTitle" class="mb-1">Property photos</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-xl">
                Listing photos for this property — front elevation, rooms, and garden. The cover photo appears on the Sites list.
              </p>

              <div
                v-if="site.photos?.length"
                class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4"
              >
                <figure
                  v-for="photo in site.photos"
                  :key="photo.id"
                  class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900"
                >
                  <button
                    type="button"
                    class="relative block w-full aspect-[4/3] bg-gray-100 dark:bg-gray-950"
                    @click="openLightbox(photo)"
                  >
                    <img
                      :src="photo.url"
                      :alt="photo.caption || photo.original_filename"
                      class="h-full w-full object-cover"
                      loading="lazy"
                    />
                    <span
                      v-if="photo.is_cover"
                      class="absolute left-2 top-2 rounded bg-black/70 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-white"
                    >
                      Cover
                    </span>
                  </button>

                  <figcaption class="space-y-2 border-t border-gray-200 dark:border-gray-700 p-3">
                    <div v-if="editingCaptionId === photo.id" class="flex gap-2">
                      <input
                        v-model="editingCaptionValue"
                        type="text"
                        maxlength="255"
                        :class="input"
                        placeholder="Caption"
                        @keyup.enter="savePhotoCaption(photo, editingCaptionValue); editingCaptionId = null"
                      />
                      <button
                        type="button"
                        :class="btnPrimary"
                        class="!px-2 !py-1 text-xs shrink-0"
                        @click="savePhotoCaption(photo, editingCaptionValue); editingCaptionId = null"
                      >
                        Save
                      </button>
                      <button
                        type="button"
                        :class="btnSecondary"
                        class="!px-2 !py-1 text-xs shrink-0"
                        @click="editingCaptionId = null"
                      >
                        Cancel
                      </button>
                    </div>
                    <p v-else class="text-sm text-gray-700 dark:text-gray-200 truncate">
                      {{ photo.caption || photo.original_filename }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                      <button
                        type="button"
                        :class="btnSecondary"
                        class="!px-2 !py-1 text-xs"
                        @click="openLightbox(photo)"
                      >
                        View
                      </button>
                      <button
                        type="button"
                        :class="btnSecondary"
                        class="!px-2 !py-1 text-xs"
                        @click="editingCaptionId = photo.id; editingCaptionValue = photo.caption || ''"
                      >
                        Edit caption
                      </button>
                      <button
                        v-if="!photo.is_cover"
                        type="button"
                        :class="btnSecondary"
                        class="!px-2 !py-1 text-xs"
                        @click="setCoverPhoto(photo)"
                      >
                        Set cover
                      </button>
                      <button
                        type="button"
                        :class="btnDangerSm"
                        @click="deletePhoto(photo)"
                      >
                        Remove
                      </button>
                    </div>
                  </figcaption>
                </figure>
              </div>

              <div :class="card">
                <label :class="label" for="site-photo-upload">Add photos</label>
                <p class="text-xs text-gray-500 mt-1 mb-3">JPG, PNG, WebP, or GIF — up to 10 at a time, 10MB each (max 30 per site).</p>
                <input
                  id="site-photo-upload"
                  type="file"
                  accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                  multiple
                  class="block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:file:bg-white dark:file:text-black"
                  @change="onPhotoFilesChange"
                />
                <input
                  v-model="photoCaption"
                  type="text"
                  maxlength="255"
                  placeholder="Optional caption for the first photo"
                  :class="[input, 'mt-3']"
                />
                <p v-if="photoFiles.length" class="mt-2 text-xs text-gray-500">
                  {{ photoFiles.length }} file{{ photoFiles.length === 1 ? '' : 's' }} selected
                </p>
                <p v-if="photoError" class="mt-2 text-sm text-red-600">{{ photoError }}</p>
                <button
                  type="button"
                  :class="[btnPrimary, 'mt-3']"
                  :disabled="!photoFiles.length || photoUploading"
                  @click="uploadPhotos"
                >
                  {{ photoUploading ? 'Uploading…' : 'Upload photos' }}
                </button>
              </div>
            </section>

            <div
              v-if="lightboxUrl"
              class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/85 p-4"
              @click.self="lightboxUrl = null"
            >
              <button
                type="button"
                class="absolute right-4 top-4 rounded-full bg-white px-3 py-1 text-sm font-medium text-gray-900"
                @click="lightboxUrl = null"
              >
                Close
              </button>
              <img :src="lightboxUrl" :alt="lightboxCaption" class="max-h-[85vh] max-w-full rounded-lg object-contain" />
              <p v-if="lightboxCaption" class="mt-3 max-w-xl text-center text-sm text-white/90">{{ lightboxCaption }}</p>
            </div>

            <section class="mb-8">
              <h2 :class="sectionTitle" class="mb-1">Certificates & documents for this site</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-xl">
                Upload gas safety, EICR, EPC, insurance, and similar files for this location. They also appear on the company Compliance page.
              </p>

              <div
                v-for="proposal in pendingDocumentProposals ?? []"
                :key="proposal.id"
                class="mb-3 rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20 p-4 flex items-center justify-between gap-4"
              >
                <div>
                  <div class="font-medium text-sm">AI extraction ready</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">{{ proposal.document_title }}</div>
                </div>
                <button type="button" :class="btnPrimary" @click="openProposalReview(proposal.id)">Review</button>
              </div>

              <div :class="card" class="mb-4">
                <div class="mb-4">
                  <label :class="label" for="site-upload-file">Upload certificate or document</label>
                  <p class="text-xs text-gray-500 mt-1">
                    PDF, DOC, DOCX, or photos — fire safety, PAT tests, boiler servicing, contracts, and similar.
                    AI reads expiry dates, then you can create reminder todos for your company.
                  </p>
                </div>

                <label
                  for="site-upload-file"
                  class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors"
                  :class="isDraggingFile
                    ? 'border-blue-500 bg-blue-50/70 dark:bg-blue-950/30'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 bg-gray-50/80 dark:bg-gray-900/40'"
                  @dragenter.prevent="isDraggingFile = true"
                  @dragover.prevent="isDraggingFile = true"
                  @dragleave.prevent="isDraggingFile = false"
                  @drop="onDrop"
                >
                  <input
                    id="site-upload-file"
                    type="file"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*"
                    class="sr-only"
                    @change="onFileChange"
                  />
                  <div class="rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3">
                    <Icon name="Upload" class="w-6 h-6 text-gray-700 dark:text-gray-200" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      Drag & drop a file here, or click to browse
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

                <p v-if="uploadError" class="mt-3 text-sm text-red-600 dark:text-red-400">{{ uploadError }}</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                  <div v-if="projects.length">
                    <label :class="label" for="site-upload-project">Project for AI reminder todos</label>
                    <select id="site-upload-project" v-model="uploadProjectId" :class="select">
                      <option v-for="project in projects" :key="project.id" :value="project.id">
                        {{ project.key }} — {{ project.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label :class="label" for="site-upload-expiry">Expiry date (optional)</label>
                    <input id="site-upload-expiry" v-model="manualExpiry" type="date" :class="input" />
                  </div>
                  <div class="md:col-span-2 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                      <input
                        v-model="extractWithAi"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span>
                        <span class="font-medium">Extract details with AI</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                          Reads certificate type and expiry, then opens a review before creating reminder todos.
                        </span>
                      </span>
                    </label>
                    <button
                      type="button"
                      class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 disabled:opacity-60 disabled:cursor-not-allowed dark:bg-white dark:text-black dark:hover:bg-gray-100"
                      :disabled="!uploadFile || uploading"
                      @click="uploadDocument"
                    >
                      <Icon :name="uploading ? 'Loader2' : 'Upload'" class="w-4 h-4" :class="{ 'animate-spin': uploading }" />
                      {{ uploading ? 'Uploading…' : (extractWithAi ? 'Upload & extract' : 'Upload') }}
                    </button>
                  </div>
                </div>
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
                      <span v-if="doc.document_type" class="capitalize">{{ doc.document_type.replace(/_/g, ' ') }} · </span>
                      {{ doc.original_filename }}
                      <span v-if="doc.expires_display"> · Expires {{ doc.expires_display }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-3 shrink-0">
                    <a :href="doc.download_url" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Download</a>
                    <button type="button" :class="btnDangerSm" @click="deleteDocument(doc)">Delete</button>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No documents uploaded yet.</p>
            </section>

            <section class="mb-8">
              <h2 :class="sectionTitle" class="mb-1">Inspections for this site</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-xl">
                Run checklists and download PDF reports for this location only.
              </p>
              <div v-if="inspectionTemplates?.length" class="flex flex-wrap gap-2 mb-4">
                <Link
                  v-for="template in inspectionTemplates"
                  :key="template.key"
                  :href="`/sites/${site.id}/inspections/create?template=${template.key}`"
                  :class="btnSecondary"
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
                  <div class="flex items-center gap-3 shrink-0">
                    <a v-if="insp.pdf_url" :href="insp.pdf_url" class="text-sm text-blue-600 dark:text-blue-400">PDF</a>
                    <button type="button" :class="btnDangerSm" @click="deleteInspection(insp)">Delete</button>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-500">No inspections yet. Start one using the buttons above.</p>
            </section>

            <section>
              <div class="flex flex-col gap-1 mb-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h2 :class="sectionTitle">Checklist for this site</h2>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-xl">
                    Due dates and renewals for this location. Items without a date stay under Unscheduled here — they only appear on the company
                    <Link href="/compliance" class="underline hover:no-underline">Compliance</Link>
                    page once dated, documented, or linked to a task.
                  </p>
                </div>
                <div v-if="hasComplianceTemplates" class="shrink-0 sm:text-right">
                  <button type="button" :class="btnSecondary" @click="applyTemplate">
                    Apply industry template
                  </button>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-xs sm:ml-auto">
                    Adds a starter checklist (gas, EICR, contracts, and similar) for this site. Set due dates to start tracking them company-wide.
                  </p>
                </div>
              </div>

              <div v-if="site.compliance_requirements.length" class="space-y-3">
                <div
                  v-for="requirement in site.compliance_requirements"
                  :key="requirement.id"
                  :class="requirementCardClass(false)"
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
                      <Link
                        v-if="requirement.open_todo?.url"
                        :href="requirement.open_todo.url"
                        class="text-xs text-blue-600 dark:text-blue-400 mt-1 inline-flex hover:underline"
                      >
                        Open task on board
                      </Link>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(requirement.status)">
                      {{ statusLabel(requirement.status) }}
                    </span>
                  </div>

                  <div v-if="editingId === requirement.id" class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                      <label :class="label">Due date</label>
                      <input v-model="editForm.next_due_date" type="date" :class="input" />
                    </div>
                    <div>
                      <label :class="label">Assignee</label>
                      <input v-model="editForm.assignee" type="text" :class="input" />
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                      <button type="button" :class="btnPrimary" @click="saveRequirement(requirement.id)">Save</button>
                      <button type="button" :class="btnSecondary" @click="editingId = null">Cancel</button>
                    </div>
                  </div>

                  <div v-else class="mt-3 flex flex-wrap gap-2">
                    <button type="button" :class="btnSecondary" class="!px-2.5 !py-1 text-xs" @click="startEdit(requirement)">Edit</button>
                    <button
                      v-if="requirement.next_due_date"
                      type="button"
                      class="text-xs rounded-md px-2.5 py-1 border border-green-300 text-green-700 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-950/30"
                      @click="completeRequirement(requirement.id)"
                    >
                      Mark complete
                    </button>
                    <button type="button" :class="btnDangerSm" @click="deleteRequirement(requirement)">Delete</button>
                  </div>
                </div>
              </div>

              <div
                v-else-if="!(site.unscheduled_compliance_requirements?.length)"
                class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center"
              >
                <p class="text-gray-600 dark:text-gray-400 mb-2">No scheduled checklist items yet.</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-md mx-auto">
                  Apply the industry template to seed a checklist for this site. Unscheduled items stay below until you set a due date — then they show on the company Compliance overview.
                </p>
                <button v-if="hasComplianceTemplates" type="button" :class="btnPrimary" @click="applyTemplate">
                  Apply industry template
                </button>
              </div>

              <p v-else class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                No dated checklist items yet. Set a due date on an unscheduled item below to track it here and on Compliance.
              </p>

              <div v-if="site.unscheduled_compliance_requirements?.length" class="mt-8">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unscheduled</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 mb-3 max-w-xl">
                  Not yet dated — set a due date or assignee, or delete items you do not need. These do not count as overdue and do not appear on the company Compliance overview.
                </p>
                <div class="space-y-3">
                  <div
                    v-for="requirement in site.unscheduled_compliance_requirements"
                    :key="requirement.id"
                    :class="requirementCardClass(true)"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <div class="font-medium text-gray-800 dark:text-gray-200">{{ requirement.label }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">No due date set</div>
                      </div>
                      <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        {{ statusLabel(requirement.status, true) }}
                      </span>
                    </div>

                    <div v-if="editingId === requirement.id" class="mt-4 grid gap-4 md:grid-cols-2">
                      <div>
                        <label :class="label">Due date</label>
                        <input v-model="editForm.next_due_date" type="date" :class="input" />
                      </div>
                      <div>
                        <label :class="label">Assignee</label>
                        <input v-model="editForm.assignee" type="text" :class="input" />
                      </div>
                      <div class="md:col-span-2 flex gap-2">
                        <button type="button" :class="btnPrimary" @click="saveRequirement(requirement.id)">Save</button>
                        <button type="button" :class="btnSecondary" @click="editingId = null">Cancel</button>
                      </div>
                    </div>

                    <div v-else class="mt-3 flex flex-wrap gap-2">
                      <button type="button" :class="btnSecondary" class="!px-2.5 !py-1 text-xs" @click="startEdit(requirement)">
                        Set due date
                      </button>
                      <button type="button" :class="btnSecondary" class="!px-2.5 !py-1 text-xs" @click="startEdit(requirement)">Edit</button>
                      <button type="button" :class="btnDangerSm" @click="deleteRequirement(requirement)">Delete</button>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <p v-if="site.notes" class="mt-8 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-6">{{ site.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
