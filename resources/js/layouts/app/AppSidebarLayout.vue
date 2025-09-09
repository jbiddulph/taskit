<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ChatWindow from '@/components/ChatWindow.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    company?: {
        id: number;
        name: string;
        code: string;
    } | null;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    company: null,
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" :company="company" />
            <slot />
        </AppContent>
        <ChatWindow />
    </AppShell>
</template>
