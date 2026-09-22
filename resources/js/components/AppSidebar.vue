<script setup lang="ts">
import NavProjects from '@/components/NavProjects.vue';
import NavUser from '@/components/NavUser.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PanelLeft } from 'lucide-vue-next';

const { toggleSidebar } = useSidebar();
const page = usePage();

const company = computed(() => (page.props.auth as any)?.user?.company);
const customLogoUrl = computed(() => company.value?.logo_url);
const hasCustomLogo = computed(() => !!customLogoUrl.value);
</script>

<template>
    <Sidebar collapsible="offcanvas" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        tooltip="Hide sidebar"
                        @click="toggleSidebar"
                    >
                        <div
                            v-if="hasCustomLogo"
                            class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md"
                        >
                            <img
                                :src="customLogoUrl"
                                :alt="(company?.name || 'Company') + ' logo'"
                                class="size-8 object-contain"
                            />
                        </div>
                        <div
                            v-else
                            class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                        >
                            <AppLogoIcon class="size-4 fill-current text-white dark:text-black" />
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">Dashboard</span>
                        </div>
                        <PanelLeft class="ml-auto size-4 shrink-0" />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavProjects />
        </SidebarContent>

        <SidebarFooter>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <NavUser />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
