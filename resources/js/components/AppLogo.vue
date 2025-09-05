<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

// Get company logo URL if available
const company = computed(() => (page.props.auth as any)?.user?.company);
const customLogoUrl = computed(() => company.value?.logo_url);
const hasCustomLogo = computed(() => !!customLogoUrl.value);
</script>

<template>
    <!-- Custom Logo Display -->
    <div v-if="hasCustomLogo" class="flex items-center">
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <img 
                :src="customLogoUrl"
                :alt="company?.name + ' logo'"
                class="max-w-full max-h-full object-contain"
                style="max-height: 32px;"
            />
        </div>
        <div class="ml-1 grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate leading-tight font-semibold">{{ company?.name }}</span>
        </div>
    </div>

    <!-- Default TaskIT Logo -->
    <template v-else>
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
            <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
        </div>
        <div class="ml-1 grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate leading-tight font-semibold">TaskIT</span>
        </div>
    </template>
</template>
