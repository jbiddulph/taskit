<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import Icon from '@/components/Icon.vue';

const props = defineProps<{
  company: { id: number; name: string };
  stats: { workspaces: number; api_keys: number; automations: number };
  apiBaseUrl: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const cards = computed(() => [
  {
    title: 'Workspaces',
    description: 'Separate Property, Fleet, Head Office, and Personal work into scoped areas.',
    href: '/settings/workspaces',
    count: props.stats.workspaces,
    icon: 'Layers',
  },
  {
    title: 'Platform API keys',
    description: 'Issue zt_live_ keys for specialised apps, n8n, and the TypeScript SDK.',
    href: '/settings/platform-api-keys',
    count: props.stats.api_keys,
    icon: 'KeyRound',
  },
  {
    title: 'Automations',
    description: 'When a date is reached or a task completes, create follow-up ZapTasks.',
    href: '/settings/automations',
    count: props.stats.automations,
    icon: 'Workflow',
  },
]);
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <SeoHead
      title="ZapTask Platform"
      description="Manage workspaces, API keys, and automations for the ZapTask multi-app platform."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="ZapTask Platform"
          description="Phase 2 foundation — workspaces, API access, and automations that specialised apps share."
        />

        <p v-if="flashSuccess" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200">
          {{ flashSuccess }}
        </p>

        <div class="rounded-lg border border-violet-200 bg-violet-50/70 p-4 text-sm dark:border-violet-900 dark:bg-violet-950/30">
          <p class="font-medium text-violet-900 dark:text-violet-100">Company: {{ company.name }}</p>
          <p class="mt-1 text-violet-800/80 dark:text-violet-200/80">
            Versioned API root:
            <code class="rounded bg-white/80 px-1.5 py-0.5 text-xs dark:bg-gray-900">{{ apiBaseUrl }}</code>
          </p>
          <p class="mt-2 text-violet-800/80 dark:text-violet-200/80">
            On the dashboard, use <strong>What needs doing?</strong> for AI task creation (preview → confirm).
          </p>
        </div>

        <div class="grid gap-4">
          <Link
            v-for="card in cards"
            :key="card.href"
            :href="card.href"
            class="flex items-start gap-4 rounded-lg border border-border bg-card p-4 transition hover:border-violet-300 hover:bg-violet-50/40 dark:hover:border-violet-800 dark:hover:bg-violet-950/20"
          >
            <div class="rounded-md border border-border bg-background p-2">
              <Icon :name="card.icon" class="h-5 w-5 text-violet-600 dark:text-violet-300" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold text-foreground">{{ card.title }}</h3>
                <span class="text-xs tabular-nums text-muted-foreground">{{ card.count }}</span>
              </div>
              <p class="mt-1 text-sm text-muted-foreground">{{ card.description }}</p>
            </div>
          </Link>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button as-child variant="outline" size="sm">
            <Link href="/dashboard">Open dashboard AI box</Link>
          </Button>
          <Button as-child variant="outline" size="sm">
            <Link href="/settings/api-tokens">Chrome extension tokens</Link>
          </Button>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
