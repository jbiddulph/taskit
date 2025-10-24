<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Props {
    title?: string;
}

defineProps<Props>();
</script>

<template>
    <Head :title="title" />
    
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <slot />
    </div>
</template>
