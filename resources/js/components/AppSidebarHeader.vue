<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CompanyCodeDisplay from '@/components/CompanyCodeDisplay.vue';
import NotificationBadge from '@/components/NotificationBadge.vue';
import CompanyUsersDropdown from '@/components/CompanyUsersDropdown.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = withDefaults(
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

// Mobile menu state
const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};
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
            
            <!-- Desktop Navigation (hidden on mobile) -->
            <div v-if="company" class="hidden md:flex items-center gap-3">
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
                
                <CompanyUsersDropdown />
            </div>
            
            <!-- Mobile Menu Button (only show if user has company) -->
            <button
                v-if="company"
                @click="toggleMobileMenu"
                class="md:hidden inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
                title="Company Menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <NotificationBadge />
        </div>
        
        <!-- Mobile Menu Overlay -->
        <div
            v-if="isMobileMenuOpen && company"
            class="fixed inset-0 z-50 md:hidden"
            @click="closeMobileMenu"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50"></div>
            
            <!-- Menu Panel -->
            <div class="fixed top-0 right-0 h-full w-80 bg-white dark:bg-gray-800 shadow-xl transform transition-transform duration-300 ease-in-out">
                <div class="flex flex-col h-full">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Company Menu</h2>
                        <button
                            @click="closeMobileMenu"
                            class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="flex-1 p-4 space-y-4">
                        <!-- Company Code -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <CompanyCodeDisplay 
                                :company-code="company.code" 
                                :company-name="company.name" 
                                :subscription-type="company.subscription_type"
                            />
                        </div>
                        
                        <!-- Company Info Link -->
                        <Link 
                            :href="`/companies/${company.id}`" 
                            @click="closeMobileMenu"
                            class="flex items-center gap-3 p-3 text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="font-medium">Company Information</span>
                        </Link>
                        
                        <!-- Team Dropdown -->
                        <div class="p-3">
                            <CompanyUsersDropdown />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
