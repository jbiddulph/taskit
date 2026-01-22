<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        image?: string;
        canonical?: string;
        type?: string;
    }>(),
    {
        type: 'website',
    }
);

const page = usePage<{ name?: string }>();
const appName = computed(() => page.props.name ?? 'ZapTask');

const fullTitle = computed(() => {
    if (props.title.includes(appName.value)) {
        return props.title;
    }

    return `${props.title} - ${appName.value}`;
});

const origin = computed(() => (typeof window !== 'undefined' ? window.location.origin : ''));

const defaultDescription =
    'ZapTask is a focused Kanban task manager for teams and agencies, combining real-time collaboration, automation, and reporting so you ship work faster.';

const normalizedDescription = computed(() => {
    const value = props.description?.trim() ?? '';
    if (value.length >= 150 && value.length <= 160) {
        return value;
    }

    return defaultDescription;
});

const normalizedPath = computed(() => {
    const rawUrl = page.url ?? '';
    return rawUrl.split('?')[0];
});

const canonicalUrl = computed(() => {
    if (props.canonical) {
        return props.canonical;
    }

    if (!origin.value) {
        return normalizedPath.value;
    }

    return `${origin.value}${normalizedPath.value}`;
});

const resolveUrl = (input?: string) => {
    if (!input) return '';
    if (input.startsWith('http://') || input.startsWith('https://')) return input;

    const normalized = input.startsWith('/') ? input : `/${input}`;
    return origin.value ? `${origin.value}${normalized}` : normalized;
};

const ogImageUrl = computed(() => resolveUrl(props.image ?? '/zap_icon.png'));
</script>

<template>
    <Head :title="props.title">
        <meta name="description" :content="normalizedDescription" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:type" :content="props.type" />
        <meta property="og:title" :content="fullTitle" />
        <meta property="og:description" :content="normalizedDescription" />
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="fullTitle" />
        <meta name="twitter:description" :content="normalizedDescription" />
        <meta name="twitter:image" :content="ogImageUrl" />
    </Head>
</template>
