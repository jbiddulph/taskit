<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup
      name="notification"
      tag="div"
      class="space-y-2"
    >
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'flex items-center gap-3 p-4 rounded-lg shadow-lg border-l-4 min-w-80 max-w-md',
          notification.type === 'success' ? 'bg-green-500 border-green-600 text-white dark:bg-green-600 dark:border-green-700' :
          notification.type === 'error' ? 'bg-red-500 border-red-600 text-white dark:bg-red-600 dark:border-red-700' :
          notification.type === 'warning' ? 'bg-yellow-500 border-yellow-600 text-white dark:bg-yellow-600 dark:border-yellow-700' :
          'bg-blue-500 border-blue-600 text-white dark:bg-blue-600 dark:border-blue-700'
        ]"
      >
        <!-- Icon -->
        <div class="flex-shrink-0">
          <Icon
            :name="getIconName(notification.type)"
            class="w-5 h-5 text-white"
          />
        </div>
        
        <!-- Content -->
        <div class="flex-1">
          <p class="font-medium">{{ notification.title }}</p>
          <p v-if="notification.message" class="text-sm opacity-90">{{ notification.message }}</p>
        </div>
        
        <!-- Close button -->
        <button
          @click="removeNotification(notification.id)"
          class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          <Icon name="X" class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';

export interface Notification {
  id: string;
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message?: string;
  duration?: number;
}

const notifications = ref<Notification[]>([]);

const getIconName = (type: string) => {
  switch (type) {
    case 'success': return 'CheckCircle';
    case 'error': return 'XCircle';
    case 'warning': return 'AlertTriangle';
    case 'info': return 'Info';
    default: return 'Info';
  }
};

const addNotification = (notification: Omit<Notification, 'id'>) => {
  const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
  const newNotification: Notification = {
    id,
    ...notification,
    duration: notification.duration || 5000
  };
  
  notifications.value.push(newNotification);
  
  // Auto-remove after duration
  if (newNotification.duration > 0) {
    setTimeout(() => {
      removeNotification(id);
    }, newNotification.duration);
  }
  
  return id;
};

const removeNotification = (id: string) => {
  const index = notifications.value.findIndex(n => n.id === id);
  if (index > -1) {
    notifications.value.splice(index, 1);
  }
};

const clearAll = () => {
  notifications.value = [];
};

// Expose methods for global use
defineExpose({
  addNotification,
  removeNotification,
  clearAll
});

// Make it globally available
onMounted(() => {
  (window as any).$notify = addNotification;
});
</script>

<style scoped>
.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}
</style>
