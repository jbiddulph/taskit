<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    company?: {
        id: number;
        name: string;
        code: string;
    } | null;
    projectColor?: string | null;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    company: null,
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :company="company" :project-color="projectColor">
        <slot />
    </AppLayout>
</template>
