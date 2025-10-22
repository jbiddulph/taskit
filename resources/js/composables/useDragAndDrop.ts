import { ref, computed } from 'vue';

export interface DragState {
  isDragging: boolean;
  draggedItem: any;
  dragOverItem: any;
  dragOverIndex: number | null;
  dropZone: string | null;
  snapToGrid: boolean;
}

export interface DropZone {
  id: string;
  element: HTMLElement;
  accepts: string[];
  onDrop: (item: any, index?: number) => void;
  onDragOver?: (item: any, index?: number) => void;
  onDragLeave?: () => void;
}

export function useDragAndDrop() {
  const dragState = ref<DragState>({
    isDragging: false,
    draggedItem: null,
    dragOverItem: null,
    dragOverIndex: null,
    dropZone: null,
    snapToGrid: true
  });

  const dropZones = ref<Map<string, DropZone>>(new Map());

  const isDragging = computed(() => dragState.value.isDragging);
  const draggedItem = computed(() => dragState.value.draggedItem);

  const registerDropZone = (zone: DropZone) => {
    dropZones.value.set(zone.id, zone);
  };

  const unregisterDropZone = (id: string) => {
    dropZones.value.delete(id);
  };

  const startDrag = (item: any, event: DragEvent) => {
    dragState.value.isDragging = true;
    dragState.value.draggedItem = item;
    
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('application/json', JSON.stringify(item));
      
      // Add visual feedback
      if (event.target instanceof HTMLElement) {
        event.target.style.opacity = '0.5';
        event.target.style.transform = 'rotate(5deg)';
      }
    }
  };

  const handleDragOver = (event: DragEvent, dropZoneId: string, index?: number) => {
    event.preventDefault();
    
    const dropZone = dropZones.value.get(dropZoneId);
    if (!dropZone) return;

    // Check if this drop zone accepts the dragged item
    if (dragState.value.draggedItem && !dropZone.accepts.includes(dragState.value.draggedItem.type)) {
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'none';
      }
      return;
    }

    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = 'move';
    }

    dragState.value.dropZone = dropZoneId;
    dragState.value.dragOverIndex = index ?? null;

    // Add visual feedback to drop zone
    if (dropZone.element) {
      dropZone.element.classList.add('drag-over');
      
      // Add snap-to-grid effect
      if (dragState.value.snapToGrid && index !== undefined) {
        const items = dropZone.element.querySelectorAll('.draggable-item');
        items.forEach((item, i) => {
          if (i === index) {
            item.classList.add('snap-target');
          } else {
            item.classList.remove('snap-target');
          }
        });
      }
    }

    // Call drop zone's drag over handler
    if (dropZone.onDragOver) {
      dropZone.onDragOver(dragState.value.draggedItem, index);
    }
  };

  const handleDragLeave = (event: DragEvent, dropZoneId: string) => {
    const dropZone = dropZones.value.get(dropZoneId);
    if (!dropZone) return;

    // Remove visual feedback
    if (dropZone.element) {
      dropZone.element.classList.remove('drag-over');
      
      // Remove snap-to-grid effect
      const items = dropZone.element.querySelectorAll('.draggable-item');
      items.forEach(item => {
        item.classList.remove('snap-target');
      });
    }

    // Call drop zone's drag leave handler
    if (dropZone.onDragLeave) {
      dropZone.onDragLeave();
    }
  };

  const handleDrop = (event: DragEvent, dropZoneId: string, index?: number) => {
    event.preventDefault();
    
    const dropZone = dropZones.value.get(dropZoneId);
    if (!dropZone || !dragState.value.draggedItem) return;

    // Remove visual feedback
    if (dropZone.element) {
      dropZone.element.classList.remove('drag-over');
      
      const items = dropZone.element.querySelectorAll('.draggable-item');
      items.forEach(item => {
        item.classList.remove('snap-target');
      });
    }

    // Call drop zone's drop handler
    dropZone.onDrop(dragState.value.draggedItem, index);

    // Reset drag state
    endDrag();
  };

  const endDrag = () => {
    // Reset visual feedback
    if (dragState.value.draggedItem) {
      const draggedElement = document.querySelector('.dragging');
      if (draggedElement instanceof HTMLElement) {
        draggedElement.style.opacity = '';
        draggedElement.style.transform = '';
        draggedElement.classList.remove('dragging');
      }
    }

    // Reset drag state
    dragState.value = {
      isDragging: false,
      draggedItem: null,
      dragOverItem: null,
      dragOverIndex: null,
      dropZone: null,
      snapToGrid: true
    };
  };

  const toggleSnapToGrid = () => {
    dragState.value.snapToGrid = !dragState.value.snapToGrid;
  };

  const getDropZone = (id: string) => {
    return dropZones.value.get(id);
  };

  const isDropZoneActive = (id: string) => {
    return dragState.value.dropZone === id;
  };

  const isItemBeingDragged = (item: any) => {
    return dragState.value.draggedItem === item;
  };

  return {
    // State
    dragState,
    isDragging,
    draggedItem,

    // Actions
    startDrag,
    handleDragOver,
    handleDragLeave,
    handleDrop,
    endDrag,
    registerDropZone,
    unregisterDropZone,
    toggleSnapToGrid,
    getDropZone,
    isDropZoneActive,
    isItemBeingDragged
  };
}
