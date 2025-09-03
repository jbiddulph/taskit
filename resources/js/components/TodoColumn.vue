<template>
  <div class="flex flex-col h-full min-w-80">
    <!-- Column Header -->
    <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 text-xs font-medium text-gray-700 dark:text-gray-300">
          {{ todos.length }}
        </span>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="showAddButton"
          @click="$emit('add')"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors"
        >
          <Icon name="Plus" class="w-4 h-4" />
        </button>
        <button
          @click="$emit('menu')"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded transition-colors"
        >
          <Icon name="MoreHorizontal" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Column Content -->
    <div
      ref="dropZone"
      class="flex-1 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 min-h-[400px] transition-colors relative"
      :class="{
        'border-blue-300 bg-blue-50 dark:bg-blue-900/20': isDragOver,
        'border-gray-200 dark:border-gray-700': !isDragOver
      }"
      @dragover.prevent="handleDragOver"
      @dragleave="handleDragLeave"
      @drop="handleDrop"
      @dragenter.prevent
    >
      <!-- Empty State -->
      <div
        v-if="todos.length === 0"
        class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400"
      >
        <Icon name="Inbox" class="w-12 h-12 mb-2 opacity-50" />
        <p class="text-sm text-center">No todos yet</p>
        <p class="text-xs text-center opacity-75">Drag and drop here or click + to add</p>
      </div>

      <!-- Drag Over Indicator -->
      <div
        v-if="isDragOver"
        class="absolute inset-0 bg-blue-100 dark:bg-blue-900/30 border-2 border-blue-300 rounded-lg flex items-center justify-center pointer-events-none"
      >
        <div class="text-blue-600 dark:text-blue-300 text-lg font-medium">
          Drop here to move todo
        </div>
      </div>

      <!-- Todo Cards -->
      <TransitionGroup
        v-else
        name="todo-list"
        tag="div"
        class="space-y-3"
      >
        <div
          v-for="todo in todos"
          :key="todo.id"
          :draggable="canDragTodo(todo)"
          @dragstart="handleDragStart($event, todo)"
          @dragend="handleDragEnd"
          class="transform transition-transform duration-200"
          :class="{
            'hover:scale-[1.02]': canDragTodo(todo),
            'opacity-60 cursor-not-allowed': !canDragTodo(todo)
          }"
        >
          <TodoCard
            :todo="todo"
            @edit="$emit('edit', $event)"
            @delete="$emit('delete', $event)"
          />
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import TodoCard from './TodoCard.vue';

import type { Todo } from '@/services/todoApi';

interface Props {
  title: string;
  status: 'todo' | 'in-progress' | 'done';
  todos: Todo[];
  showAddButton?: boolean;
  currentProjectId?: number | null;
}

const props = withDefaults(defineProps<Props>(), {
  showAddButton: false,
});

const emit = defineEmits<{
  add: [];
  edit: [todo: Todo];
  delete: [id: string];
  menu: [];
  drop: [todo: Todo, newStatus: string];
}>();

const dropZone = ref<HTMLElement>();
const isDragOver = ref(false);
const draggedTodo = ref<Todo | null>(null);

// Check if a todo can be dragged (belongs to current project)
const canDragTodo = (todo: Todo): boolean => {
  return !props.currentProjectId || todo.project_id === props.currentProjectId;
};

const handleDragStart = (event: DragEvent, todo: Todo) => {
  console.log('Drag start:', todo.title, 'Status:', todo.status, 'Column:', props.status);
  
  // Validate that the todo belongs to the current project
  if (props.currentProjectId && todo.project_id !== props.currentProjectId) {
    console.log('Cannot drag todo from different project:', todo.project_id, 'vs', props.currentProjectId);
    event.preventDefault();
    return;
  }
  
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', todo.id);
    // Store the full todo data as JSON
    event.dataTransfer.setData('application/json', JSON.stringify(todo));
    draggedTodo.value = todo;
    console.log('Set draggedTodo:', draggedTodo.value?.title);
  }
};

const handleDragEnd = () => {
  console.log('Drag end in column:', props.status, 'draggedTodo:', draggedTodo.value?.title);
  // Always reset drag state when drag ends
  isDragOver.value = false;
  
  // Only clear draggedTodo if this is the source column
  if (draggedTodo.value && draggedTodo.value.status === props.status) {
    console.log('Clearing draggedTodo in source column:', props.status);
    draggedTodo.value = null;
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
  isDragOver.value = true;
  console.log('Drag over column:', props.status, 'isDragOver:', isDragOver.value);
};

const handleDragLeave = (event: DragEvent) => {
  console.log('Drag leave column:', props.status);
  // Use a timeout to prevent flickering when moving between child elements
  setTimeout(() => {
    // Check if we're still over the drop zone or any of its children
    const relatedTarget = event.relatedTarget as HTMLElement;
    if (!dropZone.value?.contains(relatedTarget)) {
      isDragOver.value = false;
    }
  }, 50);
};

const handleDrop = (event: DragEvent) => {
  console.log('Drop event triggered in column:', props.status);
  event.preventDefault();
  event.stopPropagation();
  isDragOver.value = false;
  
  // Get the todo data from the dataTransfer
  const todoId = event.dataTransfer?.getData('text/plain');
  const todoJson = event.dataTransfer?.getData('application/json');
  console.log('Drop dataTransfer todoId:', todoId, 'todoJson:', todoJson);
  
  if (todoJson) {
    try {
      const todo = JSON.parse(todoJson) as Todo;
      console.log('Parsed todo from dataTransfer:', todo.title, 'Status:', todo.status);
      
      if (todo.status !== props.status) {
        console.log('Emitting drop event:', todo.title, '->', props.status);
        emit('drop', todo, props.status);
      } else {
        console.log('Cannot drop to same status');
      }
    } catch (error) {
      console.error('Failed to parse todo from dataTransfer:', error);
    }
  } else {
    console.log('No todo data in dataTransfer');
  }
};

// Global drag end handler to reset all drag states
const handleGlobalDragEnd = () => {
  isDragOver.value = false;
  draggedTodo.value = null;
};

onMounted(() => {
  document.addEventListener('dragend', handleGlobalDragEnd);
});

onUnmounted(() => {
  document.removeEventListener('dragend', handleGlobalDragEnd);
});
</script>

<style scoped>
.todo-list-enter-active,
.todo-list-leave-active {
  transition: all 0.3s ease;
}

.todo-list-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.todo-list-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

.todo-list-move {
  transition: transform 0.3s ease;
}
</style>
