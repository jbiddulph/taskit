<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Todos -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
            <Icon name="List" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Todos</p>
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
        </div>
      </div>
    </div>

    <!-- In Progress -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
            <Icon name="Clock" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">In Progress</p>
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.inProgress }}</p>
        </div>
      </div>
    </div>

    <!-- Completed -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
            <Icon name="CheckCircle" class="w-5 h-5 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</p>
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.done }}</p>
        </div>
      </div>
    </div>

    <!-- Overdue -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
            <Icon name="AlertTriangle" class="w-5 h-5 text-red-600 dark:text-red-400" />
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overdue</p>
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.overdue }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import type { Todo, TodoStats } from '@/services/todoApi';

interface Props {
  todos: Todo[];
}

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
