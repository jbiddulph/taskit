<script setup lang="ts">
import { ref } from 'vue';
import { Check, Copy } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface Props {
  companyCode?: string;
  companyName?: string;
}

const props = defineProps<Props>();

const copied = ref(false);

const copyToClipboard = async () => {
  if (!props.companyCode) return;

  try {
    await navigator.clipboard.writeText(props.companyCode);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch {}
};
</script>

<template>
  <div v-if="companyCode" class="flex items-center">
    <TooltipProvider :delay-duration="0" :skip-delay-duration="0">
      <Tooltip>
        <TooltipTrigger :as-child="true">
          <button
            type="button"
            class="flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md text-xs font-mono text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
            :aria-label="copied ? 'Company code copied' : 'Click to copy Company Code'"
            @click="copyToClipboard"
          >
            <span class="font-semibold">{{ companyCode }}</span>
            <component
              :is="copied ? Check : Copy"
              :size="12"
              class="text-blue-600 dark:text-blue-400"
            />
          </button>
        </TooltipTrigger>
        <TooltipContent
          side="bottom"
          class="animate-none data-[state=closed]:animate-none"
        >
          {{ copied ? 'Copied!' : 'Click to copy Company Code' }}
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>
