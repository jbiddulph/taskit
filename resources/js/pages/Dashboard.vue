<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import TodoBoard from '../components/TodoBoard.vue';

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
    } | null;
}

const props = defineProps<Props>();

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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <TodoBoard />
        </div>
    </AppLayout>
</template>
