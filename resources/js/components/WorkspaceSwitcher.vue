<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Layers, Plus } from 'lucide-vue-next';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';

interface SharedWorkspace {
  id: number;
  name: string;
  type: string;
  is_default: boolean;
}

const page = usePage();
const { isMobile, state } = useSidebar();

const workspaces = computed(() => {
  const platform = (page.props as any).platform;
  return (platform?.workspaces ?? []) as SharedWorkspace[];
});

const currentWorkspaceId = computed(() => {
  const platform = (page.props as any).platform;
  return platform?.currentWorkspaceId as number | null | undefined;
});

const currentWorkspace = computed(() => {
  return workspaces.value.find((w) => w.id === currentWorkspaceId.value) ?? workspaces.value[0] ?? null;
});

const hasCompany = computed(() => {
  const auth = (page.props as any).auth;
  return Boolean(auth?.user?.company_id || auth?.user?.company?.id);
});

const switchWorkspace = (workspaceId: number) => {
  if (workspaceId === currentWorkspaceId.value) return;
  router.post(
    '/settings/workspaces/switch',
    { workspace_id: workspaceId },
    { preserveScroll: true },
  );
};
</script>

<template>
  <SidebarMenu v-if="hasCompany && currentWorkspace">
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton
            size="lg"
            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            :tooltip="currentWorkspace.name"
          >
            <div class="flex aspect-square size-8 items-center justify-center rounded-md border border-sidebar-border bg-sidebar-primary text-sidebar-primary-foreground">
              <Layers class="size-4" />
            </div>
            <div class="grid flex-1 text-left text-sm leading-tight">
              <span class="truncate font-semibold">{{ currentWorkspace.name }}</span>
              <span class="truncate text-xs text-muted-foreground">Workspace</span>
            </div>
            <ChevronsUpDown class="ml-auto size-4" />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
          :side="isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'"
          align="start"
          :side-offset="4"
        >
          <DropdownMenuLabel class="text-xs text-muted-foreground">Workspaces</DropdownMenuLabel>
          <DropdownMenuItem
            v-for="workspace in workspaces"
            :key="workspace.id"
            class="gap-2 p-2"
            @click="switchWorkspace(workspace.id)"
          >
            <div class="flex size-6 items-center justify-center rounded-sm border">
              <Layers class="size-3.5 shrink-0" />
            </div>
            <span class="flex-1 truncate">{{ workspace.name }}</span>
            <Check
              v-if="workspace.id === currentWorkspaceId"
              class="size-4 shrink-0"
            />
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem :as-child="true">
            <Link class="flex w-full items-center gap-2 p-2" href="/settings/workspaces">
              <Plus class="size-4" />
              Manage workspaces
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem :as-child="true">
            <Link class="flex w-full items-center gap-2 p-2" href="/settings/platform">
              <Layers class="size-4" />
              Platform settings
            </Link>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
