<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import TodoBoard from '../components/TodoBoard.vue';
import LimitWarnings from '../components/LimitWarnings.vue';
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

const handleProjectChange = (project: any) => {
    currentProjectColor.value = project?.color || null;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs" :company="company" :project-color="currentProjectColor">
        <!-- Limit Warnings -->
        <LimitWarnings v-if="company" :company="company" />
        
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <TodoBoard @project-changed="handleProjectChange" />
        </div>
    </AppLayout>
</template>
