import { ref, computed } from 'vue';

export interface BulkOperation {
  id: string;
  type: 'status' | 'assignee' | 'priority' | 'delete';
  label: string;
  icon: string;
  action: (selectedIds: number[]) => Promise<void>;
  confirmMessage?: string;
  requiresConfirmation?: boolean;
}

// Global state - shared across all components
const selectedItems = ref<Set<number>>(new Set());
const isSelectMode = ref(false);
const isProcessing = ref(false);

export function useBulkOperations() {

  const selectedCount = computed(() => selectedItems.value.size);
  const hasSelection = computed(() => selectedItems.value.size > 0);

  const toggleSelection = (id: number) => {
    if (selectedItems.value.has(id)) {
      selectedItems.value.delete(id);
    } else {
      selectedItems.value.add(id);
    }
  };

  const selectAll = (allIds: number[]) => {
    selectedItems.value = new Set(allIds);
  };

  const clearSelection = () => {
    selectedItems.value.clear();
  };

  const toggleSelectMode = () => {
    isSelectMode.value = !isSelectMode.value;
    if (!isSelectMode.value) {
      clearSelection();
    }
  };

  const executeBulkOperation = async (operation: BulkOperation) => {
    if (selectedItems.value.size === 0) return;

    const selectedIds = Array.from(selectedItems.value);

    // Show confirmation if required
    if (operation.requiresConfirmation && operation.confirmMessage) {
      const confirmed = confirm(operation.confirmMessage);
      if (!confirmed) return;
    }

    try {
      isProcessing.value = true;
      await operation.action(selectedIds);
      clearSelection();
    } catch (error) {
      console.error('Bulk operation failed:', error);
      throw error;
    } finally {
      isProcessing.value = false;
    }
  };

  const isSelected = (id: number) => selectedItems.value.has(id);

  const getSelectedIds = () => Array.from(selectedItems.value);

  return {
    // State
    selectedItems,
    isSelectMode,
    isProcessing,
    selectedCount,
    hasSelection,

    // Actions
    toggleSelection,
    selectAll,
    clearSelection,
    toggleSelectMode,
    executeBulkOperation,
    isSelected,
    getSelectedIds
  };
}
