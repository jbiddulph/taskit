<script setup lang="ts">
import { ref } from 'vue';
import { Check, Copy } from 'lucide-vue-next';

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
  } catch (err) {
    console.error('Failed to copy: ', err);
  }
};
</script>

<template>
  <div v-if="companyCode" class="flex items-center gap-2">
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
</template>
