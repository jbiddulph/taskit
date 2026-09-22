<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface ApiKeyRow {
  id: number;
  name: string;
  key_prefix: string;
  permissions?: string[] | null;
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string | null;
}

const props = defineProps<{
  apiKeys: ApiKeyRow[];
  plainTextKey?: string | null;
  apiBaseUrl: string;
  defaultPermissions: string[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
  { title: 'Platform API keys', href: '/settings/platform-api-keys' },
];

const page = usePage();
const flashKey = computed(
  () =>
    props.plainTextKey ||
    (page.props.flash as { plainTextPlatformKey?: string } | undefined)?.plainTextPlatformKey ||
    null,
);

const createForm = useForm({
  name: 'Integration',
});

const copied = ref(false);

const createKey = () => {
  createForm.post('/settings/platform-api-keys', {
    preserveScroll: true,
    onSuccess: () => {
      createForm.name = 'Integration';
    },
  });
};

const revokeKey = (id: number) => {
  if (!confirm('Revoke this platform API key? Apps using it will stop working.')) return;
  router.delete(`/settings/platform-api-keys/${id}`, { preserveScroll: true });
};

const copyText = async (value: string) => {
  try {
    await navigator.clipboard.writeText(value);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch {
    // ignore
  }
};

const formatDate = (value: string | null) => {
  if (!value) return 'Never';
  return new Date(value).toLocaleString();
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <SeoHead
      title="Platform API keys"
      description="Create zt_live_ API keys for the ZapTask Platform API and SDK."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="Platform API keys"
          description="Company-scoped zt_live_ keys for specialised apps, n8n, and @zaptask/sdk. Raw keys are shown once."
        />

        <div class="rounded-lg border border-border bg-muted/40 p-4 text-sm space-y-2">
          <p class="font-medium">API base URL</p>
          <code class="block break-all rounded bg-background px-2 py-1 text-xs">{{ apiBaseUrl }}</code>
          <p class="text-muted-foreground">
            Example: <code class="text-xs">Authorization: Bearer zt_live_…</code>
          </p>
        </div>

        <div
          v-if="flashKey"
          class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 space-y-3"
        >
          <p class="text-sm font-medium">Copy your new key now — it will not be shown again.</p>
          <code class="block break-all rounded bg-background px-3 py-2 text-xs">{{ flashKey }}</code>
          <Button type="button" size="sm" @click="copyText(flashKey)">
            {{ copied ? 'Copied' : 'Copy key' }}
          </Button>
        </div>

        <form class="space-y-4" @submit.prevent="createKey">
          <div class="grid gap-2">
            <Label for="key-name">Key name</Label>
            <Input id="key-name" v-model="createForm.name" required />
            <InputError :message="createForm.errors.name" />
          </div>
          <p class="text-xs text-muted-foreground">
            Default permissions: {{ defaultPermissions.join(', ') }}
          </p>
          <Button type="submit" :disabled="createForm.processing">Create platform key</Button>
        </form>

        <div class="space-y-3">
          <div
            v-for="key in apiKeys"
            :key="key.id"
            class="flex flex-col gap-2 rounded-lg border border-border p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p class="font-medium">{{ key.name }}</p>
              <p class="text-xs text-muted-foreground">
                {{ key.key_prefix }}… · Last used {{ formatDate(key.last_used_at) }}
              </p>
            </div>
            <Button type="button" size="sm" variant="outline" @click="revokeKey(key.id)">Revoke</Button>
          </div>
          <p v-if="!apiKeys.length" class="text-sm text-muted-foreground">No platform API keys yet.</p>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
