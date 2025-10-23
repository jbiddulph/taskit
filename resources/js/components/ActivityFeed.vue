<template>
  <div class="activity-feed">
    <!-- Activity Feed Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Feed</h2>
      <div class="flex items-center gap-2">
        <select
          v-model="selectedFilter"
          @change="applyFilters"
          class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
        >
          <option value="">All Activities</option>
          <option v-for="type in activityTypes" :key="type" :value="type">
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
    <div class="space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="activity in filteredActivities"
        :key="activity.id"
        class="flex items-start gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <!-- Activity Icon -->
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center',
            getActivityColor(activity.type).replace('text-', 'bg-').replace('-600', '-100 dark:bg-') + ' dark:bg-'
          ]">
            <Icon 
              :name="getActivityIcon(activity.type)" 
              :class="[
                'w-4 h-4',
                getActivityColor(activity.type)
              ]"
            />
          </div>
        </div>

        <!-- Activity Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ activity.actor_name }}</span>
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
    <div v-if="hasMoreActivities" class="mt-4 text-center">
      <button
        @click="loadMoreActivities"
        class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
      >
        Load More
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import Icon from '@/components/Icon.vue';
import { activityFeedService, type ActivityItem } from '@/services/activityFeedService';

interface Props {
  companyId: number;
  projectId?: number;
  userId?: number;
}

const props = defineProps<Props>();

const selectedFilter = ref('');
const activities = ref<ActivityItem[]>([]);
const currentPage = ref(1);
const itemsPerPage = 20;

const activityTypes = computed(() => activityFeedService.getActivityTypes());

const filteredActivities = computed(() => {
  let filtered = activities.value;

  if (selectedFilter.value) {
    filtered = filtered.filter(activity => activity.type === selectedFilter.value);
  }

  return filtered.slice(0, currentPage.value * itemsPerPage);
});

const hasMoreActivities = computed(() => {
  return activities.value.length > currentPage.value * itemsPerPage;
});

const loadActivities = () => {
  if (props.projectId) {
    activities.value = activityFeedService.getProjectActivities(props.projectId);
  } else if (props.userId) {
    activities.value = activityFeedService.getUserActivities(props.userId);
  } else {
    activities.value = activityFeedService.getCompanyActivities(props.companyId);
  }
};

const loadMoreActivities = () => {
  currentPage.value++;
};

const refreshActivities = () => {
  loadActivities();
  currentPage.value = 1;
};

const applyFilters = () => {
  currentPage.value = 1;
};

const formatActivityType = (type: string): string => {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
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
  return activityFeedService.getActivityIcon(type);
};

const getActivityColor = (type: string): string => {
  return activityFeedService.getActivityColor(type);
};

onMounted(() => {
  loadActivities();
});
</script>
