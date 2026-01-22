<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
// import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import SeoHead from '@/components/SeoHead.vue';
import TodoBoard from '../components/TodoBoard.vue';
import LimitWarnings from '../components/LimitWarnings.vue';
import ActivityFeed from '../components/ActivityFeed.vue';
import Icon from '../components/Icon.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

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

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: '/dashboard',
    },
];

// Project color state for main tag border
const currentProjectColor = ref<string | null>(null);

// Activity Feed toggle state
const showActivityFeed = ref(false);

// Calendar and Filters state
const showCalendar = ref(false);
const showFilters = ref(false);

// Bulk selection state
const isSelectMode = ref(false);

const handleProjectChange = (project: any) => {
    currentProjectColor.value = project?.color || null;
};

const toggleSelectMode = () => {
    isSelectMode.value = !isSelectMode.value;
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
    <SeoHead
        :title="t('dashboard.title')"
        description="Manage your projects, tasks, and activity feed from the ZapTask dashboard."
        image="/zap_icon.png"
    />

    <AppLayout :breadcrumbs="breadcrumbs" :company="company" :project-color="currentProjectColor">
        <template #dashboardActions>
            <!-- Calendar Button -->
            <button
                @click="showCalendar = !showCalendar"
                :title="showCalendar ? t('dashboard.hide_calendar') : t('dashboard.show_calendar')"
                :class="[
                    'inline-flex items-center justify-center p-2 transition-colors',
                    showCalendar 
                        ? 'text-blue-600 dark:text-blue-400' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                ]"
            >
                <Icon name="Calendar" class="w-5 h-5" />
            </button>

            <!-- Activity Feed Toggle Button -->
            <button
                @click="showActivityFeed = !showActivityFeed"
                :title="showActivityFeed ? t('dashboard.hide_activity_feed') : t('dashboard.show_activity_feed')"
                :class="[
                    'inline-flex items-center justify-center p-2 transition-colors',
                    showActivityFeed 
                        ? 'text-blue-600 dark:text-blue-400' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                ]"
            >
                <Icon name="Activity" class="w-5 h-5" />
            </button>

            <!-- Select for Bulk Update Button -->
            <button
                @click="toggleSelectMode"
                :title="isSelectMode ? t('dashboard.exit_select_mode') : t('dashboard.select_for_bulk_update')"
                :class="[
                    'inline-flex items-center justify-center p-2 transition-colors',
                    isSelectMode 
                        ? 'text-blue-600 dark:text-blue-400' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                ]"
            >
                <Icon name="CheckSquare" class="w-5 h-5" />
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
                        :is-select-mode="isSelectMode"
                        @toggle-activity-feed="showActivityFeed = !showActivityFeed"
                        @toggle-calendar="showCalendar = !showCalendar"
                        @toggle-select-mode="toggleSelectMode"
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
