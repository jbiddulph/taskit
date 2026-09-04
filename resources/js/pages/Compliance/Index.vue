<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import SeoHead from '@/components/SeoHead.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';

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
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
  } | null;
}

const props = defineProps<Props>();
const { btnPrimary, btnSecondary } = useFormFieldClasses();

function isActiveRequirement(item: Requirement): boolean {
  return Boolean(item.next_due_date || item.last_completed_at || item.has_document || item.has_linked_task);
}

const visibleRequirements = computed(() => props.requirements.filter(isActiveRequirement));

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
</script>

<template>
  <SeoHead
    title="Compliance"
    description="Track certificates, fire safety, PAT tests, boiler servicing, and contracts in ZapTask."
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
                <h1 class="text-2xl font-semibold">Compliance</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                  Certificates and contracts for {{ company?.name }}. Upload PDFs on a site — OpenAI reads expiry dates and notifies everyone on the company code. Only items with a due date, uploaded document, or linked task are listed here.
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Link href="/clients" :class="btnSecondary">Clients</Link>
                <Link href="/sites" :class="btnSecondary">Sites &amp; uploads</Link>
              </div>
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

            <section v-if="pendingProposals.length" class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Waiting for review</h2>
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

            <section class="mb-8">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Checks</h2>
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
              <p v-else class="text-sm text-gray-500">No dated compliance items yet. Apply an industry template on a site, then set a due date, upload a document, or link a task. Empty checklist items stay Unscheduled on the site until then.</p>
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
              <p v-else class="text-sm text-gray-500">No PDFs uploaded yet. Open a site and upload a fire safety, PAT, boiler, or contract document.</p>
            </section>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
