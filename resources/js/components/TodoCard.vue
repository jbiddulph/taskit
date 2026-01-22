<template>
  <div
    :data-todo-id="todo.id"
    :class="[
      'group relative rounded-lg border p-4 shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer touch-manipulation',
      isOverdueAndNotDone 
        ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-700' 
        : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700',
      isSelected ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''
    ]"
    :style="projectColor ? { borderColor: projectColor, borderWidth: '1px', borderStyle: 'solid' } : {}"
    @click="handleClick"
  >
    <!-- Selection checkbox (only in select mode) -->
    <div v-if="isSelectMode" class="absolute top-2 left-2 z-10 min-w-[44px] min-h-[44px] flex items-center justify-center -m-2">
      <input
        type="checkbox"
        :checked="isSelected"
        @change="$emit('toggle-selection', todo)"
        @click.stop
        :aria-label="`Select todo: ${todo.title}`"
        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
      />
    </div>

    <!-- Priority indicator -->
    <div class="flex items-start justify-between mb-2" :class="{ 'ml-6': isSelectMode }">
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
      <div class="flex items-center gap-1">
        <!-- Time Tracking button - Hidden for now -->
        <!-- <button
          @click.stop="showTimeTracker = !showTimeTracker"
          class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-500 transition-opacity"
          title="Time Tracking"
        >
          <Icon name="Clock" class="w-4 h-4" />
        </button> -->
        <button
          v-if="!isReadOnly"
          @click.stop="$emit('delete', String(todo.id))"
          @keydown.enter.stop="$emit('delete', String(todo.id))"
          @keydown.space.stop.prevent="$emit('delete', String(todo.id))"
          :aria-label="`Delete todo: ${todo.title}`"
          class="opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 group-focus-within:opacity-100 text-gray-400 hover:text-red-500 focus:text-red-500 transition-opacity focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 rounded min-w-[44px] min-h-[44px] flex items-center justify-center p-2"
        >
          <Icon name="Trash2" class="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    </div>

    <!-- Title -->
    <div class="mb-2">
      <div v-if="!editingTitle" class="flex items-center gap-2">
        <h3
          :class="[
            'font-medium text-gray-900 dark:text-gray-100 line-clamp-2 flex-1 transition-colors',
            { 'line-through opacity-60': todo.status === 'done' },
            { 'cursor-pointer hover:text-blue-600 dark:hover:text-blue-400': !isReadOnly }
          ]"
          @click.stop="!isReadOnly && startEditTitle()"
          :title="isReadOnly ? '' : 'Click to edit title'"
        >
          {{ todo.title }}
        </h3>
        <button
          v-if="!isReadOnly"
          @click.stop="startEditTitle"
          @keydown.enter.stop="startEditTitle"
          @keydown.space.stop.prevent="startEditTitle"
          :aria-label="`Edit title of todo: ${todo.title}`"
          class="opacity-0 group-hover:opacity-100 md:group-hover:opacity-100 group-focus-within:opacity-100 text-gray-400 hover:text-blue-500 focus:text-blue-500 transition-opacity p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 rounded min-w-[44px] min-h-[44px] flex items-center justify-center"
          title="Edit title"
        >
          <Icon name="Edit3" class="w-4 h-4" aria-hidden="true" />
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
          @keydown.enter.stop="saveTitle"
          @keydown.space.stop.prevent="saveTitle"
          aria-label="Save title"
          class="p-2 text-green-500 hover:text-green-600 focus:text-green-600 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 rounded min-w-[44px] min-h-[44px] flex items-center justify-center"
          title="Save"
        >
          <Icon name="Check" class="w-4 h-4" aria-hidden="true" />
        </button>
        <button
          @click.stop="cancelEditTitle"
          @keydown.enter.stop="cancelEditTitle"
          @keydown.space.stop.prevent="cancelEditTitle"
          aria-label="Cancel editing title"
          class="p-2 text-gray-400 hover:text-red-500 focus:text-red-500 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 rounded min-w-[44px] min-h-[44px] flex items-center justify-center"
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
        
        <!-- Todo Unique ID -->
        <button
          @click.stop="copyTodoId"
          :class="[
            'px-2 py-0.5 text-xs font-mono rounded border transition-all duration-200 cursor-pointer',
            copyFeedback === 'copied' 
              ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-300 dark:border-green-600' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
          :style="projectColor && copyFeedback !== 'copied' ? { borderColor: projectColor, borderWidth: '1px', borderStyle: 'solid' } : {}"
          :title="copyFeedback === 'copied' ? 'Copied!' : `Click to copy Todo ID: ${todoUniqueId}`"
        >
          <span v-if="copyFeedback === 'copied'" class="flex items-center gap-1">
            <Icon name="Check" class="w-3 h-3" />
            Copied!
          </span>
          <span v-else>{{ todoUniqueId }}</span>
        </button>
      </div>
      
      <div class="flex items-center gap-1 text-gray-500">
        <Icon name="Calendar" class="w-4 h-4" />
        <span v-if="todo.due_date">{{ formatDate(todo.due_date) }}</span>
        <span v-else>No due date</span>
      </div>
    </div>

    <!-- Story points (only show on main todos, not subtasks) -->
    <div v-if="!todo.parent_task_id" class="absolute top-2 right-2 flex items-center gap-1">
      <span v-if="todo.story_points" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-300">
        {{ todo.story_points }}
      </span>
      <span v-if="todo.is_new_assigned" class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700">
        NEW
      </span>
    </div>

    <!-- Subtask indicator for subtasks -->
    <div v-if="todo.parent_task_id" class="absolute top-2 right-2 flex items-center gap-1">
      <span v-if="todo.is_new_assigned" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700">
        NEW
      </span>
    </div>

    <!-- Subtask Controls (only for non-subtasks) -->
    <div v-if="!todo.parent_task_id" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <button
          v-if="!isReadOnly"
          @click.stop="$emit('add-subtask', todo)"
          class="flex items-center gap-2 text-xs text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
        >
          <Icon name="Plus" class="w-3 h-3" />
          Add Subtask
        </button>
        
        <!-- Show/Hide Subtasks Toggle (only if subtasks exist) -->
        <button
          v-if="todo.subtasks && todo.subtasks.length > 0"
          @click.stop="toggleSubtasks"
          class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
        >
          <Icon 
            :name="subtasksVisible ? 'ChevronUp' : 'ChevronDown'" 
            class="w-3 h-3" 
          />
          {{ subtasksVisible ? 'Hide' : 'Show' }} Subtasks ({{ todo.subtasks.length }})
        </button>
      </div>
    </div>
  </div>

  <!-- Time Tracker - Hidden for now -->
  <!-- <div v-if="showTimeTracker" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
    <TimeTracker :todo-id="todo.id" :user-id="currentUser?.id || 1" />
  </div> -->

  <!-- Subtasks with Collapse Animation -->
  <Transition name="subtasks">
    <div v-if="todo.subtasks && todo.subtasks.length > 0 && subtasksVisible" class="mt-2 overflow-hidden">
      <TransitionGroup name="subtask-item" tag="div">
        <div
          v-for="(subtask, index) in todo.subtasks"
          :key="subtask.id"
          class="relative ml-6"
        >
          <!-- Tree line -->
          <div class="absolute -left-4 top-0 bottom-0 w-px bg-gray-300 dark:bg-gray-600"></div>
          <div class="absolute -left-4 top-4 w-3 h-px bg-gray-300 dark:bg-gray-600"></div>
          
          <!-- Last subtask - end the tree line -->
          <div 
            v-if="index === todo.subtasks.length - 1" 
            class="absolute -left-4 top-4 bottom-0 w-px bg-white dark:bg-gray-800"
          ></div>

          <!-- Subtask card -->
          <TodoCard
            :todo="subtask"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
            @update="$emit('update', $event)"
            @add-subtask="$emit('add-subtask', $event)"
            class="transform scale-95 origin-top-left"
          />
        </div>
      </TransitionGroup>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue';
import Icon from '@/components/Icon.vue';
import TimeTracker from '@/components/TimeTracker.vue';
import { todoApi } from '@/services/todoApi';

import type { Todo } from '@/services/todoApi';

interface Props {
  todo: Todo;
  isSelectMode?: boolean;
  isSelected?: boolean;
  projectColor?: string | null;
  isReadOnly?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  edit: [todo: Todo];
  delete: [id: string];
  update: [todo: Todo];
  'add-subtask': [todo: Todo];
  'toggle-selection': [todo: Todo];
  'todo-click': [todo: Todo];
}>();

// Title editing state
const editingTitle = ref(false);
const editingTitleText = ref('');
const titleInput = ref<HTMLInputElement | null>(null);

// Subtask visibility state
const subtasksVisible = ref(true);

// Copy feedback state
const copyFeedback = ref('');

const priorityClasses = {
  Low: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-yellow-200',
  Medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  High: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  Critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

// Generate unique Todo ID (first 4 chars of project + todo ID)
const todoUniqueId = computed(() => {
  const projectPrefix = props.todo.project?.name?.substring(0, 4).toUpperCase() || 'UNKN';
  return `${projectPrefix}-${props.todo.id}`;
});

// Track if we're dragging to prevent click during drag
let isDragging = false;

const handleClick = () => {
  if (!isDragging) {
    if (props.isSelectMode) {
      emit('toggle-selection', props.todo);
    } else if (props.isReadOnly) {
      // In read-only mode, emit todo-click for modal display
      emit('todo-click', props.todo);
    } else {
      emit('edit', props.todo);
    }
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

// Copy Todo ID to clipboard
const copyTodoId = async () => {
  try {
    await navigator.clipboard.writeText(todoUniqueId.value);
    
    // Show success feedback
    copyFeedback.value = 'copied';
    
    // Clear feedback after 2 seconds
    setTimeout(() => {
      copyFeedback.value = '';
    }, 2000);
    
    // Show success notification if available
    if ((window as any).$notify) {
      (window as any).$notify({
        type: 'success',
        title: 'Copied!',
        message: `Todo ID "${todoUniqueId.value}" copied to clipboard`
      });
    }
  } catch (error) {
    console.error('Failed to copy to clipboard:', error);
    
    // Fallback: try to select text for manual copy
    try {
      const textArea = document.createElement('textarea');
      textArea.value = todoUniqueId.value;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      
      copyFeedback.value = 'copied';
      setTimeout(() => {
        copyFeedback.value = '';
      }, 2000);
    } catch (fallbackError) {
      console.error('Fallback copy failed:', fallbackError);
      if ((window as any).$notify) {
        (window as any).$notify({
          type: 'error',
          title: 'Copy Failed',
          message: 'Unable to copy to clipboard. Please copy manually.'
        });
      }
    }
  }
};

// Toggle subtasks visibility
const toggleSubtasks = () => {
  subtasksVisible.value = !subtasksVisible.value;
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Subtasks collapse/expand animations */
.subtasks-enter-active,
.subtasks-leave-active {
  transition: all 0.3s ease;
  transform-origin: top;
}

.subtasks-enter-from {
  opacity: 0;
  transform: scaleY(0);
  max-height: 0;
}

.subtasks-leave-to {
  opacity: 0;
  transform: scaleY(0);
  max-height: 0;
}

.subtasks-enter-to,
.subtasks-leave-from {
  opacity: 1;
  transform: scaleY(1);
  max-height: 1000px; /* Large enough to accommodate content */
}

/* Individual subtask item animations */
.subtask-item-enter-active,
.subtask-item-leave-active {
  transition: all 0.2s ease;
}

.subtask-item-enter-from,
.subtask-item-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

.subtask-item-move {
  transition: transform 0.2s ease;
}
</style>
