<template>
  <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
    <div class="inline-flex items-center gap-1 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-1.5 py-0.5">
      <Icon name="List" class="w-3 h-3 text-blue-600 dark:text-blue-400" />
      <span class="text-gray-600 dark:text-gray-400 hidden sm:inline">{{ t('dashboard.total_todos') }}</span>
      <span class="font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</span>
    </div>

    <div class="inline-flex items-center gap-1 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-1.5 py-0.5">
      <Icon name="Clock" class="w-3 h-3 text-yellow-600 dark:text-yellow-400" />
      <span class="text-gray-600 dark:text-gray-400 hidden sm:inline">{{ t('dashboard.in_progress') }}</span>
      <span class="font-semibold text-gray-900 dark:text-gray-100">{{ stats.inProgress }}</span>
    </div>

    <div class="inline-flex items-center gap-1 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-1.5 py-0.5">
      <Icon name="CheckCircle" class="w-3 h-3 text-green-600 dark:text-green-400" />
      <span class="text-gray-600 dark:text-gray-400 hidden sm:inline">{{ t('dashboard.completed') }}</span>
      <span class="font-semibold text-gray-900 dark:text-gray-100">{{ stats.done }}</span>
    </div>

    <div class="inline-flex items-center gap-1 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-1.5 py-0.5">
      <Icon name="AlertTriangle" class="w-3 h-3 text-red-600 dark:text-red-400" />
      <span class="text-gray-600 dark:text-gray-400 hidden sm:inline">{{ t('dashboard.overdue') }}</span>
      <span class="font-semibold text-gray-900 dark:text-gray-100">{{ stats.overdue }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
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
</script>
