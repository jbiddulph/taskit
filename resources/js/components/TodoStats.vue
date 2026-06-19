<template>
  <div class="flex w-full items-stretch gap-2 text-xs">
    <div
      v-for="item in statItems"
      :key="item.key"
      class="flex flex-1 min-w-0 items-center justify-between gap-2 rounded-md border border-gray-200 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-900"
    >
      <span class="truncate text-gray-600 dark:text-gray-400">{{ item.label }}</span>
      <span class="shrink-0 font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ item.value }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Todo, TodoStats } from '@/services/todoApi';
import { useI18n } from 'vue-i18n';

interface Props {
  todos: Todo[];
}
const { t } = useI18n();
const props = defineProps<Props>();

const stats = computed<TodoStats>(() => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  
  const overdue = props.todos.filter(todo => {
    if (!todo.due_date) return false;
    const dueDate = new Date(todo.due_date);
    return dueDate < today && todo.status !== 'done';
  }).length;

  const dueToday = props.todos.filter(todo => {
    if (!todo.due_date) return false;
    const dueDate = new Date(todo.due_date);
    return dueDate.getTime() === today.getTime();
  }).length;

  return {
    total: props.todos.length,
    todo: props.todos.filter(t => t.status === 'todo').length,
    inProgress: props.todos.filter(t => t.status === 'in-progress').length,
    done: props.todos.filter(t => t.status === 'done').length,
    overdue,
    dueToday,
  };
});

const statItems = computed(() => [
  { key: 'total', label: t('dashboard.total_todos'), value: stats.value.total },
  { key: 'inProgress', label: t('dashboard.in_progress'), value: stats.value.inProgress },
  { key: 'done', label: t('dashboard.completed'), value: stats.value.done },
  { key: 'overdue', label: t('dashboard.overdue'), value: stats.value.overdue },
]);
</script>
