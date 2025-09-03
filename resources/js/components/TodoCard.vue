<template>
  <div
    class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="handleClick"
  >
    <!-- Priority indicator -->
    <div class="flex items-start justify-between mb-2">
      <div class="flex items-center gap-2">
        <span
          :class="[
            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
            priorityClasses[todo.priority]
          ]"
        >
          {{ todo.priority }}
        </span>
        <span
          v-if="todo.type"
          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
        >
          {{ todo.type }}
        </span>
      </div>
      <button
        @click.stop="$emit('delete', todo.id)"
        class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-opacity"
      >
        <Icon name="Trash2" class="w-4 h-4" />
      </button>
    </div>

    <!-- Title -->
    <h3 
      :class="[
        'font-medium text-gray-900 dark:text-gray-100 mb-2 line-clamp-2',
        { 'line-through opacity-60': todo.status === 'done' }
      ]"
    >
      {{ todo.title }}
    </h3>

    <!-- Description -->
    <p
      v-if="todo.description"
      :class="[
        'text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2',
        { 'line-through opacity-60': todo.status === 'done' }
      ]"
    >
      {{ todo.description }}
    </p>

    <!-- Tags -->
    <div v-if="todo.tags && todo.tags.length" class="flex flex-wrap gap-1 mb-3">
      <span
        v-for="tag in todo.tags"
        :key="tag"
        :class="[
          'inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
          { 'line-through opacity-60': todo.status === 'done' }
        ]"
      >
        {{ tag }}
      </span>
    </div>

    <!-- Assignee and due date -->
    <div class="flex items-center justify-between text-sm">
      <div class="flex items-center gap-2">
        <div
          v-if="todo.assignee"
          class="w-6 h-6 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-medium"
        >
          {{ todo.assignee.charAt(0).toUpperCase() }}
        </div>
        <span v-else class="text-gray-400">Unassigned</span>
      </div>
      
      <div class="flex items-center gap-1 text-gray-500">
        <Icon name="Calendar" class="w-4 h-4" />
        <span v-if="todo.due_date">{{ formatDate(todo.due_date) }}</span>
        <span v-else>No due date</span>
      </div>
    </div>

    <!-- Story points -->
    <div v-if="todo.story_points" class="absolute top-2 right-2">
      <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
        {{ todo.story_points }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';

import type { Todo } from '@/services/todoApi';

interface Props {
  todo: Todo;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  edit: [todo: Todo];
  delete: [id: string];
}>();

const priorityClasses = {
  Low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-yellow-200',
  Medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  High: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  Critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

// Track if we're dragging to prevent click during drag
let isDragging = false;

const handleClick = () => {
  if (!isDragging) {
    emit('edit', props.todo);
  }
  isDragging = false;
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffTime = date.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays < 0) {
    return `${Math.abs(diffDays)}d overdue`;
  } else if (diffDays === 0) {
    return 'Today';
  } else if (diffDays === 1) {
    return 'Tomorrow';
  } else if (diffDays <= 7) {
    return `${diffDays}d`;
  } else {
    return date.toLocaleDateString();
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
