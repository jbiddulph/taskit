/**
 * Touch-friendly drag and drop composable for mobile devices
 * Provides a better experience than HTML5 drag API on touch screens
 */
import { ref, computed, onMounted, onUnmounted, type Ref } from 'vue';

interface TouchDragState {
  isDragging: boolean;
  draggedElement: HTMLElement | null;
  startX: number;
  startY: number;
  currentX: number;
  currentY: number;
  draggedItem: any | null;
}

export function useTouchDragAndDrop() {
  const touchState = ref<TouchDragState>({
    isDragging: false,
    draggedElement: null,
    startX: 0,
    startY: 0,
    currentX: 0,
    currentY: 0,
    draggedItem: null,
  });

  const dragThreshold = 10; // Minimum distance to start drag (in pixels)

  const handleTouchStart = (event: TouchEvent, item: any, element: HTMLElement) => {
    if (event.touches.length !== 1) return; // Only handle single touch

    const touch = event.touches[0];
    touchState.value = {
      isDragging: false,
      draggedElement: element,
      startX: touch.clientX,
      startY: touch.clientY,
      currentX: touch.clientX,
      currentY: touch.clientY,
      draggedItem: item,
    };

    // Prevent scrolling while dragging
    element.style.userSelect = 'none';
    element.style.touchAction = 'none';
  };

  const handleTouchMove = (event: TouchEvent, onDragStart?: (item: any) => void) => {
    if (!touchState.value.draggedElement || event.touches.length !== 1) return;

    const touch = event.touches[0];
    const deltaX = touch.clientX - touchState.value.startX;
    const deltaY = touch.clientY - touchState.value.startY;
    const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

    touchState.value.currentX = touch.clientX;
    touchState.value.currentY = touch.clientY;

    // Start dragging if moved beyond threshold
    if (!touchState.value.isDragging && distance > dragThreshold) {
      touchState.value.isDragging = true;
      if (onDragStart && touchState.value.draggedItem) {
        onDragStart(touchState.value.draggedItem);
      }

      // Add visual feedback
      if (touchState.value.draggedElement) {
        touchState.value.draggedElement.style.opacity = '0.5';
        touchState.value.draggedElement.style.transform = 'scale(0.95)';
        touchState.value.draggedElement.style.transition = 'opacity 0.2s, transform 0.2s';
      }

      // Prevent default to avoid scrolling
      event.preventDefault();
    }

    // Update position if dragging
    if (touchState.value.isDragging && touchState.value.draggedElement) {
      touchState.value.draggedElement.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.95)`;
      event.preventDefault();
    }
  };

  const handleTouchEnd = (
    event: TouchEvent,
    onDrop?: (item: any, dropTarget: HTMLElement | null) => void
  ) => {
    if (!touchState.value.draggedElement) return;

    const element = touchState.value.draggedElement;
    const item = touchState.value.draggedItem;

    // Reset styles
    element.style.userSelect = '';
    element.style.touchAction = '';
    element.style.opacity = '';
    element.style.transform = '';
    element.style.transition = '';

    if (touchState.value.isDragging) {
      // Find drop target
      const touch = event.changedTouches[0];
      const dropTarget = document.elementFromPoint(touch.clientX, touch.clientY) as HTMLElement;

      // Find the closest drop zone
      let dropZone: HTMLElement | null = dropTarget;
      while (dropZone && !dropZone.hasAttribute('data-drop-zone')) {
        dropZone = dropZone.parentElement;
      }

      if (onDrop && item) {
        onDrop(item, dropZone);
      }

      event.preventDefault();
    }

    // Reset state
    touchState.value = {
      isDragging: false,
      draggedElement: null,
      startX: 0,
      startY: 0,
      currentX: 0,
      currentY: 0,
      draggedItem: null,
    };
  };

  const isDragging = computed(() => touchState.value.isDragging);
  const draggedItem = computed(() => touchState.value.draggedItem);

  return {
    touchState,
    isDragging,
    draggedItem,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
  };
}

