<template>
  <div v-if="shouldShowPrompt" class="pwa-install-prompt">
    <div class="install-content">
      <div class="install-icon">
        <Icon name="Download" class="w-8 h-8 text-blue-600" />
      </div>
      
      <div class="install-text">
        <h3 class="install-title">Install ZapTask</h3>
        <p class="install-description">
          Get quick access to your tasks with our app. Install ZapTask for a better experience.
        </p>
      </div>
      
      <div class="install-actions">
        <button
          @click="installPWA"
          class="install-button"
          :disabled="isInstalling"
        >
          <Icon v-if="isInstalling" name="Loader" class="w-4 h-4 animate-spin" />
          <Icon v-else name="Download" class="w-4 h-4" />
          {{ isInstalling ? 'Installing...' : 'Install' }}
        </button>
        
        <button
          @click="dismissPrompt"
          class="dismiss-button"
        >
          <Icon name="X" class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <!-- iOS Install Instructions -->
    <div v-if="isIOS && !isStandalone" class="ios-instructions">
      <div class="instructions-content">
        <Icon name="Smartphone" class="w-6 h-6 text-gray-600" />
        <div class="instructions-text">
          <h4 class="instructions-title">Install on iOS</h4>
          <p class="instructions-description">
            Tap the Share button <Icon name="Share" class="w-4 h-4 inline" /> 
            in Safari, then "Add to Home Screen"
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { usePWA } from '@/composables/usePWA';

const emit = defineEmits<{
  installed: [];
  dismissed: [];
}>();

const {
  isInstalled,
  isStandalone,
  canInstall,
  installPWA: installPWAComposable,
  addToHomeScreen
} = usePWA();

const isInstalling = ref(false);
const isDismissed = ref(false);

const isIOS = computed(() => {
  return /iPad|iPhone|iPod/.test(navigator.userAgent);
});

const installPWA = async () => {
  try {
    isInstalling.value = true;
    
    if (isIOS.value) {
      addToHomeScreen();
    } else {
      await installPWAComposable();
    }
    
    emit('installed');
  } catch (error) {
    console.error('Installation failed:', error);
  } finally {
    isInstalling.value = false;
  }
};

const dismissPrompt = () => {
  isDismissed.value = true;
  emit('dismissed');
  
  // Remember dismissal for this session
  sessionStorage.setItem('pwa-prompt-dismissed', 'true');
};

// Check if prompt was dismissed in this session
const wasDismissed = computed(() => {
  return sessionStorage.getItem('pwa-prompt-dismissed') === 'true';
});

// Show prompt only if not dismissed and conditions are met
const shouldShowPrompt = computed(() => {
  return canInstall.value && 
         !isInstalled.value && 
         !isDismissed.value && 
         !wasDismissed.value;
});
</script>

<style scoped>
.pwa-install-prompt {
  position: fixed;
  bottom: 1rem;
  left: 1rem;
  right: 1rem;
  z-index: 1000;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  overflow: hidden;
  animation: slideUp 0.3s ease-out;
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

.install-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
}

.install-icon {
  flex-shrink: 0;
  width: 3rem;
  height: 3rem;
  background: #eff6ff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.install-text {
  flex: 1;
  min-width: 0;
}

.install-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.install-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

.install-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.install-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.install-button:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.install-button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}

.dismiss-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.dismiss-button:hover {
  background: #e5e7eb;
  color: #374151;
}

.ios-instructions {
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  padding: 1rem;
}

.instructions-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.instructions-text {
  flex: 1;
}

.instructions-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.25rem 0;
}

.instructions-description {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .pwa-install-prompt {
    left: 0.5rem;
    right: 0.5rem;
    bottom: 0.5rem;
  }
  
  .install-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .install-actions {
    width: 100%;
    justify-content: space-between;
  }
  
  .install-button {
    flex: 1;
    justify-content: center;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .pwa-install-prompt {
    background: #1f2937;
    border-color: #374151;
  }
  
  .install-title {
    color: #f9fafb;
  }
  
  .install-description {
    color: #d1d5db;
  }
  
  .dismiss-button {
    background: #374151;
    color: #9ca3af;
  }
  
  .dismiss-button:hover {
    background: #4b5563;
    color: #d1d5db;
  }
  
  .ios-instructions {
    background: #374151;
    border-color: #4b5563;
  }
  
  .instructions-title {
    color: #f9fafb;
  }
  
  .instructions-description {
    color: #d1d5db;
  }
}
</style>
