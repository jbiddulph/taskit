<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { realtimeService } from '@/services/realtimeService';

interface Message {
  id: number;
  message: string;
  sender_id: number;
  sender_name: string;
  recipient_id: number;
  recipient_name: string;
  is_read: boolean;
  created_at: string;
  created_at_human: string;
}

interface ChatUser {
  id: number;
  name: string;
  email: string;
}

interface ChatData {
  messages: Message[];
  other_user: ChatUser;
}

const isVisible = ref(false);
const isMinimized = ref(false);
const loading = ref(false);
const sending = ref(false);

// Get fresh CSRF token
const getCSRFToken = async (): Promise<string> => {
  // First try to get from meta tag
  const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (metaToken) {
    return metaToken;
  }
  
  // If not available, try to refresh it
  try {
    const response = await fetch('/sanctum/csrf-cookie', {
      method: 'GET',
      credentials: 'same-origin'
    });
    
    if (response.ok) {
      // Try again to get the token from meta tag
      const refreshedToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (refreshedToken) {
        return refreshedToken;
      }
    }
  } catch (error) {
    console.error('Failed to refresh CSRF token:', error);
  }
  
  // Fallback to any available token
  return document.querySelector('input[name="_token"]')?.getAttribute('value') || '';
};
const newMessage = ref('');
const messages = ref<Message[]>([]);
const otherUser = ref<ChatUser | null>(null);
const messagesContainer = ref<HTMLElement | null>(null);

const openChat = (user: ChatUser) => {
  otherUser.value = user;
  isVisible.value = true;
  isMinimized.value = false;
  
  // Set global chat state for notification filtering
  (window as any).currentChatState = {
    isOpen: true,
    isMinimized: false,
    otherUserId: user.id
  };
  
  // Emit chat state change
  window.dispatchEvent(new CustomEvent('chatStateChange', {
    detail: { isOpen: true, isMinimized: false }
  }));
  
  loadMessages();
};

const closeChat = () => {
  isVisible.value = false;
  isMinimized.value = false;
  messages.value = [];
  otherUser.value = null;
  newMessage.value = '';
  
  // Clear global chat state
  (window as any).currentChatState = {
    isOpen: false,
    isMinimized: false,
    otherUserId: null
  };
  
  // Emit chat state change
  window.dispatchEvent(new CustomEvent('chatStateChange', {
    detail: { isOpen: false, isMinimized: false }
  }));
};

const toggleMinimize = () => {
  isMinimized.value = !isMinimized.value;
  
  // Update global chat state
  if ((window as any).currentChatState) {
    (window as any).currentChatState.isMinimized = isMinimized.value;
  }
  
  // Emit chat state change
  window.dispatchEvent(new CustomEvent('chatStateChange', {
    detail: { isOpen: isVisible.value, isMinimized: isMinimized.value }
  }));
};

const loadMessages = async () => {
  if (!otherUser.value) return;
  
  loading.value = true;
  try {
    const response = await fetch(`/api/company-users/${otherUser.value.id}/messages`, {
      credentials: 'include', // Include cookies for session authentication
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      const chatData: ChatData = data.data;
      messages.value = chatData.messages;
      // Scroll to bottom after loading messages
      await nextTick();
      scrollToBottom();
    } else {
      console.error('Failed to load messages:', response.status, response.statusText);
      if (response.status === 403) {
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'error',
            title: 'Access Denied',
            message: 'You cannot access messages with this user.',
            duration: 3000
          });
        }
      }
    }
  } catch (error) {
    console.error('Failed to load messages:', error);
  } finally {
    loading.value = false;
  }
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || !otherUser.value || sending.value) return;
  
  sending.value = true;
  const messageText = newMessage.value.trim();
  newMessage.value = '';
  
  try {
    // Ensure CSRF cookie exists before POST
    await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'include' }).catch(() => {});
    const csrfToken = await getCSRFToken();
    const response = await fetch('/api/company-messages', {
      method: 'POST',
      credentials: 'include', // Include cookies for session authentication
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        recipient_id: otherUser.value.id,
        message: messageText
      })
    });
    
    if (response.ok) {
      const data = await response.json();
      messages.value.push(data.data);
      await nextTick();
      scrollToBottom();
      
      // Dispatch event to refresh unread counts
      window.dispatchEvent(new CustomEvent('messagesSent'));
    } else {
      // Restore message on error
      newMessage.value = messageText;
      
      // Handle specific error cases
      if (response.status === 419) {
        console.error('CSRF token mismatch - please refresh the page');
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'error',
            title: 'Authentication Error',
            message: 'Session expired. Please refresh the page and try again.',
            duration: 5000
          });
        }
      } else if (response.status === 403) {
        console.error('Unauthorized - user not in same company');
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'error',
            title: 'Permission Error',
            message: 'You do not have permission to send messages to this user.',
            duration: 5000
          });
        }
      } else {
        console.error('Failed to send message:', response.status, response.statusText);
        if ((window as any).$notify) {
          (window as any).$notify({
            type: 'error',
            title: 'Message Failed',
            message: 'Failed to send message. Please try again.',
            duration: 3000
          });
        }
      }
    }
  } catch (error) {
    // Restore message on error
    newMessage.value = messageText;
    console.error('Failed to send message:', error);
  } finally {
    sending.value = false;
  }
};

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const handleKeyPress = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
};

const handleChatOpen = (event: CustomEvent) => {
  const { user } = event.detail;
  openChat(user);
};

// Watch for new messages and scroll to bottom
watch(messages, () => {
  nextTick(() => {
    scrollToBottom();
  });
});

// Real-time message handling
let unsubscribeMessages: (() => void) | null = null;

const handleRealtimeMessage = (event: any) => {
  if (event.type === 'new_message' && otherUser.value) {
    const message = event.data;
    
    // Check if message is part of current conversation
    if ((message.sender_id === otherUser.value.id && message.recipient_id === currentUserId.value) ||
        (message.sender_id === currentUserId.value && message.recipient_id === otherUser.value.id)) {
      
      // Format message to match our interface
      const formattedMessage: Message = {
        id: message.id,
        message: message.message,
        sender_id: message.sender_id,
        sender_name: message.sender_id === currentUserId.value ? 'You' : otherUser.value.name,
        recipient_id: message.recipient_id,
        recipient_name: message.recipient_id === currentUserId.value ? 'You' : otherUser.value.name,
        is_read: message.is_read,
        created_at: new Date(message.created_at).toLocaleString(),
        created_at_human: 'Just now',
      };
      
      // Add to messages if not already present
      if (!messages.value.find(m => m.id === formattedMessage.id)) {
        messages.value.push(formattedMessage);
        
        // Show notification if chat is minimized and message is from other user
        if (isMinimized.value && message.sender_id === otherUser.value.id) {
          // Flash the chat header or show some indicator
          flashChatNotification();
        }
        
        // Auto-scroll to bottom
        nextTick(() => {
          scrollToBottom();
        });
      }
    }
  }
};

const flashChatNotification = () => {
  // Add a visual indicator for new messages when minimized
  // This could be a pulsing effect or color change
  if ((window as any).$notify) {
    (window as any).$notify({
      type: 'info',
      title: 'New Message',
      message: `${otherUser.value?.name} sent you a message`,
      duration: 3000
    });
  }
};

onMounted(() => {
  window.addEventListener('openChat', handleChatOpen as EventListener);
  
  // Subscribe to real-time messages
  unsubscribeMessages = realtimeService.onMessage(handleRealtimeMessage);
});

onUnmounted(() => {
  window.removeEventListener('openChat', handleChatOpen as EventListener);
  
  // Unsubscribe from real-time messages
  if (unsubscribeMessages) {
    unsubscribeMessages();
  }
});

// Helper to get current user ID (you might need to pass this as a prop)
import { usePage } from '@inertiajs/vue3';
const page = usePage();
const currentUserId = ref((page.props.auth as any)?.user?.id);
</script>

<template>
  <div
    v-if="isVisible"
    class="fixed bottom-4 right-4 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
    :class="{ 'h-16': isMinimized, 'h-96': !isMinimized }"
  >
    <!-- Chat Header -->
    <div class="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-gray-700 rounded-t-lg">
      <div class="flex items-center gap-2">
        <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
          <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
            {{ otherUser?.name.charAt(0).toUpperCase() }}
          </span>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ otherUser?.name }}
          </h3>
          <p class="text-xs text-gray-500">{{ otherUser?.email }}</p>
        </div>
      </div>
      
      <div class="flex items-center gap-1">
        <button
          @click="toggleMinimize"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
          :title="isMinimized ? 'Maximize' : 'Minimize'"
        >
          <Icon :name="isMinimized ? 'Maximize2' : 'Minimize2'" class="w-4 h-4" />
        </button>
        <button
          @click="closeChat"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded"
          title="Close"
        >
          <Icon name="X" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Chat Content (hidden when minimized) -->
    <div v-if="!isMinimized" class="flex flex-col h-80">
      <!-- Messages Area -->
      <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-3 space-y-3"
      >
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-4">
          <Icon name="Loader2" class="w-5 h-5 animate-spin mx-auto text-gray-400" />
          <p class="text-xs text-gray-500 mt-2">Loading messages...</p>
        </div>

        <!-- Messages -->
        <div v-else>
          <div
            v-for="message in messages"
            :key="message.id"
            :class="[
              'flex',
              message.sender_id === currentUserId ? 'justify-end' : 'justify-start'
            ]"
          >
            <div
              :class="[
                'max-w-xs px-3 py-2 rounded-lg text-sm',
                message.sender_id === currentUserId
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
              ]"
            >
              <p>{{ message.message }}</p>
              <p
                :class="[
                  'text-xs mt-1',
                  message.sender_id === currentUserId
                    ? 'text-blue-100'
                    : 'text-gray-500 dark:text-gray-400'
                ]"
              >
                {{ message.created_at_human }}
              </p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!loading && messages.length === 0" class="text-center py-8">
            <Icon name="MessageCircle" class="w-8 h-8 mx-auto text-gray-400 mb-2" />
            <p class="text-sm text-gray-500">No messages yet</p>
            <p class="text-xs text-gray-400">Start the conversation!</p>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="border-t border-gray-200 dark:border-gray-700 p-3">
        <div class="flex gap-2">
          <textarea
            v-model="newMessage"
            @keypress="handleKeyPress"
            placeholder="Type a message..."
            rows="1"
            class="flex-1 resize-none border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
            :disabled="sending"
          ></textarea>
          <button
            @click="sendMessage"
            :disabled="!newMessage.trim() || sending"
            class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon 
              :name="sending ? 'Loader2' : 'Send'" 
              :class="['w-4 h-4', { 'animate-spin': sending }]" 
            />
          </button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Press Enter to send, Shift+Enter for new line</p>
      </div>
    </div>
  </div>
</template>
