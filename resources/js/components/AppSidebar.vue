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
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
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
