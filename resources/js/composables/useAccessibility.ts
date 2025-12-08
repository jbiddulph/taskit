/**
 * Accessibility composable for keyboard navigation and ARIA attributes
 */
import { onMounted, onUnmounted, ref, type Ref } from 'vue';

/**
 * Handle keyboard navigation for modals and dropdowns
 */
export function useKeyboardNavigation() {
  const handleEscape = (callback: () => void) => {
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        callback();
      }
    };

    onMounted(() => {
      document.addEventListener('keydown', handler);
    });

    onUnmounted(() => {
      document.removeEventListener('keydown', handler);
    });
  };

  const handleEnter = (callback: () => void, element: HTMLElement | null) => {
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Enter' && event.target === element) {
        event.preventDefault();
        callback();
      }
    };

    if (element) {
      element.addEventListener('keydown', handler);
      onUnmounted(() => {
        element.removeEventListener('keydown', handler);
      });
    }
  };

  return {
    handleEscape,
    handleEnter,
  };
}

/**
 * Focus trap for modals
 */
export function useFocusTrap(elementRef: Ref<HTMLElement | null>) {
  const focusableSelectors = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(', ');

  const getFocusableElements = (): HTMLElement[] => {
    if (!elementRef.value) return [];
    const elements = Array.from(
      elementRef.value.querySelectorAll(focusableSelectors)
    ) as HTMLElement[];
    return elements.filter(
      (el) => !el.hasAttribute('disabled') && el.offsetParent !== null
    );
  };

  const trapFocus = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return;

    const focusableElements = getFocusableElements();
    if (focusableElements.length === 0) return;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      }
    } else {
      // Tab
      if (document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  };

  onMounted(() => {
    if (elementRef.value) {
      elementRef.value.addEventListener('keydown', trapFocus);
      // Focus first element when trap is activated
      const focusableElements = getFocusableElements();
      if (focusableElements.length > 0) {
        focusableElements[0].focus();
      }
    }
  });

  onUnmounted(() => {
    if (elementRef.value) {
      elementRef.value.removeEventListener('keydown', trapFocus);
    }
  });
}

/**
 * Screen reader announcements
 */
export function useScreenReader() {
  const announceRef = ref<HTMLElement | null>(null);

  const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
    if (!announceRef.value) {
      // Create aria-live region if it doesn't exist
      const region = document.createElement('div');
      region.setAttribute('role', 'status');
      region.setAttribute('aria-live', priority);
      region.setAttribute('aria-atomic', 'true');
      region.className = 'sr-only';
      region.id = 'screen-reader-announcements';
      document.body.appendChild(region);
      announceRef.value = region;
    }

    // Clear previous announcement
    announceRef.value.textContent = '';
    
    // Announce new message after a brief delay
    setTimeout(() => {
      if (announceRef.value) {
        announceRef.value.textContent = message;
      }
    }, 100);
  };

  return {
    announce,
  };
}

/**
 * Get accessible label for icon-only buttons
 */
export function getIconButtonLabel(iconName: string, action?: string): string {
  const iconLabels: Record<string, string> = {
    Plus: 'Add',
    Trash2: 'Delete',
    Edit3: 'Edit',
    X: 'Close',
    Check: 'Confirm',
    Bell: 'Notifications',
    Search: 'Search',
    Calendar: 'Calendar',
    Activity: 'Activity feed',
    Mic: 'Voice record',
    Filter: 'Filter',
    Settings: 'Settings',
    Menu: 'Menu',
    ChevronDown: 'Expand',
    ChevronUp: 'Collapse',
    ArrowRight: 'Next',
    ArrowLeft: 'Previous',
  };

  const baseLabel = iconLabels[iconName] || iconName;
  return action ? `${baseLabel} ${action}` : baseLabel;
}

