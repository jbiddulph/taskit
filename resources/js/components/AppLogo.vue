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
    <!-- Custom Logo Display - Logo only, no text -->
    <div v-if="hasCustomLogo" class="flex items-center">
        <img 
            :src="customLogoUrl"
            :alt="company?.name + ' logo'"
            class="object-contain"
        />
    </div>

    <!-- Default ZapTask Logo -->
    <template v-else>
        <div>
            <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
        </div>
        <div class="grid flex-1 text-left text-sm">
            <span class="mb-0.5 truncate leading-tight font-semibold">ZapTask</span>
        </div>
    </template>
</template>
