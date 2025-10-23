<template>
  <div class="activity-feed">
    <!-- Activity Feed Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Feed</h2>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedFilter"
          class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
        >
          <option value="">All Activities</option>
          <option v-for="type in Object.keys(activityTypes)" :key="type" :value="type">
            {{ formatActivityType(type) }}
          </option>
        </select>
        <button
          @click="refreshActivities"
          class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <Icon name="RefreshCw" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Activities List -->
    <div class="space-y-2 max-h-96 overflow-y-auto">
      <div
        v-for="activity in filteredActivities"
        :key="activity.id"
        class="flex items-start gap-2 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <!-- Activity Icon -->
        <div class="flex-shrink-0">
          <div :class="[
            'w-4 h-4 rounded-full flex items-center justify-center',
            getActivityColor(activity.type).replace('text-', 'bg-').replace('-600', '-100 dark:bg-') + ' dark:bg-'
          ]">
            <Icon 
              :name="getActivityIcon(activity.type)" 
              :class="[
                'w-2 h-2',
                getActivityColor(activity.type)
              ]"
            />
          </div>
        </div>

        <!-- Activity Content -->
        <div class="flex-1 min-w-0">
          <div class="mb-1">
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ activity.description }}</span>
          </div>
          
          <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatDate(activity.created_at) }}
            <span v-if="activity.target_name" class="ml-2">
              • {{ activity.target_name }}
            </span>
          </div>

          <!-- Activity Metadata -->
          <div v-if="activity.metadata" class="mt-2 text-xs text-gray-600 dark:text-gray-400">
            <div v-if="activity.type === 'todo_status_changed' && activity.metadata.oldStatus && activity.metadata.newStatus">
              <span class="inline-block px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs mr-2">
                {{ activity.metadata.oldStatus }}
              </span>
              <Icon name="ArrowRight" class="w-3 h-3 inline mx-1" />
              <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200 rounded text-xs">
                {{ activity.metadata.newStatus }}
              </span>
            </div>
            
            <div v-if="activity.type === 'todo_assigned' && activity.metadata.assignee">
              Assigned to: <span class="font-medium">{{ activity.metadata.assignee }}</span>
            </div>
            
            <div v-if="activity.type === 'mention' && activity.metadata.mentionedUser">
              Mentioned: <span class="font-medium">{{ activity.metadata.mentionedUser.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- No activities message -->
      <div v-if="filteredActivities.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <Icon name="Activity" class="w-12 h-12 mx-auto mb-4" />
        <p class="text-sm">No activities found</p>
      </div>
    </div>

    <!-- Load More Button -->
    <div v-if="hasMore" class="mt-4 text-center">
      <button
        @click="loadMoreActivities"
        :disabled="loading"
        class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ loading ? 'Loading...' : 'Load More' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { realtimeService } from '@/services/realtimeService';

interface ActivityItem {
  id: number;
  type: string;
  actor_id: number;
  actor_name: string;
  actor_email?: string;
  target_id?: number;
  target_name?: string;
  description: string;
  metadata?: any;
  created_at: string;
  project_id?: number;
  company_id: number;
  actor?: {
    id: number;
    name: string;
    email: string;
  };
  project?: {
    id: number;
    name: string;
  };
}

interface Props {
  companyId: number;
  projectId?: number;
  userId?: number;
  visible?: boolean;
}

const props = defineProps<Props>();

const selectedFilter = ref('');
const activities = ref<ActivityItem[]>([]);
const activityTypes = ref<Record<string, string>>({});
const loading = ref(false);
const currentPage = ref(1);
const itemsPerPage = 20;
const hasMore = ref(true);

const filteredActivities = computed(() => {
  let filtered = activities.value;

  if (selectedFilter.value) {
    filtered = filtered.filter(activity => activity.type === selectedFilter.value);
  }

  return filtered;
});

const loadActivities = async () => {
  try {
    loading.value = true;
    
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: itemsPerPage.toString()
    });

    if (props.projectId) {
      params.append('project_id', props.projectId.toString());
    }

    if (props.userId) {
      params.append('user_id', props.userId.toString());
    }


    const response = await fetch(`/api/activities?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    if (response.ok) {
      const data = await response.json();
      if (currentPage.value === 1) {
        activities.value = data.data;
      } else {
        activities.value = [...activities.value, ...data.data];
      }
      hasMore.value = data.meta.has_more;
      console.log('ActivityFeed: Activities loaded:', activities.value.length);
    } else {
      console.error('ActivityFeed: API Error:', response.status, response.statusText);
    }
  } catch (error) {
    console.error('Failed to load activities:', error);
  } finally {
    loading.value = false;
  }
};

const loadActivityTypes = async () => {
  try {
    const response = await fetch('/api/activities/types', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    if (response.ok) {
      const data = await response.json();
      activityTypes.value = data.data;
    }
  } catch (error) {
    console.error('Failed to load activity types:', error);
  }
};

const loadMoreActivities = async () => {
  if (hasMore.value && !loading.value) {
    currentPage.value++;
    await loadActivities();
  }
};

const refreshActivities = async () => {
  currentPage.value = 1;
  await loadActivities();
};


const formatActivityType = (type: string): string => {
  return activityTypes.value[type] || type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) return 'Just now';
  if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
  if (diffInMinutes < 10080) return `${Math.floor(diffInMinutes / 1440)}d ago`;
  
  return date.toLocaleDateString();
};

const getActivityIcon = (type: string): string => {
  const icons: { [key: string]: string } = {
    'todo_created': 'Plus',
    'todo_updated': 'Edit',
    'todo_deleted': 'Trash2',
    'todo_commented': 'MessageCircle',
    'todo_assigned': 'UserCheck',
    'todo_status_changed': 'ArrowRight',
    'project_created': 'FolderPlus',
    'project_updated': 'FolderEdit',
    'project_deleted': 'FolderX',
    'user_joined': 'UserPlus',
    'mention': 'AtSign'
  };
  return icons[type] || 'Activity';
};

const getActivityColor = (type: string): string => {
  const colors: { [key: string]: string } = {
    'todo_created': 'text-green-600',
    'todo_updated': 'text-blue-600',
    'todo_deleted': 'text-red-600',
    'todo_commented': 'text-purple-600',
    'todo_assigned': 'text-orange-600',
    'todo_status_changed': 'text-indigo-600',
    'project_created': 'text-green-600',
    'project_updated': 'text-blue-600',
    'project_deleted': 'text-red-600',
    'user_joined': 'text-green-600',
    'mention': 'text-pink-600'
  };
  return colors[type] || 'text-gray-600';
};

// Real-time subscription
let unsubscribeActivity: (() => void) | null = null;

onMounted(async () => {
  await loadActivityTypes();
  if (props.visible) {
    await loadActivities();
  }
  
  // Subscribe to real-time activity events
  unsubscribeActivity = realtimeService.onActivity((event) => {
    if (event.type === 'activity_created') {
      // Check if the new activity matches the current filter
      const shouldInclude = !selectedFilter.value || event.data.type === selectedFilter.value;
      
      if (shouldInclude) {
        // Add new activity to the beginning of the list
        activities.value.unshift(event.data);
        
        // If we have too many activities, remove the last one to maintain pagination
        if (activities.value.length > itemsPerPage) {
          activities.value = activities.value.slice(0, itemsPerPage);
        }
      }
    } else if (event.type === 'activity_updated') {
      // Check if the updated activity should be visible with current filter
      const shouldInclude = !selectedFilter.value || event.data.type === selectedFilter.value;
      const index = activities.value.findIndex(activity => activity.id === event.data.id);
      
      if (shouldInclude && index !== -1) {
        // Update existing activity
        activities.value[index] = event.data;
      } else if (shouldInclude && index === -1) {
        // Activity now matches filter, add it
        activities.value.unshift(event.data);
        if (activities.value.length > itemsPerPage) {
          activities.value = activities.value.slice(0, itemsPerPage);
        }
      } else if (!shouldInclude && index !== -1) {
        // Activity no longer matches filter, remove it
        activities.value.splice(index, 1);
      }
    } else if (event.type === 'activity_deleted') {
      // Remove deleted activity
      activities.value = activities.value.filter(activity => activity.id !== event.data.id);
    }
  });
});

onUnmounted(() => {
  if (unsubscribeActivity) {
    unsubscribeActivity();
  }
});

// Watch for visibility changes
watch(() => props.visible, async (newVisible) => {
  if (newVisible && activities.value.length === 0) {
    await loadActivities();
  }
});
</script>

