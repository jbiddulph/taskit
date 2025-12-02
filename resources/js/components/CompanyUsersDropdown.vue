<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { realtimeService } from '@/services/realtimeService';

interface CompanyUser {
  id: number;
  name: string;
  email: string;
  joined_at: string;
  unread_messages: number;
}

const showDropdown = ref(false);
const companyUsers = ref<CompanyUser[]>([]);
const loading = ref(false);
const isChatOpen = ref(false);

// Helper function to get initials from full name
const getInitials = (name: string): string => {
  const names = name.trim().split(' ');
  if (names.length === 1) {
    // If only one name, return first two characters
    return names[0].substring(0, 2).toUpperCase();
  }
  // Return first letter of first name and first letter of last name
  return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase();
};

const loadCompanyUsers = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/company-users', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    if (response.ok) {
      const data = await response.json();
      companyUsers.value = data.data || [];
    }
  } catch (error) {
    console.error('Failed to load company users:', error);
  } finally {
    loading.value = false;
  }
};

const openChat = (user: CompanyUser) => {
  // Emit event to open chat
  window.dispatchEvent(new CustomEvent('openChat', { 
    detail: { user } 
  }));
  showDropdown.value = false;
};

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
  if (showDropdown.value) {
    loadCompanyUsers();
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  const dropdown = document.getElementById('company-users-dropdown');
  if (dropdown && !dropdown.contains(target)) {
    showDropdown.value = false;
  }
};

// Real-time unread count updates
let unsubscribeUnreadCount: (() => void) | null = null;

const handleUnreadCountUpdate = (count: number) => {
  // Only reload users if we don't have an active chat or if the notification is from a different user
  // We need to be more intelligent about when to reload
  if (showDropdown.value) {
    // Always reload for now, but we could make this smarter by tracking which user the notification was from
    loadCompanyUsers();
  }
};

// Cleanup
const cleanup = () => {
  document.removeEventListener('click', handleClickOutside);
  if (unsubscribeUnreadCount) {
    unsubscribeUnreadCount();
  }
};

// Listen for chat window state changes
const handleChatStateChange = (event: Event) => {
  const customEvent = event as CustomEvent;
  isChatOpen.value = customEvent.detail.isOpen;
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  
  // Subscribe to unread count changes
  unsubscribeUnreadCount = realtimeService.onUnreadCountChange(handleUnreadCountUpdate);
  
  // Listen for chat window state changes
  window.addEventListener('chatStateChange', handleChatStateChange as EventListener);
});

onUnmounted(() => {
  cleanup();
  window.removeEventListener('chatStateChange', handleChatStateChange as EventListener);
});
</script>

<template>
  <div class="relative" id="company-users-dropdown">
    <button
      @click="toggleDropdown"
      class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
      title="Company Users"
    >
      <Icon name="Users" class="w-4 h-4" />
      <span class="hidden sm:inline">Team</span>
      <Icon 
        :name="showDropdown ? 'ChevronUp' : 'ChevronDown'" 
        class="w-3 h-3" 
      />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="showDropdown"
      class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50"
    >
      <div class="p-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Company Team</h3>
      </div>

      <div class="max-h-80 overflow-y-auto">
        <!-- Loading State -->
        <div v-if="loading" class="p-4 text-center">
          <Icon name="Loader2" class="w-5 h-5 animate-spin mx-auto text-gray-400" />
          <p class="text-sm text-gray-500 mt-2">Loading team members...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="companyUsers.length === 0" class="p-4 text-center">
          <Icon name="Users" class="w-8 h-8 mx-auto text-gray-400 mb-2" />
          <p class="text-sm text-gray-500">No other team members</p>
        </div>

        <!-- Company Users List -->
        <div v-else class="py-1">
          <div
            v-for="(user, index) in companyUsers"
            :key="user.id"
            class="group"
          >
            <!-- User Row -->
            <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                      <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        {{ getInitials(user.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                      {{ user.name }}
                    </p>
                    <p class="text-xs text-gray-400">
                      Joined {{ user.joined_at }}
                    </p>
                  </div>
                  <!-- Unread Messages Badge - Only show when chat is closed -->
                  <div
                    v-if="user.unread_messages > 0 && !isChatOpen"
                    class="flex-shrink-0 bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center"
                  >
                    {{ user.unread_messages }}
                  </div>
                </div>
              </div>
              
              <button
                @click="openChat(user)"
                class="ml-3 flex-shrink-0 inline-flex items-center justify-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300"
              >
                <Icon name="MessageCircle" class="w-3 h-3 mr-1" />
                Message
              </button>
            </div>
            
            <!-- Separator Line - Don't show after last item -->
            <div 
              v-if="index < companyUsers.length - 1" 
              class="mx-4 border-b border-gray-200 dark:border-gray-600"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
