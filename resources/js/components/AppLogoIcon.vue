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
    <!-- Light mode: PNG -->
    <img 
        src="/zap_icon.png" 
        alt="ZapTask Logo" 
        :class="['dark:hidden', className]" 
        v-bind="$attrs"
    />
    
    <!-- Dark mode: White SVG -->
    <img 
        src="/zap_icon_white.svg" 
        alt="ZapTask Logo" 
        :class="['hidden dark:block', className]" 
        v-bind="$attrs"
    />
</template>
