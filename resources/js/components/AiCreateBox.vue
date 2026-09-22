<script setup lang="ts">
import { computed, ref } from 'vue';
import axios from 'axios';
import Icon from './Icon.vue';
import { useDashboardProjectContext } from '@/composables/useDashboardProjectContext';

interface TaskProposal {
  title?: string;
  assigned_to?: string | null;
  asset_id?: number | null;
  asset_reference?: string | null;
  asset_name?: string | null;
  due_date?: string | null;
  category?: string | null;
  priority?: string | null;
  description?: string | null;
}

interface Preview {
  title?: string | null;
  assigned_to?: string | null;
  asset?: string | null;
  due_date?: string | null;
  category?: string | null;
  priority?: string | null;
}

const emit = defineEmits<{
  created: [task: Record<string, unknown>];
}>();

const { currentProjectId } = useDashboardProjectContext();

const message = ref('');
const loading = ref(false);
const confirming = ref(false);
const error = ref<string | null>(null);
const proposal = ref<TaskProposal | null>(null);
const preview = ref<Preview | null>(null);
const editing = ref(false);

const canSubmit = computed(() => message.value.trim().length > 0 && !loading.value);

const formatDueDate = (value?: string | null) => {
  if (!value) return null;
  try {
    return new Date(value + 'T00:00:00').toLocaleDateString(undefined, {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return value;
  }
};

const reset = () => {
  proposal.value = null;
  preview.value = null;
  editing.value = false;
  error.value = null;
};

const dismiss = () => {
  reset();
  message.value = '';
};

const propose = async () => {
  if (!canSubmit.value) return;

  loading.value = true;
  error.value = null;
  proposal.value = null;
  preview.value = null;

  try {
    const { data } = await axios.post('/api/ai', {
      message: message.value.trim(),
      context: 'task_creation',
      project_id: currentProjectId.value ?? undefined,
    });

    if (!data?.success || data.intent !== 'create_task' || !data.task) {
      error.value = data?.message || 'Could not understand that request. Try rephrasing.';
      return;
    }

    proposal.value = data.task;
    preview.value = data.preview ?? {
      title: data.task.title,
      assigned_to: data.task.assigned_to,
      asset: data.task.asset_name || data.task.asset_reference,
      due_date: data.task.due_date,
      category: data.task.category,
      priority: data.task.priority,
    };
  } catch (e: any) {
    error.value =
      e?.response?.data?.message ||
      e?.response?.data?.errors?.message?.[0] ||
      'Something went wrong talking to AI.';
  } finally {
    loading.value = false;
  }
};

const confirmCreate = async () => {
  if (!proposal.value?.title) return;

  confirming.value = true;
  error.value = null;

  try {
    const { data } = await axios.post('/api/ai', {
      confirm: true,
      message: message.value.trim(),
      project_id: currentProjectId.value ?? undefined,
      task: {
        title: proposal.value.title,
        assigned_to: proposal.value.assigned_to,
        asset_id: proposal.value.asset_id,
        due_date: proposal.value.due_date,
        category: proposal.value.category,
        priority: proposal.value.priority || 'normal',
        description: proposal.value.description || message.value.trim(),
      },
    });

    if (data?.success && data.task) {
      emit('created', data.task);
      dismiss();
    } else {
      error.value = data?.message || 'Could not create the task.';
    }
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Could not create the task.';
  } finally {
    confirming.value = false;
  }
};

const onKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    if (proposal.value && !editing.value) {
      confirmCreate();
    } else {
      propose();
    }
  }
};
</script>

<template>
  <div class="ai-create-box rounded-xl border border-violet-200/80 bg-gradient-to-br from-violet-50 via-white to-sky-50 p-3 shadow-sm dark:border-violet-900/40 dark:from-violet-950/40 dark:via-gray-900 dark:to-sky-950/30">
    <label for="ai-create-input" class="mb-1.5 flex items-center gap-2 text-sm font-medium text-violet-800 dark:text-violet-200">
      <Icon name="Sparkles" class="h-4 w-4" />
      What needs doing?
    </label>

    <div class="flex gap-2">
      <input
        id="ai-create-input"
        v-model="message"
        type="text"
        class="min-w-0 flex-1 rounded-lg border border-violet-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-200 dark:border-violet-800 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:ring-violet-900"
        placeholder="Remind Sarah to renew the insurance for van AB12 CDE two weeks before it expires on 14 November."
        :disabled="loading || confirming"
        @keydown="onKeydown"
      />
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="!canSubmit"
        @click="propose"
      >
        <Icon v-if="loading" name="LoaderCircle" class="h-4 w-4 animate-spin" />
        <Icon v-else name="Sparkles" class="h-4 w-4" />
        <span class="hidden sm:inline">{{ loading ? 'Thinking…' : 'Ask AI' }}</span>
      </button>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
      {{ error }}
    </p>

    <div
      v-if="proposal && preview"
      class="mt-3 rounded-lg border border-violet-200 bg-white/90 p-3 dark:border-violet-800 dark:bg-gray-950/80"
      role="region"
      aria-label="Task preview"
    >
      <p class="text-xs font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-300">
        Create task?
      </p>

      <div v-if="!editing" class="mt-2 space-y-1.5">
        <p class="text-base font-semibold text-gray-900 dark:text-gray-50">
          {{ preview.title }}
        </p>
        <dl class="grid gap-1 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
          <div v-if="preview.assigned_to">
            <dt class="inline text-gray-400">Assigned:</dt>
            <dd class="inline ml-1">{{ preview.assigned_to }}</dd>
          </div>
          <div v-if="preview.asset">
            <dt class="inline text-gray-400">Asset:</dt>
            <dd class="inline ml-1">{{ preview.asset }}</dd>
          </div>
          <div v-if="preview.due_date">
            <dt class="inline text-gray-400">Due:</dt>
            <dd class="inline ml-1">{{ formatDueDate(preview.due_date) }}</dd>
          </div>
          <div v-if="preview.category">
            <dt class="inline text-gray-400">Category:</dt>
            <dd class="inline ml-1">{{ preview.category }}</dd>
          </div>
        </dl>
      </div>

      <div v-else class="mt-2 grid gap-2 sm:grid-cols-2">
        <label class="block text-sm sm:col-span-2">
          <span class="text-gray-500">Title</span>
          <input v-model="proposal.title" class="mt-1 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900" />
        </label>
        <label class="block text-sm">
          <span class="text-gray-500">Assigned</span>
          <input v-model="proposal.assigned_to" class="mt-1 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900" />
        </label>
        <label class="block text-sm">
          <span class="text-gray-500">Due date</span>
          <input v-model="proposal.due_date" type="date" class="mt-1 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900" />
        </label>
        <label class="block text-sm">
          <span class="text-gray-500">Category</span>
          <input v-model="proposal.category" class="mt-1 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900" />
        </label>
        <label class="block text-sm">
          <span class="text-gray-500">Priority</span>
          <select v-model="proposal.priority" class="mt-1 w-full rounded border px-2 py-1.5 dark:border-gray-700 dark:bg-gray-900">
            <option value="low">Low</option>
            <option value="normal">Normal</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </label>
      </div>

      <div class="mt-3 flex flex-wrap gap-2">
        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60"
          :disabled="confirming || !proposal.title"
          @click="confirmCreate"
        >
          <Icon v-if="confirming" name="LoaderCircle" class="mr-1.5 h-4 w-4 animate-spin" />
          Create Task
        </button>
        <button
          type="button"
          class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
          :disabled="confirming"
          @click="editing = !editing"
        >
          {{ editing ? 'Done editing' : 'Edit' }}
        </button>
        <button
          type="button"
          class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
          :disabled="confirming"
          @click="dismiss"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>
