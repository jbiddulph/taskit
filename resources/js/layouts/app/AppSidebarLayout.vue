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
  console.log('🔥 AppSidebarLayout mounted. User:', currentUser?.id, 'Company:', props.company?.id);
  console.log('🔥 Supabase URL:', window.VITE_SUPABASE_URL);
  console.log('🔥 Supabase Key exists:', !!window.VITE_SUPABASE_ANON_KEY);
  
  if (currentUser?.id && props.company?.id) {
    console.log('🔥 Initializing realtimeService from AppSidebarLayout');
    realtimeService.init(currentUser.id, props.company.id);
    
    // Add test functions to window for debugging
    (window as any).testRealtime = () => {
      realtimeService.testRealtime();
    };
    (window as any).testDatabaseRealtime = () => {
      realtimeService.testDatabaseRealtime();
    };
    console.log('🧪 Test functions available: window.testRealtime() and window.testDatabaseRealtime()');
  } else {
    console.log('🚨 Missing user ID or company ID for realtime service');
    console.log('🚨 User ID:', currentUser?.id);
    console.log('🚨 Company ID:', props.company?.id);
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
