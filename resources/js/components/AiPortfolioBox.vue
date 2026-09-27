<script setup lang="ts">
import { computed, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

interface MatchRow {
  id?: number | null;
  site_id?: number | null;
  site_name?: string | null;
  type_label?: string | null;
  label?: string | null;
  status?: string | null;
  next_due_date?: string | null;
  has_open_task?: boolean;
}

interface SuggestedTask {
  title: string;
  requirement_id?: number;
  asset_id?: number | null;
  due_date?: string | null;
  category?: string;
  priority?: string;
  description?: string;
}

const examples = [
  'Which properties have gas certificates expiring in the next 60 days?',
  'Show me properties with no EICR.',
  'When was the boiler at 22 Richmond Road last serviced?',
  'What needs attention this week?',
];

const message = ref('');
const loading = ref(false);
const creating = ref<number | null>(null);
const error = ref<string | null>(null);
const answer = ref<string | null>(null);
const matches = ref<MatchRow[]>([]);
const suggestedTasks = ref<SuggestedTask[]>([]);
const createdTaskIds = ref<Set<number>>(new Set());

const canSubmit = computed(() => message.value.trim().length > 0 && !loading.value);

const ask = async (preset?: string) => {
  if (preset) {
    message.value = preset;
  }
  if (!canSubmit.value && !preset) return;
  if (!message.value.trim()) return;

  loading.value = true;
  error.value = null;
  answer.value = null;
  matches.value = [];
  suggestedTasks.value = [];

  try {
    const { data } = await axios.post('/ai', {
      message: message.value.trim(),
      context: 'portfolio',
    });

    if (!data?.success || data.intent !== 'portfolio_answer') {
      error.value = data?.message || 'Could not answer that from your portfolio data.';
      return;
    }

    answer.value = data.answer ?? '';
    matches.value = Array.isArray(data.matches) ? data.matches : [];
    suggestedTasks.value = Array.isArray(data.suggested_tasks) ? data.suggested_tasks : [];
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message || 'Portfolio AI is unavailable right now.';
  } finally {
    loading.value = false;
  }
};

const createTask = async (task: SuggestedTask) => {
  if (!task.requirement_id) return;
  creating.value = task.requirement_id;
  error.value = null;

  try {
    const { data } = await axios.post(`/compliance/requirements/${task.requirement_id}/create-task`);
    if (data?.task?.id) {
      createdTaskIds.value = new Set([...createdTaskIds.value, task.requirement_id]);
    }
  } catch {
    error.value = 'Could not create that task on your board.';
  } finally {
    creating.value = null;
  }
};

const createAllSuggested = async () => {
  for (const task of suggestedTasks.value) {
    if (task.requirement_id && !createdTaskIds.value.has(task.requirement_id)) {
      await createTask(task);
    }
  }
};
</script>

<template>
  <section class="rounded-lg border border-slate-200 dark:border-slate-700 bg-gradient-to-br from-slate-50 via-white to-sky-50/40 dark:from-gray-900 dark:via-gray-800 dark:to-slate-900 p-5 md:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
      <div>
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 flex items-center gap-2">
          <Icon name="Sparkles" class="w-4 h-4" />
          Ask about your portfolio
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-xl">
          Questions run against your private sites, certificates, and documents — then can create ZapTask jobs from the answer.
        </p>
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
      <input
        v-model="message"
        type="text"
        class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/40"
        placeholder="e.g. Which gas certificates expire in the next 60 days?"
        @keydown.enter.prevent="ask()"
      />
      <button
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-60 dark:bg-sky-600 dark:hover:bg-sky-500"
        :disabled="!canSubmit"
        @click="ask()"
      >
        <Icon :name="loading ? 'Loader2' : 'Search'" class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        {{ loading ? 'Searching…' : 'Ask' }}
      </button>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
      <button
        v-for="example in examples"
        :key="example"
        type="button"
        class="text-left text-xs rounded-md border border-gray-200 dark:border-gray-700 px-2.5 py-1.5 text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-900"
        @click="ask(example)"
      >
        {{ example }}
      </button>
    </div>

    <p v-if="error" class="mt-4 text-sm text-red-600 dark:text-red-400">{{ error }}</p>

    <div v-if="answer" class="mt-5 rounded-md border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 p-4">
      <pre class="whitespace-pre-wrap font-sans text-sm text-gray-800 dark:text-gray-100 m-0">{{ answer }}</pre>

      <div v-if="matches.length" class="mt-4 space-y-2">
        <div
          v-for="(row, idx) in matches.slice(0, 12)"
          :key="`${row.id ?? row.site_id}-${idx}`"
          class="flex flex-wrap items-center justify-between gap-2 text-sm border-t border-gray-100 dark:border-gray-800 pt-2"
        >
          <div>
            <Link
              v-if="row.site_id"
              :href="`/sites/${row.site_id}`"
              class="font-medium hover:underline"
            >
              {{ row.site_name }}
            </Link>
            <span v-else class="font-medium">{{ row.site_name }}</span>
            <span class="text-gray-500"> · {{ row.type_label || row.label }}</span>
            <span v-if="row.next_due_date" class="text-gray-500"> · due {{ row.next_due_date }}</span>
          </div>
          <button
            v-if="row.id && !row.has_open_task && !createdTaskIds.has(row.id)"
            type="button"
            class="text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 px-2.5 py-1 hover:bg-gray-50 dark:hover:bg-gray-800"
            :disabled="creating === row.id"
            @click="createTask({ title: '', requirement_id: row.id })"
          >
            {{ creating === row.id ? 'Creating…' : 'Create task' }}
          </button>
          <span
            v-else-if="row.id && (row.has_open_task || createdTaskIds.has(row.id))"
            class="text-xs text-green-700 dark:text-green-300"
          >
            Task on board
          </span>
        </div>
      </div>

      <div v-if="suggestedTasks.length" class="mt-4 flex flex-wrap gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium bg-slate-900 text-white hover:bg-slate-800 dark:bg-sky-600"
          @click="createAllSuggested"
        >
          <Icon name="ListTodo" class="w-3.5 h-3.5" />
          Create {{ suggestedTasks.length }} ZapTask {{ suggestedTasks.length === 1 ? 'job' : 'jobs' }}
        </button>
      </div>
    </div>
  </section>
</template>
