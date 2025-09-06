<template>
  <div
    :class="[
      'group relative rounded-lg border p-4 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer',
      isOverdueAndNotDone 
        ? 'bg-red-100 dark:bg-red-900/20 border-red-200 dark:border-red-700' 
        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
    ]"
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
          class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
        >
          <Icon :name="getTypeIcon(todo.type)" class="w-3 h-3" />
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
    <div class="mb-2">
      <div v-if="!editingTitle" class="flex items-center gap-2">
        <h3 
          :class="[
            'font-medium text-gray-900 dark:text-gray-100 line-clamp-2 flex-1 cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition-colors',
            { 'line-through opacity-60': todo.status === 'done' }
          ]"
          @click.stop="startEditTitle"
          title="Click to edit title"
        >
          {{ todo.title }}
        </h3>
        <button
          @click.stop="startEditTitle"
          class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-500 transition-opacity p-1"
          title="Edit title"
        >
          <Icon name="Edit3" class="w-3 h-3" />
        </button>
      </div>
      <div v-else class="flex items-center gap-2">
        <input
          v-model="editingTitleText"
          @keydown.enter="saveTitle"
          @keydown.escape="cancelEditTitle"
          @blur="saveTitle"
          class="flex-1 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
          placeholder="Enter todo title"
          ref="titleInput"
        />
        <button
          @click.stop="saveTitle"
          class="p-1 text-green-500 hover:text-green-600 transition-colors"
          title="Save"
        >
          <Icon name="Check" class="w-3 h-3" />
        </button>
        <button
          @click.stop="cancelEditTitle"
          class="p-1 text-gray-400 hover:text-red-500 transition-colors"
          title="Cancel"
        >
          <Icon name="X" class="w-3 h-3" />
        </button>
      </div>
    </div>

    <!-- Description & first image preview -->
    <div v-if="firstImageSrc || plainTextDescription" class="mb-3 flex items-start gap-3">
      <img
        v-if="firstImageSrc"
        :src="firstImageSrc"
        alt="attachment"
        class="w-14 h-14 object-cover rounded border border-gray-200 dark:border-gray-700 flex-shrink-0"
      />
      <p
        v-if="plainTextDescription"
        :class="[
          'text-sm text-gray-600 dark:text-gray-400 line-clamp-2',
          { 'line-through opacity-60': todo.status === 'done' }
        ]"
      >
        {{ plainTextDescription }}
      </p>
    </div>

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
    <div class="absolute top-2 right-2 flex items-center gap-1">
      <span v-if="todo.story_points" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
        {{ todo.story_points }}
      </span>
      <span v-if="todo.is_new_assigned" class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700">
        NEW
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue';
import Icon from '@/components/Icon.vue';
import { todoApi } from '@/services/todoApi';

import type { Todo } from '@/services/todoApi';

interface Props {
  todo: Todo;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  edit: [todo: Todo];
  delete: [id: string];
  update: [todo: Todo];
}>();

// Title editing state
const editingTitle = ref(false);
const editingTitleText = ref('');
const titleInput = ref<HTMLInputElement | null>(null);

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

// Safely extract first image src and plain text from HTML description
const getFirstImageSrc = (html?: string): string | null => {
  if (!html) return null;
  const match = html.match(/<img[^>]*src=["']([^"']+)["'][^>]*>/i);
  return match ? match[1] : null;
};

const stripHtml = (html?: string): string => {
  if (!html) return '';
  const withoutImgs = html.replace(/<img[^>]*>/gi, '');
  const text = withoutImgs.replace(/<[^>]+>/g, ' ');
  return text.replace(/\s+/g, ' ').trim();
};

// Get appropriate icon for todo type
const getTypeIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    'Bug': 'Bug',
    'Feature': 'Zap',
    'Task': 'CheckSquare',
    'Story': 'BookOpen',
    'Epic': 'Layers'
  };
  return iconMap[type] || 'Circle';
};

const firstImageSrc = computed(() => getFirstImageSrc((props as any).todo?.description));
const plainTextDescription = computed(() => stripHtml((props as any).todo?.description));

// Check if todo is overdue and not in Done status
const isOverdueAndNotDone = computed(() => {
  if (!props.todo.due_date || props.todo.status === 'done') {
    return false;
  }
  
  const dueDate = new Date(props.todo.due_date);
  const now = new Date();
  const diffTime = dueDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  return diffDays < 0; // Overdue if due date is in the past
});

// Title editing methods
const startEditTitle = () => {
  editingTitle.value = true;
  editingTitleText.value = props.todo.title;
  nextTick(() => {
    titleInput.value?.focus();
    titleInput.value?.select();
  });
};

const saveTitle = async () => {
  if (!editingTitleText.value.trim()) {
    cancelEditTitle();
    return;
  }
  
  try {
    const updatedTodo = await todoApi.updateTodo(props.todo.id, {
      title: editingTitleText.value.trim()
    });
    
    // Emit update event to parent
    emit('update', updatedTodo);
    
    // Show success notification
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Todo Updated',
        message: 'Todo title has been updated successfully.'
      });
    }
    
    // Exit edit mode
    editingTitle.value = false;
    editingTitleText.value = '';
    
    console.log('Todo title updated successfully');
  } catch (error) {
    console.error('Failed to update todo title:', error);
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'error',
        title: 'Update Failed',
        message: 'Failed to update todo title. Please try again.'
      });
    }
  }
};

const cancelEditTitle = () => {
  editingTitle.value = false;
  editingTitleText.value = '';
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
