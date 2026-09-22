<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { PanelLeft } from 'lucide-vue-next'
import { useSidebar } from './utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    :class="cn('h-8 gap-2 px-2', props.class)"
    @click="toggleSidebar"
  >
    <PanelLeft class="size-4 shrink-0" />
    <span class="text-sm font-medium">Sidebar</span>
  </Button>
</template>
