<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CompanyCodeDisplay from '@/components/CompanyCodeDisplay.vue';
import NotificationBadge from '@/components/NotificationBadge.vue';
import CompanyUsersDropdown from '@/components/CompanyUsersDropdown.vue';
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
        
        <!-- Company info, Clients Link, Team, and Notification Badge on the right -->
        <div class="flex items-center gap-3">
            <!-- Dashboard Action Buttons -->
            <template v-if="$slots.dashboardActions">
                <slot name="dashboardActions" />
            </template>
            <!-- Company-related items (only show if user belongs to a company) -->
            <template v-if="company">
                <CompanyCodeDisplay 
                    :company-code="company.code" 
                    :company-name="company.name" 
                    :subscription-type="company.subscription_type"
                />
                
                <!-- Company Info Link -->
                <Link 
                    :href="`/companies/${company.id}`" 
                    class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                    title="Company Information"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="hidden sm:inline">Company</span>
                </Link>
                
                <!-- Clients Link -->
                <Link 
                    href="/clients" 
                    class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                    title="Manage Clients"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6z"></path>
                    </svg>
                    <span class="hidden sm:inline">Clients</span>
                </Link>
                
                <CompanyUsersDropdown />
            </template>
            
            <NotificationBadge />
        </div>
    </header>
</template>
