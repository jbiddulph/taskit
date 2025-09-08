<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CompanyCodeDisplay from '@/components/CompanyCodeDisplay.vue';
import NotificationBadge from '@/components/NotificationBadge.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
        company?: {
            id: number;
            name: string;
            code: string;
            subscription_type: string;
        } | null;
    }>(),
    {
        breadcrumbs: () => [],
        company: null,
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        
        <!-- Company Code, Clients Link, and Notification Badge on the right -->
        <div class="flex items-center gap-3">
            <CompanyCodeDisplay 
                :company-code="company?.code" 
                :company-name="company?.name" 
                :subscription-type="company?.subscription_type"
            />
            
            <!-- Clients Link -->
            <Link 
                href="/clients" 
                class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                title="Manage Clients"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.121L16.5 17l-1.5-1.5L13.5 17l-.304-1.121A3 3 0 008 18v2h5m4-7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span class="hidden sm:inline">Clients</span>
            </Link>
            
            <NotificationBadge />
        </div>
    </header>
</template>
