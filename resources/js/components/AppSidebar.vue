<script setup lang="ts">
import NavProjects from '@/components/NavProjects.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';




</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <Link :href="dashboard()" class="flex items-center gap-1 p-2 rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                        <AppLogo />
                    </Link>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="space-y-2">
                <!-- Clients Navigation -->
                <SidebarMenu class="px-2 py-0">
                    <SidebarMenuItem>
                        <Link 
                            href="/clients" 
                            class="flex items-center gap-2 p-2 rounded-md text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.121L16.5 17l-1.5-1.5L13.5 17l-.304-1.121A3 3 0 008 18v2h5m4-7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                            </svg>
                            <span>Clients</span>
                        </Link>
                    </SidebarMenuItem>
                </SidebarMenu>
                
                <!-- Projects Navigation -->
                <NavProjects />
            </div>
        </SidebarContent>

        <SidebarFooter>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <NavUser />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
