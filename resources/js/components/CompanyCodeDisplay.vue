<script setup lang="ts">
import { ref } from 'vue';
import { Check, Copy } from 'lucide-vue-next';

interface Props {
  companyCode?: string;
  companyName?: string;
  subscriptionType?: string;
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
  } catch (err) {
    console.error('Failed to copy: ', err);
  }
};
</script>

<template>
  <div v-if="companyCode" class="flex items-center gap-4">
    <!-- Company Code -->
    <div class="flex items-center gap-2">
      <div class="text-xs text-gray-500 dark:text-gray-400">
        Company Code:
      </div>
      <button
        @click="copyToClipboard"
        class="flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md text-xs font-mono text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
        :title="`Click to copy company code: ${companyCode}`"
      >
        <span class="font-semibold">{{ companyCode }}</span>
        <component 
          :is="copied ? Check : Copy" 
          :size="12" 
          class="text-blue-600 dark:text-blue-400"
        />
      </button>
    </div>

    <!-- Subscription Type -->
    <div v-if="subscriptionType" class="flex items-center gap-2">
      <div class="text-xs text-gray-500 dark:text-gray-400">
        Plan:
      </div>
      <div 
        class="px-2 py-1 rounded-md text-xs font-semibold"
        :class="{
          'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': subscriptionType === 'FREE',
          'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800': subscriptionType === 'MIDI',
          'bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/20 dark:to-amber-900/20 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800': subscriptionType === 'MAXI'
        }"
      >
        {{ subscriptionType }}
      </div>
    </div>
  </div>
</template>
