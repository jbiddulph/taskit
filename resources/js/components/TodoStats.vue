<template>
  <div class="grid grid-cols-4 gap-1 sm:gap-2 mb-3">
    <!-- Total Todos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-2">
      <div class="text-center">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{t('dashboard.total_todos')}}</p>
        <div class="flex items-center justify-center">
          <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-2">
            <Icon name="List" class="w-4 h-4 text-blue-600 dark:text-blue-400" />
          </div>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
        </div>
      </div>
    </div>

    <!-- In Progress -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-2">
      <div class="text-center">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">In Progress</p>
        <div class="flex items-center justify-center">
          <div class="w-6 h-6 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-2">
            <Icon name="Clock" class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
          </div>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.inProgress }}</p>
        </div>
      </div>
    </div>

    <!-- Completed -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-2">
      <div class="text-center">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Completed</p>
        <div class="flex items-center justify-center">
          <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-2">
            <Icon name="CheckCircle" class="w-4 h-4 text-green-600 dark:text-green-400" />
          </div>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.done }}</p>
        </div>
      </div>
    </div>

    <!-- Overdue -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-2">
      <div class="text-center">
        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Overdue</p>
        <div class="flex items-center justify-center">
          <div class="w-6 h-6 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-2">
            <Icon name="AlertTriangle" class="w-4 h-4 text-red-600 dark:text-red-400" />
          </div>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.overdue }}</p>
        </div>
      </div>
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
