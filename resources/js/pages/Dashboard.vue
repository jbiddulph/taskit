<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import TodoBoard from '../components/TodoBoard.vue';
import LimitWarnings from '../components/LimitWarnings.vue';

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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs" :company="company">
        <!-- Limit Warnings -->
        <LimitWarnings v-if="company" :company="company" />
        
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <TodoBoard />
        </div>
    </AppLayout>
</template>
