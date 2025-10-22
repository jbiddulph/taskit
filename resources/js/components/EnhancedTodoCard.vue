<template>
  <div
    :class="[
      'enhanced-todo-card',
      {
        'dragging': isDragging,
        'drag-over': isDragOver,
        'selected': isSelected,
        'priority-high': todo.priority === 'High',
        'priority-critical': todo.priority === 'Critical',
        'overdue': isOverdue
      }
    ]"
    :draggable="!isSelectMode"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
    @click="handleCardClick"
  >
    <!-- Selection Checkbox -->
    <div v-if="isSelectMode" class="selection-checkbox">
      <input
        type="checkbox"
        :checked="isSelected"
        @change="toggleSelection"
        class="checkbox"
      />
    </div>

    <!-- Drag Handle -->
    <div v-if="!isSelectMode" class="drag-handle">
      <Icon name="GripVertical" class="w-4 h-4 text-gray-400" />
    </div>

    <!-- Priority Indicator -->
    <div class="priority-indicator" :class="`priority-${todo.priority.toLowerCase()}`"></div>

    <!-- Todo Content -->
    <div class="todo-content">
      <div class="todo-header">
        <h3 class="todo-title">{{ todo.title }}</h3>
        <div class="todo-actions">
          <button
            v-if="!isSelectMode"
            @click.stop="editTodo"
            class="action-button"
            title="Edit"
          >
            <Icon name="Edit" class="w-4 h-4" />
          </button>
          <button
            v-if="!isSelectMode"
            @click.stop="deleteTodo"
            class="action-button delete"
            title="Delete"
          >
            <Icon name="Trash2" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <p v-if="todo.description" class="todo-description">
        {{ todo.description }}
      </p>

      <div class="todo-meta">
        <div class="meta-item" v-if="todo.assignee">
          <Icon name="User" class="w-3 h-3" />
          <span>{{ todo.assignee }}</span>
        </div>
        
        <div class="meta-item" v-if="todo.due_date">
          <Icon name="Calendar" class="w-3 h-3" />
          <span :class="{ 'overdue': isOverdue }">
            {{ formatDueDate(todo.due_date) }}
          </span>
        </div>
        
        <div class="meta-item" v-if="todo.story_points">
          <Icon name="Target" class="w-3 h-3" />
          <span>{{ todo.story_points }} pts</span>
        </div>
      </div>

      <div v-if="todo.tags && todo.tags.length > 0" class="todo-tags">
        <span
          v-for="tag in todo.tags"
          :key="tag"
          class="tag"
        >
          {{ tag }}
        </span>
      </div>

      <div class="todo-footer">
        <div class="todo-type">
          <span class="type-badge" :class="`type-${todo.type?.toLowerCase()}`">
            {{ todo.type }}
          </span>
        </div>
        
        <div class="todo-stats">
          <span v-if="todo.comments_count > 0" class="stat">
            <Icon name="MessageCircle" class="w-3 h-3" />
            {{ todo.comments_count }}
          </span>
          <span v-if="todo.attachments_count > 0" class="stat">
            <Icon name="Paperclip" class="w-3 h-3" />
            {{ todo.attachments_count }}
          </span>
        </div>
      </div>
    </div>

    <!-- Drag Preview -->
    <div v-if="isDragging" class="drag-preview">
      <div class="preview-content">
        <h4>{{ todo.title }}</h4>
        <p>{{ todo.description }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useDragAndDrop } from '@/composables/useDragAndDrop';

interface Props {
  todo: any;
  isSelectMode?: boolean;
  isSelected?: boolean;
  isDragOver?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isSelectMode: false,
  isSelected: false,
  isDragOver: false
});

const emit = defineEmits<{
  dragStart: [todo: any, event: DragEvent];
  dragEnd: [todo: any, event: DragEvent];
  click: [todo: any];
  edit: [todo: any];
  delete: [todo: any];
  select: [todo: any];
}>();

const { isDragging, draggedItem } = useDragAndDrop();

const isOverdue = computed(() => {
  if (!props.todo.due_date) return false;
  return new Date(props.todo.due_date) < new Date();
});

const handleDragStart = (event: DragEvent) => {
  if (props.isSelectMode) return;
  emit('dragStart', props.todo, event);
};

const handleDragEnd = (event: DragEvent) => {
  if (props.isSelectMode) return;
  emit('dragEnd', props.todo, event);
};

const handleCardClick = () => {
  if (props.isSelectMode) {
    emit('select', props.todo);
  } else {
    emit('click', props.todo);
  }
};

const toggleSelection = () => {
  emit('select', props.todo);
};

const editTodo = () => {
  emit('edit', props.todo);
};

const deleteTodo = () => {
  emit('delete', props.todo);
};

const formatDueDate = (date: string) => {
  const dueDate = new Date(date);
  const now = new Date();
  const diffTime = dueDate.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return 'Tomorrow';
  if (diffDays === -1) return 'Yesterday';
  if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
  if (diffDays <= 7) return `In ${diffDays} days`;
  
  return dueDate.toLocaleDateString();
};
</script>

<style scoped>
.enhanced-todo-card {
  position: relative;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.enhanced-todo-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.enhanced-todo-card.dragging {
  opacity: 0.5;
  transform: rotate(5deg) scale(1.05);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.enhanced-todo-card.drag-over {
  border-color: #3b82f6;
  background: #eff6ff;
  transform: scale(1.02);
}

.enhanced-todo-card.selected {
  border-color: #3b82f6;
  background: #eff6ff;
}

.enhanced-todo-card.priority-high {
  border-left: 4px solid #f59e0b;
}

.enhanced-todo-card.priority-critical {
  border-left: 4px solid #ef4444;
}

.enhanced-todo-card.overdue {
  border-color: #ef4444;
  background: #fef2f2;
}

.selection-checkbox {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  z-index: 10;
}

.checkbox {
  width: 1.25rem;
  height: 1.25rem;
  accent-color: #3b82f6;
}

.drag-handle {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  cursor: grab;
  opacity: 0;
  transition: opacity 0.2s;
}

.enhanced-todo-card:hover .drag-handle {
  opacity: 1;
}

.drag-handle:active {
  cursor: grabbing;
}

.priority-indicator {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  border-radius: 8px 0 0 8px;
}

.priority-low {
  background: #10b981;
}

.priority-medium {
  background: #3b82f6;
}

.priority-high {
  background: #f59e0b;
}

.priority-critical {
  background: #ef4444;
}

.todo-content {
  margin-left: 1rem;
}

.todo-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.todo-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
  line-height: 1.4;
  flex: 1;
  margin-right: 0.5rem;
}

.todo-actions {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.enhanced-todo-card:hover .todo-actions {
  opacity: 1;
}

.action-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 4px;
  background: #f3f4f6;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.action-button:hover {
  background: #e5e7eb;
  color: #374151;
}

.action-button.delete:hover {
  background: #fef2f2;
  color: #ef4444;
}

.todo-description {
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0 0 0.75rem 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.todo-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.meta-item.overdue {
  color: #ef4444;
  font-weight: 500;
}

.todo-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.tag {
  padding: 0.125rem 0.5rem;
  background: #f3f4f6;
  color: #374151;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.todo-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.type-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.type-bug {
  background: #fef2f2;
  color: #dc2626;
}

.type-feature {
  background: #f0f9ff;
  color: #0369a1;
}

.type-task {
  background: #f0fdf4;
  color: #16a34a;
}

.type-story {
  background: #faf5ff;
  color: #9333ea;
}

.type-epic {
  background: #fff7ed;
  color: #ea580c;
}

.todo-stats {
  display: flex;
  gap: 0.75rem;
}

.stat {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.drag-preview {
  position: fixed;
  top: -1000px;
  left: -1000px;
  pointer-events: none;
  z-index: 9999;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  max-width: 300px;
}

.preview-content h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
}

.preview-content p {
  margin: 0;
  font-size: 0.75rem;
  color: #6b7280;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Animation for drag operations */
@keyframes dragPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}

.enhanced-todo-card.dragging {
  animation: dragPulse 0.5s ease-in-out infinite;
}

/* Snap-to-grid effect */
.snap-target {
  border: 2px dashed #3b82f6 !important;
  background: #eff6ff !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .enhanced-todo-card {
    padding: 0.75rem;
  }
  
  .todo-content {
    margin-left: 0.75rem;
  }
  
  .todo-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .todo-actions {
    opacity: 1;
    margin-top: 0.5rem;
  }
}
</style>
