<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ChatWindow from '@/components/ChatWindow.vue';
import MeetingNoteApprovalModal from '@/components/MeetingNoteApprovalModal.vue';
import DocumentExtractionApprovalModal from '@/components/DocumentExtractionApprovalModal.vue';
import MeetingNotesRecordingTips from '@/components/MeetingNotesRecordingTips.vue';
import SkipToMain from '@/components/SkipToMain.vue';
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
        subscription_type?: string;
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
    (window as any).testRealtimeEvents = () => {
      realtimeService.testRealtimeEvents();
    };
    (window as any).testSupabaseConnection = () => {
      realtimeService.testSupabaseConnection();
    };
    (window as any).testDeleteEvents = () => {
      realtimeService.testDeleteEvents();
    };
    (window as any).testActivityRealtime = () => {
      // Note: activityCallbacks is private, access via public methods if needed
    };
    (window as any).testActivitySubscription = () => {
      const testCallback = () => {
      };
      const unsubscribe = realtimeService.onActivity(testCallback);
      
      // Clean up after 10 seconds
      setTimeout(() => {
        unsubscribe();
      }, 10000);
    };
  }
});

onUnmounted(() => {
  realtimeService.disconnect();
});
</script>

<template>
    <SkipToMain />
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden" :project-color="projectColor">
            <AppSidebarHeader 
              :breadcrumbs="breadcrumbs" 
              :company="company ? { ...company, subscription_type: company.subscription_type || 'FREE' } : null"
            >
                <template #dashboardActions>
                    <slot name="dashboardActions" />
                </template>
            </AppSidebarHeader>
            <slot />
        </AppContent>
        <ChatWindow />
        <MeetingNoteApprovalModal />
        <DocumentExtractionApprovalModal />
        <MeetingNotesRecordingTips />
    </AppShell>
</template>
