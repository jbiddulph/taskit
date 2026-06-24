<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SEO_DEFAULT_DESCRIPTION } from '@/constants/seo';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        image?: string;
        canonical?: string;
        type?: string;
        keywords?: string;
        jsonLd?: Record<string, unknown> | Record<string, unknown>[];
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

    return `${props.title} | ${appName.value}`;
});

const origin = computed(() => (typeof window !== 'undefined' ? window.location.origin : 'https://www.zaptask.co.uk'));

const normalizedDescription = computed(() => {
    const value = props.description?.trim() ?? '';
    if (!value) {
        return SEO_DEFAULT_DESCRIPTION;
    }

    if (value.length <= 160) {
        return value;
    }

    return `${value.slice(0, 157).trimEnd()}...`;
});

const normalizedPath = computed(() => {
    const rawUrl = page.url ?? '';
    return rawUrl.split('?')[0];
});

const canonicalUrl = computed(() => {
    if (props.canonical) {
        return props.canonical;
    }

    return `${origin.value}${normalizedPath.value}`;
});

const resolveUrl = (input?: string) => {
    if (!input) return '';
    if (input.startsWith('http://') || input.startsWith('https://')) return input;

    const normalized = input.startsWith('/') ? input : `/${input}`;
    return `${origin.value}${normalized}`;
};

const ogImageUrl = computed(() => resolveUrl(props.image ?? '/zap_icon.png'));

const jsonLdPayload = computed(() => {
    if (!props.jsonLd) {
        return null;
    }

    return JSON.stringify(props.jsonLd);
});
</script>

<template>
    <Head :title="props.title">
        <meta name="description" :content="normalizedDescription" />
        <meta v-if="props.keywords" name="keywords" :content="props.keywords" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:type" :content="props.type" />
        <meta property="og:title" :content="fullTitle" />
        <meta property="og:description" :content="normalizedDescription" />
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:site_name" :content="appName" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="fullTitle" />
        <meta name="twitter:description" :content="normalizedDescription" />
        <meta name="twitter:image" :content="ogImageUrl" />
        <component
            :is="'script'"
            v-if="jsonLdPayload"
            type="application/ld+json"
            v-html="jsonLdPayload"
        />
    </Head>
</template>
