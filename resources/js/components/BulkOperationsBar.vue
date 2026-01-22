<template>
  <div class="bulk-operations-bar">
    <div class="bulk-info">
      <Icon name="CheckSquare" class="w-5 h-5 text-blue-600" />
      <span class="text-sm font-medium text-gray-900">
        {{ selectedCount }} {{ t('todos.items_selected') }}
      </span>
    </div>

    <div class="bulk-actions">
      <!-- Row 1: Status and Priority -->
      <div class="action-row">
        <div class="action-group">
          <label class="action-label">{{ t('todos.status') }}:</label>
          <select 
            v-model="selectedStatus" 
            @change="bulkChangeStatus"
            class="action-select"
            :disabled="isProcessing"
          >
            <option value="">{{ t('todos.please_select') }}</option>
            <option value="todo">{{ t('todos.to_do') }}</option>
            <option value="in-progress">{{ t('todos.in_progress') }}</option>
            <option value="qa-testing">{{ t('dashboard.qa_testing') }}</option>
            <option value="done">{{ t('todos.done') }}</option>
          </select>
        </div>

        <div class="action-group">
          <label class="action-label">{{ t('todos.priority') }}:</label>
          <select 
            v-model="selectedPriority" 
            @change="bulkChangePriority"
            class="action-select"
            :disabled="isProcessing"
          >
            <option value="">{{ t('todos.please_select') }}</option>
            <option value="Low">{{ t('todos.priority_low') }}</option>
            <option value="Medium">{{ t('todos.priority_medium') }}</option>
            <option value="High">{{ t('todos.priority_high') }}</option>
            <option value="Critical">{{ t('todos.priority_critical') }}</option>
          </select>
        </div>
      </div>

      <!-- Row 2: Assignee and Type -->
      <div class="action-row">
        <div class="action-group">
          <label class="action-label">{{ t('todos.assignee') }}:</label>
          <select 
            v-model="selectedAssignee" 
            @change="bulkChangeAssignee"
            class="action-select"
            :disabled="isProcessing"
          >
            <option value="">{{ t('todos.please_select') }}</option>
            <option v-for="assignee in availableAssignees" :key="assignee" :value="assignee">
              {{ assignee }}
            </option>
            <option value="unassigned">{{ t('todos.unassigned') }}</option>
          </select>
        </div>

        <div class="action-group">
          <label class="action-label">{{ t('todos.type') }}:</label>
          <select 
            v-model="selectedType" 
            @change="bulkChangeType"
            class="action-select"
            :disabled="isProcessing"
          >
            <option value="">{{ t('todos.please_select') }}</option>
            <option value="Bug">{{ t('todos.type_bug') }}</option>
            <option value="Feature">{{ t('todos.type_feature') }}</option>
            <option value="Task">{{ t('todos.type_task') }}</option>
            <option value="Story">{{ t('todos.type_story') }}</option>
            <option value="Epic">{{ t('todos.type_epic') }}</option>
          </select>
        </div>
      </div>

      <!-- Row 3: Due Date and Tags -->
      <div class="action-row">
        <div class="action-group">
          <label class="action-label">{{ t('todos.due_date') }}:</label>
          <input
            v-model="selectedDueDate"
            @change="bulkChangeDueDate"
            type="date"
            :placeholder="t('todos.please_select')"
            class="action-input"
            :disabled="isProcessing"
          />
        </div>

        <div class="action-group">
          <label class="action-label">{{ t('todos.tags') }}:</label>
          <input
            v-model="selectedTags"
            @keyup.enter="bulkChangeTags"
            @blur="bulkChangeTags"
            type="text"
            :placeholder="t('todos.type_comma_separated')"
            class="action-input"
            :disabled="isProcessing"
          />
        </div>
      </div>

    </div>

    <div class="bulk-controls">
      <button
        @click="bulkDelete"
        class="control-button delete-button"
        :disabled="isProcessing"
      >
        {{ t('common.delete') }}
      </button>
      <button
        @click="toggleSelectMode"
        class="control-button primary"
        :disabled="isProcessing"
      >
        {{ t('todos.done_button') }}
      </button>
    </div>

    <!-- Processing indicator -->
    <div v-if="isProcessing" class="processing-indicator">
      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
      <span class="text-sm text-gray-600">{{ t('todos.processing') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { useBulkOperations } from '@/composables/useBulkOperations';
import { todoApi } from '@/services/todoApi';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface Props {
  availableAssignees: string[];
}

defineProps<Props>();

const emit = defineEmits<{
  refresh: [];
  'toggle-select-mode': [];
}>();

const {
  selectedCount,
  isProcessing,
  toggleSelectMode: composableToggleSelectMode,
  getSelectedIds
} = useBulkOperations();

// Local function that toggles select mode and emits event
const toggleSelectMode = () => {
  composableToggleSelectMode();
  emit('toggle-select-mode');
};


const selectedStatus = ref('');
const selectedPriority = ref('');
const selectedAssignee = ref('');
const selectedType = ref('');
const selectedDueDate = ref('');
const selectedTags = ref('');

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

const bulkChangeType = async () => {
  if (!selectedType.value) return;
  
  try {
    const selectedIds = getSelectedIds();
    await todoApi.bulkUpdateType(selectedIds, selectedType.value);
    selectedType.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update type:', error);
  }
};

const bulkChangeDueDate = async () => {
  if (!selectedDueDate.value) return;
  
  try {
    const selectedIds = getSelectedIds();
    await todoApi.bulkUpdateDueDate(selectedIds, selectedDueDate.value);
    selectedDueDate.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update due date:', error);
  }
};

const bulkChangeTags = async () => {
  if (!selectedTags.value.trim()) return;
  
  try {
    const selectedIds = getSelectedIds();
    const tags = selectedTags.value.split(',').map(tag => tag.trim()).filter(tag => tag);
    await todoApi.bulkUpdateTags(selectedIds, tags);
    selectedTags.value = '';
    emit('refresh');
  } catch (error) {
    console.error('Failed to update tags:', error);
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
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 50;
  background: white;
  border-top: 2px solid #3b82f6;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
  animation: slideUp 0.3s ease-out;
}

@media (min-width: 768px) {
  .bulk-operations-bar {
    align-items: center;
    padding: 1rem;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: fit-content;
}

.bulk-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.action-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  align-items: center;
}

@media (min-width: 768px) {
  .bulk-actions {
    flex-direction: row;
    align-items: center;
  }
  
  .action-row {
    display: contents;
  }
}

.action-group {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.action-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
}

.action-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.75rem;
  background: white;
  min-width: 100px;
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

.action-input {
  padding: 0.25rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.75rem;
  background: white;
  min-width: 100px;
}

.action-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.action-input:disabled {
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
  background: #ef4444 !important;
  color: white !important;
  border: none !important;
}

.delete-button:hover:not(:disabled) {
  background: #dc2626 !important;
}

.delete-button:disabled {
  background: #fca5a5 !important;
  cursor: not-allowed;
}

.bulk-controls {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.5rem;
  align-items: center;
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
