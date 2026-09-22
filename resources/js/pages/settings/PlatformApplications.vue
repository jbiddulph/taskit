<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface ApplicationRow {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  enabled: boolean;
  is_core: boolean;
}

defineProps<{
  applications: ApplicationRow[];
  sdkHint: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
  { title: 'Applications', href: '/settings/platform-applications' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const toggleApp = (app: ApplicationRow) => {
  if (app.is_core) return;
  router.put(
    `/settings/platform-applications/${app.slug}`,
    { enabled: !app.enabled },
    { preserveScroll: true },
  );
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <SeoHead
      title="Platform applications"
      description="Enable specialised apps that run on the ZapTask platform engine."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="Apps on ZapTask"
          description="Specialised apps consume Company → Clients → Compliance → Sites → Projects → Tasks via the Platform API and SDK. Enable the ones your company uses."
        />

        <p
          v-if="flashSuccess"
          class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200"
        >
          {{ flashSuccess }}
        </p>

        <div class="rounded-lg border border-border bg-muted/40 p-4 text-sm text-muted-foreground space-y-2">
          <p>
            Builders ship product UX in their app; ZapTask is the shared engine for tasks, sites, automations, and AI.
          </p>
          <p>
            Use
            <code class="rounded bg-background px-1.5 py-0.5 text-xs">{{ sdkHint }}</code>
            with a
            <Link href="/settings/platform-api-keys" class="underline underline-offset-2">Platform API key</Link>
            (
            <code class="rounded bg-background px-1.5 py-0.5 text-xs">zt_live_…</code>
            ). See
            <code class="rounded bg-background px-1.5 py-0.5 text-xs">BUILDERS.md</code>
            in the repo and
            <code class="rounded bg-background px-1.5 py-0.5 text-xs">examples/property-ops-app</code>
            for a working consumer.
          </p>
        </div>

        <ul class="divide-y divide-border rounded-lg border border-border">
          <li
            v-for="app in applications"
            :key="app.slug"
            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <p class="font-medium text-foreground">{{ app.name }}</p>
              <p class="mt-0.5 text-sm text-muted-foreground">{{ app.description }}</p>
              <p class="mt-1 font-mono text-xs text-muted-foreground">{{ app.slug }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
              <span class="text-xs text-muted-foreground">
                {{ app.is_core ? 'Always on' : app.enabled ? 'Enabled' : 'Off' }}
              </span>
              <Button
                type="button"
                size="sm"
                :variant="app.enabled ? 'default' : 'outline'"
                :disabled="app.is_core"
                @click="toggleApp(app)"
              >
                {{ app.is_core ? 'Core' : app.enabled ? 'Disable' : 'Enable' }}
              </Button>
            </div>
          </li>
        </ul>

        <div class="flex flex-wrap gap-2">
          <Button as-child variant="outline" size="sm">
            <Link href="/settings/platform">Platform overview</Link>
          </Button>
          <Button as-child variant="outline" size="sm">
            <Link href="/settings/automations">Automations</Link>
          </Button>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
