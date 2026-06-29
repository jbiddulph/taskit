<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import OperationsTips from '@/components/OperationsTips.vue';
import SeoHead from '@/components/SeoHead.vue';

interface ComplianceCounts {
  overdue: number;
  due_soon: number;
  compliant: number;
  missing: number;
}

interface Site {
  id: number;
  type: string;
  type_label: string;
  name: string;
  reference?: string;
  full_address: string;
  parent_name?: string;
  compliance_counts: ComplianceCounts;
}

interface Props {
  sites: Site[];
  complianceSummary: ComplianceCounts & { total: number };
  hasComplianceTemplates: boolean;
  company?: {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    industry?: string;
  } | null;
}

defineProps<Props>();

function statusColor(status: keyof ComplianceCounts): string {
  const map: Record<string, string> = {
    overdue: 'text-red-600 dark:text-red-400',
    due_soon: 'text-amber-600 dark:text-amber-400',
    compliant: 'text-green-600 dark:text-green-400',
    missing: 'text-gray-500 dark:text-gray-400',
  };
  return map[status] ?? 'text-gray-600';
}
</script>

<template>
  <SeoHead
    title="Sites & Assets"
    description="Manage properties, sites, and compliance requirements in ZapTask."
    image="/zap_icon.png"
  />

  <AppLayout :company="company">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="flex justify-between items-center mb-6">
              <div>
                <div class="flex items-center gap-3 mb-2">
                  <Link href="/dashboard" class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100 flex items-center gap-2">
                    <Icon name="ArrowLeft" class="w-4 h-4" />
                    <span class="text-sm font-medium">Dashboard</span>
                  </Link>
                </div>
                <h1 class="text-2xl font-semibold">Sites & Assets</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                  Properties, buildings, equipment, and compliance tracking
                </p>
              </div>
              <Link
                href="/sites/create"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium border transition-colors bg-black text-white hover:bg-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100"
              >
                <Icon name="Plus" class="w-4 h-4" />
                Add Site
              </Link>
            </div>

            <OperationsTips context="sites_index" class="mb-8" :default-open="sites.length === 0" />

            <div v-if="complianceSummary.total > 0" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
              <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-red-700 dark:text-red-300">Overdue</div>
                <div class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ complianceSummary.overdue }}</div>
              </div>
              <div class="rounded-lg border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Due soon</div>
                <div class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ complianceSummary.due_soon }}</div>
              </div>
              <div class="rounded-lg border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/30 p-4">
                <div class="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Compliant</div>
                <div class="text-2xl font-semibold text-green-700 dark:text-green-300">{{ complianceSummary.compliant }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4">
                <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">Missing dates</div>
                <div class="text-2xl font-semibold text-gray-700 dark:text-gray-300">{{ complianceSummary.missing }}</div>
              </div>
            </div>

            <div v-if="sites.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              <Link
                v-for="site in sites"
                :key="site.id"
                :href="`/sites/${site.id}`"
                class="block rounded-lg border border-gray-200 dark:border-gray-700 p-5 hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
              >
                <div class="flex items-start justify-between gap-3 mb-2">
                  <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ site.type_label }}</div>
                    <h2 class="text-lg font-semibold">{{ site.name }}</h2>
                  </div>
                </div>
                <p v-if="site.full_address" class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ site.full_address }}</p>
                <p v-if="site.parent_name" class="text-xs text-gray-500 mb-3">Inside {{ site.parent_name }}</p>
                <div class="flex flex-wrap gap-3 text-xs">
                  <span :class="statusColor('overdue')">{{ site.compliance_counts.overdue }} overdue</span>
                  <span :class="statusColor('due_soon')">{{ site.compliance_counts.due_soon }} due soon</span>
                  <span :class="statusColor('compliant')">{{ site.compliance_counts.compliant }} compliant</span>
                </div>
              </Link>
            </div>

            <div v-else class="text-center py-12">
              <Icon name="Building2" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
              <h3 class="text-lg font-medium mb-2">No sites yet</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                Add a property, building, or site to start tracking compliance items
                <span v-if="hasComplianceTemplates"> with industry templates</span>.
              </p>
              <Link
                href="/sites/create"
                class="inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium bg-black text-white hover:bg-gray-900 dark:bg-white dark:text-black"
              >
                <Icon name="Plus" class="w-4 h-4" />
                Add your first site
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
