<template>
  <div v-if="isSelectMode && hasSelection" class="bulk-operations-bar">
    <div class="bulk-info">
      <Icon name="CheckSquare" class="w-5 h-5 text-blue-600" />
      <span class="text-sm font-medium text-gray-900">
        {{ selectedCount }} item{{ selectedCount > 1 ? 's' : '' }} selected
      </span>
    </div>

    <div class="bulk-actions">
      <!-- Status Change -->
      <div class="action-group">
        <label class="action-label">Status:</label>
        <select 
          v-model="selectedStatus" 
          @change="bulkChangeStatus"
          class="action-select"
          :disabled="isProcessing"
        >
          <option value="">Select status...</option>
          <option value="todo">To Do</option>
          <option value="in-progress">In Progress</option>
          <option value="qa-testing">Q&A/Testing</option>
          <option value="done">Done</option>
        </select>
      </div>

      <!-- Priority Change -->
      <div class="action-group">
        <label class="action-label">Priority:</label>
        <select 
          v-model="selectedPriority" 
          @change="bulkChangePriority"
          class="action-select"
          :disabled="isProcessing"
        >
          <option value="">Select priority...</option>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
          <option value="Critical">Critical</option>
        </select>
      </div>

      <!-- Assignee Change -->
      <div class="action-group">
        <label class="action-label">Assignee:</label>
        <select 
          v-model="selectedAssignee" 
          @change="bulkChangeAssignee"
          class="action-select"
          :disabled="isProcessing"
        >
          <option value="">Select assignee...</option>
          <option v-for="assignee in availableAssignees" :key="assignee" :value="assignee">
            {{ assignee }}
          </option>
          <option value="unassigned">Unassigned</option>
        </select>
      </div>

      <!-- Delete Action -->
      <button
        @click="bulkDelete"
        class="action-button delete-button"
        :disabled="isProcessing"
      >
        <Icon name="Trash2" class="w-4 h-4" />
        Delete
      </button>
    </div>

    <div class="bulk-controls">
      <button
        @click="clearSelection"
        class="control-button"
        :disabled="isProcessing"
      >
        Clear
      </button>
      <button
        @click="toggleSelectMode"
        class="control-button primary"
        :disabled="isProcessing"
      >
        Done
      </button>
    </div>

    <!-- Processing indicator -->
    <div v-if="isProcessing" class="processing-indicator">
      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
      <span class="text-sm text-gray-600">Processing...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useBulkOperations } from '@/composables/useBulkOperations';
import { todoApi } from '@/services/todoApi';

interface Props {
  availableAssignees: string[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  refresh: [];
}>();

const {
  selectedCount,
  hasSelection,
  isSelectMode,
  isProcessing,
  clearSelection,
  toggleSelectMode,
  getSelectedIds
} = useBulkOperations();

const selectedStatus = ref('');
const selectedPriority = ref('');
const selectedAssignee = ref('');

const bulkChangeStatus = async () => {
  if (!selectedStatus.value) return;
  
  try {
    const selectedIds = getSelectedIds();
    await todoApi.bulkUpdateStatus(selectedIds, selectedStatus.value);
    selectedStatus.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update status:', error);
  }
};

const bulkChangePriority = async () => {
  if (!selectedPriority.value) return;
  
  try {
    const selectedIds = getSelectedIds();
    await todoApi.bulkUpdatePriority(selectedIds, selectedPriority.value);
    selectedPriority.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update priority:', error);
  }
};

const bulkChangeAssignee = async () => {
  try {
    const selectedIds = getSelectedIds();
    const assignee = selectedAssignee.value === 'unassigned' ? null : selectedAssignee.value;
    await todoApi.bulkUpdateAssignee(selectedIds, assignee);
    selectedAssignee.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update assignee:', error);
  }
};

const bulkDelete = async () => {
  const confirmed = confirm(
    `Are you sure you want to delete ${selectedCount.value} item${selectedCount.value > 1 ? 's' : ''}? This action cannot be undone.`
  );
  
  if (!confirmed) return;
  
  try {
    const selectedIds = getSelectedIds();
    await todoApi.bulkDelete(selectedIds);
    emit('refresh');
  } catch (error) {
    console.error('Failed to delete items:', error);
  }
};
</script>

<style scoped>
.bulk-operations-bar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: white;
  border-bottom: 2px solid #3b82f6;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: fit-content;
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.action-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.action-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
}

.action-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  min-width: 120px;
}

.action-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.action-select:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
}

.delete-button {
  background: #ef4444;
  color: white;
  border: none;
}

.delete-button:hover:not(:disabled) {
  background: #dc2626;
}

.delete-button:disabled {
  background: #fca5a5;
  cursor: not-allowed;
}

.bulk-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.control-button {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
}

.control-button:hover:not(:disabled) {
  background: #f9fafb;
}

.control-button.primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.control-button.primary:hover:not(:disabled) {
  background: #2563eb;
}

.control-button:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.processing-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}

/* Responsive design */
@media (max-width: 768px) {
  .bulk-operations-bar {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .action-group {
    justify-content: space-between;
  }
  
  .action-select {
    min-width: auto;
    flex: 1;
  }
}
</style>
