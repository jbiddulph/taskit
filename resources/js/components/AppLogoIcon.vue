<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img 
        src="/zap_icon.png" 
        alt="ZapTask Logo" 
        :class="['dark:bg-white dark:rounded-md dark:p-1', className]" 
        v-bind="$attrs"
    />
</template>
