<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TodoBoard from '@/components/TodoBoard.vue';
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard">
        <TodoBoard />
    </AppLayout>
</template>