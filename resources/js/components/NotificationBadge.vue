<template>
  <div class="relative">
    <button
      @click="toggleNotifications"
      class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors"
    >
      <Icon name="Bell" class="w-5 h-5" />
      
      <!-- Notification Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium animate-pulse"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Notification Dropdown -->
    <div
      v-if="showNotifications"
      class="absolute top-full right-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Notifications
          </h3>
          <div class="flex items-center gap-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
              Mark all read
            </button>
            <button
              @click="showNotifications = false"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Icon name="X" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="loading" class="p-4 text-center text-gray-500">
          <Icon name="Loader2" class="w-6 h-6 animate-spin mx-auto mb-2" />
          Loading notifications...
        </div>
        
        <div v-else-if="notifications.length === 0" class="p-4 text-center text-gray-500">
          No notifications yet
        </div>
        
        <div v-else>
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
            :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start gap-3">
              <!-- Notification Icon -->
              <div
                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                :class="getNotificationIconClass(notification.type)"
              >
                <Icon :name="getNotificationIcon(notification.type)" class="w-4 h-4" />
              </div>
              
              <!-- Notification Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ notification.title }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {{ notification.message }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                      {{ formatDate(notification.created_at) }}
                    </p>
                  </div>
                  
                  <!-- Actions -->
                  <div class="flex items-center gap-1 ml-2" @click.stop>
                    <button
                      v-if="!notification.is_read"
                      @click="markAsRead(notification.id)"
                      class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                      title="Mark as read"
                    >
                      <Icon name="Check" class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteNotification(notification.id)"
                      class="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                      title="Delete"
                    >
                      <Icon name="Trash2" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="notifications.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
        <button
          @click="loadMoreNotifications"
          class="w-full text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Load more notifications
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { notificationApi, type Notification } from '@/services/notificationApi';
import { realtimeService } from '@/services/realtimeService';

const showNotifications = ref(false);
const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
const currentPage = ref(1);

// Real-time notification handling
let unsubscribeNotifications: (() => void) | null = null;

const handleRealtimeNotification = (event: any) => {
  if (event.type === 'new_notification') {
    const notification = event.data;
    
    // Check if this is a chat message notification and if chat is currently open with sender
    const isMessageNotification = notification.data?.message_id && notification.data?.sender_id;
    const isChatOpen = isMessageNotification && isChatOpenWithUser(notification.data.sender_id);
    
    // Only process notification if chat is not open with the sender
    if (!isChatOpen) {
      // Add to the beginning of notifications list if dropdown is open
      if (showNotifications.value) {
        notifications.value.unshift(notification);
      }
      
      // Update unread count
      loadUnreadCount();
    } else {
      console.log('Chat is open with sender, skipping notification badge update');
    }
  }
};

/**
 * Check if chat is currently open with a specific user
 */
const isChatOpenWithUser = (userId: number): boolean => {
  // Check if there's an active chat state
  const chatState = (window as any).currentChatState;
  return chatState?.isOpen && chatState?.otherUserId === userId;
};

const loadNotifications = async () => {
  try {
    loading.value = true;
    const response = await notificationApi.getNotifications(currentPage.value);
    if (currentPage.value === 1) {
      notifications.value = response.data;
    } else {
      notifications.value.push(...response.data);
    }
  } catch (error) {
    console.error('Failed to load notifications:', error);
  } finally {
    loading.value = false;
  }
};

const loadUnreadCount = async () => {
  try {
    const response = await notificationApi.getUnreadCount();
    unreadCount.value = response.count;
  } catch (error) {
    console.error('Failed to load unread count:', error);
  }
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value && notifications.value.length === 0) {
    loadNotifications();
  }
};

const markAsRead = async (notificationId: number) => {
  try {
    await notificationApi.markAsRead(notificationId);
    const notification = notifications.value.find(n => n.id === notificationId);
    if (notification) {
      notification.is_read = true;
      notification.read_at = new Date().toISOString();
    }
    await loadUnreadCount();
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await notificationApi.markAllAsRead();
    notifications.value.forEach(notification => {
      notification.is_read = true;
      notification.read_at = new Date().toISOString();
    });
    unreadCount.value = 0;
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  }
};

const deleteNotification = async (notificationId: number) => {
  try {
    await notificationApi.deleteNotification(notificationId);
    notifications.value = notifications.value.filter(n => n.id !== notificationId);
    await loadUnreadCount();
  } catch (error) {
    console.error('Failed to delete notification:', error);
  }
};

const handleNotificationClick = async (notification: Notification) => {
  // Mark as read if unread
  if (!notification.is_read) {
    await markAsRead(notification.id);
  }

  // Handle different notification types
  const data = notification.data || {};

  // Mention notifications - navigate to the Todo
  if (notification.type === 'mention' && data.todo_id) {
    // Close notification dropdown
    showNotifications.value = false;
    
    // Navigate to the Todo with comment ID to highlight the specific mention
    const commentId = data.comment_id ? `?highlight=${data.comment_id}` : '';
    window.location.href = `/todos/${data.todo_id}${commentId}`;
    return;
  }

  // Message notifications - open chat with sender
  if (notification.data?.sender_id) {
    const senderId = notification.data.sender_id;
    const senderName = notification.data.sender_name || 'Unknown User';
    
    // Fetch user details and open chat
    try {
      // Emit event to open chat with this user
      window.dispatchEvent(new CustomEvent('openChat', {
        detail: {
          user: {
            id: senderId,
            name: senderName,
            email: ''
          }
        }
      }));
      
      // Close notification dropdown
      showNotifications.value = false;
    } catch (error) {
      console.error('Failed to open chat:', error);
    }
  }
};

const loadMoreNotifications = async () => {
  currentPage.value++;
  await loadNotifications();
};

const getNotificationIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    'info': 'Info',
    'warning': 'AlertTriangle',
    'error': 'XCircle',
    'success': 'CheckCircle'
  };
  return iconMap[type] || 'Bell';
};

const getNotificationIconClass = (type: string): string => {
  const classMap: Record<string, string> = {
    'info': 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    'warning': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    'error': 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
    'success': 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
  };
  return classMap[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffTime = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
  const diffMinutes = Math.floor(diffTime / (1000 * 60));

  if (diffMinutes < 1) return 'Just now';
  if (diffMinutes < 60) return `${diffMinutes}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return date.toLocaleDateString();
};

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    showNotifications.value = false;
  }
};

onMounted(() => {
  loadUnreadCount();
  document.addEventListener('click', handleClickOutside);
  
  // Subscribe to real-time notifications
  unsubscribeNotifications = realtimeService.onNotification(handleRealtimeNotification);
  
  // Refresh unread count every 30 seconds
  const interval = setInterval(loadUnreadCount, 30000);
  
  onUnmounted(() => {
    clearInterval(interval);
    document.removeEventListener('click', handleClickOutside);
    
    // Unsubscribe from real-time notifications
    if (unsubscribeNotifications) {
      unsubscribeNotifications();
    }
  });
});
</script>
