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
                        @toggle-activity-feed="showActivityFeed = !showActivityFeed"
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
