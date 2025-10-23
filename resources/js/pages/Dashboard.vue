<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import TodoBoard from '../components/TodoBoard.vue';
import LimitWarnings from '../components/LimitWarnings.vue';
import ActivityFeed from '../components/ActivityFeed.vue';
import Icon from '../components/Icon.vue';
import { ref } from 'vue';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        company_id?: number;
    };
    company?: {
        id: number;
        name: string;
        code: string;
        subscription_type: string;
        current_member_count: number;
        member_limit: number;
        current_project_count: number;
        project_limit: number;
    } | null;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Project color state for main tag border
const currentProjectColor = ref<string | null>(null);

// Activity Feed toggle state
const showActivityFeed = ref(false);

// Calendar and Filters state
const showCalendar = ref(false);
const showFilters = ref(false);

const handleProjectChange = (project: any) => {
    currentProjectColor.value = project?.color || null;
};
</script>

<style scoped>
.activity-feed-container {
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs" :company="company" :project-color="currentProjectColor">
        <template #dashboardActions>
            <!-- Calendar Button -->
            <button
                @click="showCalendar = !showCalendar"
                :title="showCalendar ? 'Hide Calendar' : 'Show Calendar'"
                class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600"
            >
                <Icon name="Calendar" class="w-4 h-4" />
            </button>

            <!-- Activity Feed Toggle Button -->
            <button
                @click="showActivityFeed = !showActivityFeed"
                :title="showActivityFeed ? 'Hide Activity Feed' : 'Show Activity Feed'"
                :class="[
                    'inline-flex items-center justify-center w-10 h-10 rounded-lg border transition-colors',
                    showActivityFeed 
                        ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-600' 
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
            >
                <Icon name="Activity" class="w-4 h-4" />
            </button>
        </template>
        <!-- Limit Warnings -->
        <LimitWarnings v-if="company" :company="company" />
        
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div :class="[
                'grid gap-4 h-full',
                showActivityFeed ? 'grid-cols-1 lg:grid-cols-4' : 'grid-cols-1'
            ]">
                <!-- Main Todo Board -->
                <div :class="showActivityFeed ? 'lg:col-span-3' : 'col-span-1'">
                    <TodoBoard 
                        @project-changed="handleProjectChange" 
                        :show-activity-feed="showActivityFeed"
                        :show-calendar="showCalendar"
                        @toggle-activity-feed="showActivityFeed = !showActivityFeed"
                        @toggle-calendar="showCalendar = !showCalendar"
                    />
                </div>
                
                <!-- Activity Feed Sidebar -->
                <div v-show="showActivityFeed" class="lg:col-span-1 activity-feed-container">
                    <ActivityFeed 
                        :company-id="company?.id || 1" 
                        :visible="showActivityFeed"
                        class="h-full"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
