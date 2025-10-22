import { onMounted, onUnmounted, ref } from 'vue';

export interface KeyboardShortcut {
  key: string;
  ctrlKey?: boolean;
  shiftKey?: boolean;
  altKey?: boolean;
  metaKey?: boolean;
  action: () => void;
  description: string;
  category: string;
}

export function useKeyboardShortcuts() {
  const shortcuts = ref<KeyboardShortcut[]>([]);
  const isEnabled = ref(true);

  const registerShortcut = (shortcut: KeyboardShortcut) => {
    shortcuts.value.push(shortcut);
  };

  const unregisterShortcut = (key: string, modifiers?: { ctrlKey?: boolean; shiftKey?: boolean; altKey?: boolean; metaKey?: boolean }) => {
    const index = shortcuts.value.findIndex(s => 
      s.key === key && 
      (!modifiers || (
        s.ctrlKey === modifiers.ctrlKey &&
        s.shiftKey === modifiers.shiftKey &&
        s.altKey === modifiers.altKey &&
        s.metaKey === modifiers.metaKey
      ))
    );
    if (index !== -1) {
      shortcuts.value.splice(index, 1);
    }
  };

  const handleKeydown = (event: KeyboardEvent) => {
    if (!isEnabled.value) return;

    // Don't trigger shortcuts when typing in inputs
    const target = event.target as HTMLElement;
    if (target && (
      target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.contentEditable === 'true' ||
      target.closest('[contenteditable="true"]')
    )) {
      return;
    }

    const matchingShortcut = shortcuts.value.find(shortcut => {
      return shortcut.key.toLowerCase() === event.key.toLowerCase() &&
        !!shortcut.ctrlKey === event.ctrlKey &&
        !!shortcut.shiftKey === event.shiftKey &&
        !!shortcut.altKey === event.altKey &&
        !!shortcut.metaKey === event.metaKey;
    });

    if (matchingShortcut) {
      event.preventDefault();
      event.stopPropagation();
      matchingShortcut.action();
    }
  };

  const enable = () => {
    isEnabled.value = true;
  };

  const disable = () => {
    isEnabled.value = false;
  };

  const getShortcutsByCategory = () => {
    const categories: Record<string, KeyboardShortcut[]> = {};
    shortcuts.value.forEach(shortcut => {
      if (!categories[shortcut.category]) {
        categories[shortcut.category] = [];
      }
      categories[shortcut.category].push(shortcut);
    });
    return categories;
  };

  onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
  });

  return {
    shortcuts,
    isEnabled,
    registerShortcut,
    unregisterShortcut,
    enable,
    disable,
    getShortcutsByCategory
  };
}
