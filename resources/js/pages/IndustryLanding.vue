<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    getIndustryLandingPage,
    INDUSTRY_LANDING_PAGES,
    type IndustryLandingPage,
} from '@/constants/industryLandingPages';

const props = defineProps<{
    slug: string;
}>();

const page = computed<IndustryLandingPage>(() => {
    const match = getIndustryLandingPage(props.slug);
    if (!match) {
        throw new Error(`Unknown industry landing page: ${props.slug}`);
    }
    return match;
});

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: `ZapTask for ${page.value.label}`,
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    description: page.value.seoDescription,
    offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'GBP',
    },
    featureList: page.value.features.map((feature) => feature.title),
}));

const otherIndustries = computed(() =>
    INDUSTRY_LANDING_PAGES.filter((industry) => industry.slug !== props.slug).slice(0, 8),
);

const registerUrl = computed(() => `/register?industry=${encodeURIComponent(props.slug)}`);
</script>

<template>
    <SeoHead
        :title="page.seoTitle"
        :description="page.seoDescription"
        :keywords="page.keywords"
        :json-ld="jsonLd"
        :canonical="`https://www.zaptask.co.uk/${page.slug}`"
        image="/images/activity-feed.png"
    />
    <Head>
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <Link href="/" class="flex items-center space-x-2">
                        <img src="/zap_icon.png" alt="ZapTask" class="w-8 h-8 dark:bg-white dark:rounded-md dark:p-1" />
                        <span class="text-xl font-bold text-gray-900 dark:text-white">ZapTask</span>
                    </Link>
                    <nav class="hidden md:flex items-center space-x-6 text-sm">
                        <a href="/#features" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Features</a>
                        <a href="/#pricing" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Pricing</a>
                        <a href="/demo" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Demo</a>
                    </nav>
                    <PublicNavigation />
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <section class="max-w-5xl mx-auto px-6 text-center mb-16">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-3">
                    {{ page.audienceLabel }}
                </p>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-5 leading-tight">
                    {{ page.headline }}
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8">
                    {{ page.subheadline }}
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link
                        :href="registerUrl"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Start free — no card needed
                    </Link>
                    <Link
                        href="/demo"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-800 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800"
                    >
                        Try the demo
                    </Link>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-6 mb-16">
                <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-8">
                    See how {{ page.label.toLowerCase() }} use ZapTask
                </h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <figure
                        v-for="(screenshot, index) in page.screenshots"
                        :key="index"
                        class="rounded-xl border border-gray-200 bg-white overflow-hidden dark:border-gray-700 dark:bg-gray-800"
                    >
                        <img
                            :src="screenshot.src"
                            :alt="screenshot.alt"
                            class="w-full h-48 object-cover object-top bg-gray-100 dark:bg-gray-900"
                            loading="lazy"
                        />
                        <figcaption class="p-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ screenshot.caption }}
                        </figcaption>
                    </figure>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
                <div
                    v-for="feature in page.features"
                    :key="feature.title"
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ feature.icon }} {{ feature.title }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ feature.description }}
                    </p>
                </div>
            </section>

            <section class="max-w-4xl mx-auto px-6 mb-16">
                <div class="rounded-xl border border-gray-200 bg-white p-6 md:p-8 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">
                        Example workflow for {{ page.label.toLowerCase() }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
                        Starter templates you can recreate in minutes—tasks, locations, and a board your team will recognise.
                    </p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div
                            v-for="task in page.exampleTasks"
                            :key="task.title"
                            class="rounded-lg border border-gray-200 p-4 dark:border-gray-600"
                        >
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
                                <span class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                    {{ task.column }}
                                </span>
                            </div>
                            <p v-if="task.location" class="text-xs text-gray-500 dark:text-gray-400">
                                📍 {{ task.location }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="max-w-4xl mx-auto px-6 mb-16">
                <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-8 md:p-10 text-center text-white">
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">{{ page.ctaHeadline }}</h2>
                    <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                        {{ page.ctaSubheadline }}
                    </p>
                    <Link
                        :href="registerUrl"
                        class="inline-flex items-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                    >
                        Create your free account
                    </Link>
                </div>
            </section>

            <section v-if="otherIndustries.length" class="max-w-4xl mx-auto px-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">
                    ZapTask for other industries
                </h2>
                <div class="flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="industry in otherIndustries"
                        :key="industry.slug"
                        :href="`/${industry.slug}`"
                        class="rounded-full border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        {{ industry.label }}
                    </Link>
                    <Link
                        href="/industries"
                        class="rounded-full border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950/40 dark:text-blue-200"
                    >
                        View all industries
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
