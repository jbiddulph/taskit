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

interface WorkspaceRow {
  id: number;
  name: string;
  description?: string | null;
  type: string;
  is_default: boolean;
  projects_count: number;
  todos_count: number;
  assets_count: number;
}

interface TypeOption {
  value: string;
  label: string;
}

const props = defineProps<{
  workspaces: WorkspaceRow[];
  workspaceTypes: TypeOption[];
  company: { id: number; name: string };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
  { title: 'Platform', href: '/settings/platform' },
  { title: 'Workspaces', href: '/settings/workspaces' },
];

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const createForm = useForm({
  name: '',
  description: '',
  type: 'general',
  is_default: false,
});

const editingId = ref<number | null>(null);
const editForm = useForm({
  name: '',
  description: '',
  type: 'general',
  is_default: false,
});

const createWorkspace = () => {
  createForm.post('/settings/workspaces', {
    preserveScroll: true,
    onSuccess: () => {
      createForm.reset();
      createForm.type = 'general';
    },
  });
};

const startEdit = (workspace: WorkspaceRow) => {
  editingId.value = workspace.id;
  editForm.name = workspace.name;
  editForm.description = workspace.description ?? '';
  editForm.type = workspace.type;
  editForm.is_default = workspace.is_default;
  editForm.clearErrors();
};

const saveEdit = () => {
  if (!editingId.value) return;
  editForm.put(`/settings/workspaces/${editingId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      editingId.value = null;
    },
  });
};

const deleteWorkspace = (workspace: WorkspaceRow) => {
  if (workspace.is_default) return;
  if (!confirm(`Delete workspace "${workspace.name}"? Projects and tasks stay; only the workspace grouping is removed.`)) {
    return;
  }
  router.delete(`/settings/workspaces/${workspace.id}`, { preserveScroll: true });
};

const typeLabel = (value: string) =>
  props.workspaceTypes.find((t) => t.value === value)?.label ?? value;
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <SeoHead
      title="Workspaces"
      description="Organise ZapTask into workspaces for property, fleet, and other areas of work."
      image="/zap_icon.png"
    />

    <SettingsLayout>
      <div class="space-y-8">
        <HeadingSmall
          title="Workspaces"
          description="Group tasks and assets into areas like Property Portfolio, Fleet, or Head Office."
        />

        <p v-if="flashSuccess" class="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
          {{ flashSuccess }}
        </p>

        <form class="space-y-4 rounded-lg border border-border p-4" @submit.prevent="createWorkspace">
          <p class="text-sm font-medium">Create workspace</p>
          <div class="grid gap-2">
            <Label for="ws-name">Name</Label>
            <Input id="ws-name" v-model="createForm.name" placeholder="Property Portfolio" required />
            <InputError :message="createForm.errors.name" />
          </div>
          <div class="grid gap-2">
            <Label for="ws-type">Type</Label>
            <select id="ws-type" v-model="createForm.type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs">
              <option v-for="option in workspaceTypes" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>
          <div class="grid gap-2">
            <Label for="ws-desc">Description</Label>
            <Input id="ws-desc" v-model="createForm.description" placeholder="Optional" />
          </div>
          <label class="flex items-center gap-2 text-sm">
            <input v-model="createForm.is_default" type="checkbox" class="rounded border-input" />
            Make default workspace
          </label>
          <Button type="submit" :disabled="createForm.processing">Create workspace</Button>
        </form>

        <div class="space-y-3">
          <div
            v-for="workspace in workspaces"
            :key="workspace.id"
            class="rounded-lg border border-border p-4"
          >
            <div v-if="editingId === workspace.id" class="space-y-3">
              <Input v-model="editForm.name" />
              <InputError :message="editForm.errors.name" />
              <select v-model="editForm.type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                <option v-for="option in workspaceTypes" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <Input v-model="editForm.description" placeholder="Description" />
              <label class="flex items-center gap-2 text-sm">
                <input v-model="editForm.is_default" type="checkbox" class="rounded border-input" :disabled="workspace.is_default" />
                Default workspace
              </label>
              <div class="flex gap-2">
                <Button type="button" size="sm" :disabled="editForm.processing" @click="saveEdit">Save</Button>
                <Button type="button" size="sm" variant="outline" @click="editingId = null">Cancel</Button>
              </div>
            </div>
            <div v-else class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-semibold">{{ workspace.name }}</h3>
                  <span
                    v-if="workspace.is_default"
                    class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-700 dark:bg-violet-950 dark:text-violet-200"
                  >
                    Default
                  </span>
                  <span class="text-xs text-muted-foreground">{{ typeLabel(workspace.type) }}</span>
                </div>
                <p v-if="workspace.description" class="mt-1 text-sm text-muted-foreground">
                  {{ workspace.description }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                  {{ workspace.projects_count }} projects · {{ workspace.todos_count }} tasks · {{ workspace.assets_count }} assets
                </p>
              </div>
              <div class="flex gap-2">
                <Button type="button" size="sm" variant="outline" @click="startEdit(workspace)">Edit</Button>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  :disabled="workspace.is_default"
                  @click="deleteWorkspace(workspace)"
                >
                  Delete
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
