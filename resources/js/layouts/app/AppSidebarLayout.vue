<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ChatWindow from '@/components/ChatWindow.vue';
import { realtimeService } from '@/services/realtimeService';
import { usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
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

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    company: null,
});

// Initialize real-time service
const page = usePage();
const currentUser = (page.props.auth as any)?.user;

onMounted(() => {
  if (currentUser?.id && props.company?.id) {
    realtimeService.init(currentUser.id, props.company.id);
    
    // Add test functions to window for debugging
    (window as any).testRealtime = () => {
      realtimeService.testRealtime();
    };
    (window as any).testDatabaseRealtime = () => {
      realtimeService.testDatabaseRealtime();
    };
    (window as any).testActivityRealtime = () => {
      console.log('Testing activity real-time...');
      console.log('Current company ID:', props.company?.id);
      console.log('Current user ID:', currentUser?.id);
      console.log('Activity callbacks:', realtimeService.activityCallbacks?.size || 0);
    };
    (window as any).testActivitySubscription = () => {
      console.log('Testing activity subscription...');
      const testCallback = (event: any) => {
        console.log('Test activity callback received:', event);
      };
      const unsubscribe = realtimeService.onActivity(testCallback);
      console.log('Test callback registered');
      
      // Clean up after 10 seconds
      setTimeout(() => {
        unsubscribe();
        console.log('Test callback unregistered');
      }, 10000);
    };
  }
});

onUnmounted(() => {
  realtimeService.disconnect();
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden" :project-color="projectColor">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" :company="company" />
            <slot />
        </AppContent>
        <ChatWindow />
    </AppShell>
</template>
