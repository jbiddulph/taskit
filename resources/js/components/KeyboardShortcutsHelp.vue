<template>
  <div v-if="showHelp" class="keyboard-shortcuts-help">
    <div class="help-overlay" @click="closeHelp"></div>
    <div class="help-modal">
      <div class="help-header">
        <h2 class="text-xl font-semibold">Keyboard Shortcuts</h2>
        <button @click="closeHelp" class="close-button">
          <Icon name="X" class="w-5 h-5" />
        </button>
      </div>
      
      <div class="help-content">
        <div v-for="(shortcuts, category) in shortcutsByCategory" :key="category" class="category-section">
          <h3 class="category-title">{{ category }}</h3>
          <div class="shortcuts-list">
            <div v-for="shortcut in shortcuts" :key="shortcut.key" class="shortcut-item">
              <div class="shortcut-keys">
                <kbd v-if="shortcut.ctrlKey || shortcut.metaKey" class="key">{{ isMac ? '⌘' : 'Ctrl' }}</kbd>
                <kbd v-if="shortcut.altKey" class="key">Alt</kbd>
                <kbd v-if="shortcut.shiftKey" class="key">Shift</kbd>
                <kbd class="key main-key">{{ shortcut.key.toUpperCase() }}</kbd>
              </div>
              <div class="shortcut-description">{{ shortcut.description }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/components/Icon.vue';

interface Props {
  showHelp: boolean;
  shortcutsByCategory: Record<string, any[]>;
}

defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

const isMac = computed(() => navigator.platform.toUpperCase().indexOf('MAC') >= 0);

const closeHelp = () => {
  emit('close');
};
</script>

<style scoped>
.keyboard-shortcuts-help {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.help-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.help-modal {
  position: relative;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 600px;
  max-height: 80vh;
  overflow: hidden;
  width: 90%;
}

.help-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.close-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 6px;
  background: #f3f4f6;
  color: #6b7280;
  transition: all 0.2s;
}

.close-button:hover {
  background: #e5e7eb;
  color: #374151;
}

.help-content {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.category-section {
  margin-bottom: 2rem;
}

.category-section:last-child {
  margin-bottom: 0;
}

.category-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.shortcuts-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.shortcut-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.shortcut-keys {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.key {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.5rem;
  height: 1.5rem;
  padding: 0 0.5rem;
  background: #374151;
  color: white;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
}

.main-key {
  background: #1f2937;
  font-weight: 600;
}

.shortcut-description {
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .help-modal {
    background: #1f2937;
    color: #f9fafb;
  }
  
  .help-header {
    border-bottom-color: #374151;
  }
  
  .close-button {
    background: #374151;
    color: #9ca3af;
  }
  
  .close-button:hover {
    background: #4b5563;
    color: #d1d5db;
  }
  
  .category-title {
    color: #f9fafb;
    border-bottom-color: #374151;
  }
  
  .shortcut-item {
    background: #374151;
    border-color: #4b5563;
  }
  
  .shortcut-description {
    color: #d1d5db;
  }
}
</style>
