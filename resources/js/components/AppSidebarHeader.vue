<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CompanyCodeDisplay from '@/components/CompanyCodeDisplay.vue';
import NotificationBadge from '@/components/NotificationBadge.vue';
import CompanyUsersDropdown from '@/components/CompanyUsersDropdown.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { HeartHandshake, Building2, ShieldCheck } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { isNavSectionActive } from '@/lib/activeNavSection';

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

// Access user data to check if they have a company_id
const page = usePage();
const user = computed(() => {
    const pageProps: any = page.props;
    return pageProps.user ?? (pageProps.auth as any)?.user ?? null;
});

// Show redemption code link only if user doesn't have a company_id
const showRedemptionLink = computed(() => {
    const u: any = user.value;
    return u && (u.company_id === null || u.company_id === undefined);
});

const canAccessSites = computed(() => {
    const pageProps: any = page.props;
    if (typeof pageProps.features?.sites === 'boolean') {
        return pageProps.features.sites;
    }

    const subscriptionType = pageProps.company?.subscription_type ?? user.value?.company?.subscription_type;
    return ['MAXI', 'LTD_AGENCY', 'LTD_BUSINESS'].includes(subscriptionType);
});

const navCompany = computed(() => {
    if (props.company) {
        return props.company;
    }

    const pageProps: any = page.props;
    const source = pageProps.company ?? user.value?.company ?? null;
    if (!source?.id) {
        return null;
    }

    return {
        id: source.id,
        name: source.name,
        code: source.code,
        subscription_type: source.subscription_type ?? 'FREE',
    };
});

const isCompanyActive = computed(() => isNavSectionActive(page.url, 'company'));
const isClientsActive = computed(() => isNavSectionActive(page.url, 'clients'));
const isSitesActive = computed(() => isNavSectionActive(page.url, 'sites'));
const isComplianceActive = computed(() => isNavSectionActive(page.url, 'compliance'));
const isTeamUrlActive = computed(() => isNavSectionActive(page.url, 'team'));
const isTeamDropdownOpen = ref(false);
const isTeamActive = computed(() => isTeamUrlActive.value || isTeamDropdownOpen.value);

const desktopNavClass = (active: boolean) =>
    cn(
        'flex items-center gap-1 px-3 py-1.5 text-sm rounded-md transition-colors',
        active
            ? 'font-semibold text-gray-900 bg-gray-200 ring-1 ring-gray-300 dark:bg-gray-700 dark:text-white dark:ring-gray-500'
            : 'font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-800',
    );

const mobileNavClass = (active: boolean) =>
    cn(
        'flex items-center gap-3 p-4 rounded-lg transition-colors border min-h-[44px]',
        active
            ? 'font-semibold text-gray-900 bg-gray-100 border-gray-900 dark:bg-gray-700 dark:text-white dark:border-gray-300'
            : 'text-gray-700 hover:text-gray-900 hover:bg-gray-100 border-gray-200 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700 dark:border-gray-600',
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
        
        <!-- Center: Redemption Code Link (only for users without company_id) -->
        <div class="flex-1 flex justify-center">
            <Link
                v-if="showRedemptionLink"
                href="/ltd/redeem"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                title="Apply Redemption Code"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <span>Apply Redemption Code</span>
            </Link>
        </div>
        
        <!-- Company info, Clients Link, Team, and Notification Badge on the right -->
        <div class="flex items-center gap-3">
            <!-- Dashboard Action Buttons -->
            <template v-if="$slots.dashboardActions">
                <slot name="dashboardActions" />
            </template>
            
            <!-- Desktop Navigation (hidden on mobile) -->
            <div v-if="navCompany" class="hidden xl:flex items-center gap-3">
                <CompanyCodeDisplay
                    :company-code="navCompany.code"
                    :company-name="navCompany.name"
                />
                
                <!-- Company Info Link -->
                <Link 
                    :href="`/companies/${navCompany.id}`" 
                    :class="desktopNavClass(isCompanyActive)"
                    :aria-current="isCompanyActive ? 'page' : undefined"
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
                    :class="desktopNavClass(isClientsActive)"
                    :aria-current="isClientsActive ? 'page' : undefined"
                    title="Clients"
                >
                    <HeartHandshake class="w-4 h-4" />
                    <span class="hidden sm:inline">Clients</span>
                </Link>

                <!-- Sites Link -->
                <Link 
                    v-if="canAccessSites"
                    href="/sites" 
                    :class="desktopNavClass(isSitesActive)"
                    :aria-current="isSitesActive ? 'page' : undefined"
                    title="Sites & Assets"
                >
                    <Building2 class="w-4 h-4" />
                    <span class="hidden sm:inline">Sites</span>
                </Link>

                <Link
                    v-if="canAccessSites"
                    href="/compliance"
                    :class="desktopNavClass(isComplianceActive)"
                    :aria-current="isComplianceActive ? 'page' : undefined"
                    title="Compliance"
                >
                    <ShieldCheck class="w-4 h-4" />
                    <span class="hidden sm:inline">Compliance</span>
                </Link>
                
                <CompanyUsersDropdown :is-active="isTeamUrlActive" @update:open="isTeamDropdownOpen = $event" />
            </div>
            
            <!-- Mobile Menu Button (only show if user has company) -->
            <button
                v-if="navCompany"
                @click="toggleMobileMenu"
                class="xl:hidden inline-flex items-center justify-center p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md transition-colors"
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
            v-if="isMobileMenuOpen && navCompany"
            class="fixed inset-0 z-50 xl:hidden"
            @click="closeMobileMenu"
        >
            <!-- Backdrop - 50% opacity -->
            <div class="fixed inset-0 bg-black/50"></div>
            
            <!-- Menu Panel - No animation, full width on mobile -->
            <div 
                class="fixed top-0 right-0 h-full w-full sm:w-80 bg-white dark:bg-gray-800 shadow-xl"
                @click.stop
            >
                <div class="flex flex-col h-full">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Company Menu</h2>
                        <button
                            @click="closeMobileMenu"
                            class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors min-w-[44px] min-h-[44px] flex items-center justify-center"
                            aria-label="Close menu"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Menu Items - Single column layout -->
                    <div class="flex-1 overflow-y-auto p-4">
                        <div class="flex flex-col gap-4">
                            <!-- Company Code -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <CompanyCodeDisplay
                                    :company-code="navCompany.code"
                                    :company-name="navCompany.name"
                                />
                            </div>
                            
                            <!-- Company Plan Display -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Plan</span>
                                </div>
                                <div class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ navCompany.subscription_type }}
                                </div>
                            </div>
                            
                            <!-- Company Info Link -->
                            <Link 
                                :href="`/companies/${navCompany.id}`" 
                                @click="closeMobileMenu"
                                :class="mobileNavClass(isCompanyActive)"
                                :aria-current="isCompanyActive ? 'page' : undefined"
                            >
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span class="font-medium">Company Information</span>
                            </Link>
                            
                            <!-- Clients Link -->
                            <Link 
                                href="/clients" 
                                @click="closeMobileMenu"
                                :class="mobileNavClass(isClientsActive)"
                                :aria-current="isClientsActive ? 'page' : undefined"
                            >
                                <HeartHandshake class="w-5 h-5 flex-shrink-0" />
                                <span class="font-medium">Clients</span>
                            </Link>

                            <!-- Sites Link -->
                            <Link 
                                v-if="canAccessSites"
                                href="/sites" 
                                @click="closeMobileMenu"
                                :class="mobileNavClass(isSitesActive)"
                                :aria-current="isSitesActive ? 'page' : undefined"
                            >
                                <Building2 class="w-5 h-5 flex-shrink-0" />
                                <span class="font-medium">Sites & Assets</span>
                            </Link>

                            <Link
                                v-if="canAccessSites"
                                href="/compliance"
                                @click="closeMobileMenu"
                                :class="mobileNavClass(isComplianceActive)"
                                :aria-current="isComplianceActive ? 'page' : undefined"
                            >
                                <ShieldCheck class="w-5 h-5 flex-shrink-0" />
                                <span class="font-medium">Compliance</span>
                            </Link>
                            
                            <!-- Team Dropdown -->
                            <div
                                :class="cn(
                                    'p-4 rounded-lg border',
                                    isTeamActive
                                        ? 'border-gray-900 bg-gray-100 dark:border-gray-300 dark:bg-gray-700'
                                        : 'border-gray-200 dark:border-gray-600',
                                )"
                            >
                                <div class="mb-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Team</span>
                                </div>
                                <CompanyUsersDropdown :is-active="isTeamUrlActive" @update:open="isTeamDropdownOpen = $event" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
