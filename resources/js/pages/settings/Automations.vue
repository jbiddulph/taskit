<script setup lang="ts">
import { computed } from 'vue';
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

interface AutomationRow {
  id: number;
  name: string;
  trigger_type: string;
  action_type: string;
  trigger_config?: Record<string, unknown> | null;
  action_config?: Record<string, unknown> | null;
  enabled: boolean;
  last_run_at: string | null;
}

interface Option {
  value: string;
  label: string;
}

defineProps<{
  automations: AutomationRow[];
  triggerOptions: Option[];
  actionOptions: Option[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
  { title: 'Automations', href: '/settings/automations' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const createForm = useForm({
  name: '',
  trigger_type: 'date_reached',
  action_type: 'create_task',
  trigger_date: '',
  task_title: '',
  enabled: true,
});

const createAutomation = () => {
  createForm.post('/settings/automations', {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset();
      createForm.trigger_type = 'date_reached';
      createForm.action_type = 'create_task';
      createForm.enabled = true;
    },
  });
};

const toggleEnabled = (automation: AutomationRow) => {
  router.put(
    `/settings/automations/${automation.id}`,
    { enabled: !automation.enabled },
    { preserveScroll: true },
  );
};

const deleteAutomation = (automation: AutomationRow) => {
  if (!confirm(`Delete automation "${automation.name}"?`)) return;
  router.delete(`/settings/automations/${automation.id}`, { preserveScroll: true });
};

const labelFor = (options: Option[], value: string) =>
  options.find((o) => o.value === value)?.label ?? value;
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <SeoHead
      title="Automations"
      description="Create ZapTask automations that generate tasks when dates are reached."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="Automations"
          description="Simple when/then rules on the ZapTask platform — shared by Property, Fleet, and core."
        />

        <p v-if="flashSuccess" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
          {{ flashSuccess }}
        </p>

        <form class="space-y-4 rounded-lg border border-border p-4" @submit.prevent="createAutomation">
          <p class="text-sm font-medium">Create automation</p>
          <div class="grid gap-2">
            <Label for="auto-name">Name</Label>
            <Input id="auto-name" v-model="createForm.name" placeholder="Renewal reminder" required />
            <InputError :message="createForm.errors.name" />
          </div>
          <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
              <Label for="auto-trigger">When</Label>
              <select id="auto-trigger" v-model="createForm.trigger_type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                <option v-for="option in triggerOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            <div class="grid gap-2">
              <Label for="auto-action">Then</Label>
              <select id="auto-action" v-model="createForm.action_type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                <option v-for="option in actionOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
          </div>
          <div v-if="createForm.trigger_type === 'date_reached'" class="grid gap-2">
            <Label for="auto-date">Trigger date</Label>
            <Input id="auto-date" v-model="createForm.trigger_date" type="date" />
          </div>
          <div class="grid gap-2">
            <Label for="auto-title">Task title (optional)</Label>
            <Input id="auto-title" v-model="createForm.task_title" placeholder="Defaults to automation name" />
          </div>
          <Button type="submit" :disabled="createForm.processing">Create automation</Button>
        </form>

        <div class="space-y-3">
          <div
            v-for="automation in automations"
            :key="automation.id"
            class="rounded-lg border border-border p-4"
          >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-semibold">{{ automation.name }}</h3>
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                    :class="automation.enabled
                      ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                  >
                    {{ automation.enabled ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  When {{ labelFor(triggerOptions, automation.trigger_type) }}
                  → {{ labelFor(actionOptions, automation.action_type) }}
                </p>
                <p v-if="automation.trigger_config?.date" class="mt-1 text-xs text-muted-foreground">
                  Date: {{ automation.trigger_config.date }}
                </p>
              </div>
              <div class="flex gap-2">
                <Button type="button" size="sm" variant="outline" @click="toggleEnabled(automation)">
                  {{ automation.enabled ? 'Disable' : 'Enable' }}
                </Button>
                <Button type="button" size="sm" variant="outline" @click="deleteAutomation(automation)">
                  Delete
                </Button>
              </div>
            </div>
          </div>
          <p v-if="!automations.length" class="text-sm text-muted-foreground">
            No automations yet. Create one above — for example “date reached → create task”.
          </p>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
