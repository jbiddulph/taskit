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
  stats: { api_keys: number; automations: number };
  apiBaseUrl: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const cards = computed(() => [
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
      description="Manage API keys and automations for the ZapTask platform."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="ZapTask Platform"
          description="API access and automations on top of Company → Clients → Compliance → Sites → Projects → Tasks."
        />

        <p v-if="flashSuccess" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200">
          {{ flashSuccess }}
        </p>

        <div class="rounded-lg border border-border bg-muted/40 p-4 text-sm">
          <p class="font-medium text-foreground">Company: {{ company.name }}</p>
          <p class="mt-1 text-muted-foreground">
            Versioned API root:
            <code class="rounded bg-background px-1.5 py-0.5 text-xs">{{ apiBaseUrl }}</code>
          </p>
          <p class="mt-2 text-muted-foreground">
            On the dashboard, use <strong>What needs doing?</strong> for AI task creation (preview → confirm).
          </p>
        </div>

        <div class="grid gap-4">
          <Link
            v-for="card in cards"
            :key="card.href"
            :href="card.href"
            class="flex items-start gap-4 rounded-lg border border-border bg-card p-4 transition hover:border-foreground/20 hover:bg-muted/30"
          >
            <div class="rounded-md border border-border bg-background p-2">
              <Icon :name="card.icon" class="h-5 w-5 text-foreground" />
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
