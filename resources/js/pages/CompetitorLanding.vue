<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    COMPETITOR_LANDING_PAGES,
    getCompetitorLandingPage,
    type CompetitorLandingKind,
    type CompetitorLandingPage,
} from '@/constants/competitorLandingPages';

const props = defineProps<{
    kind: CompetitorLandingKind;
    slug: string;
    seo?: {
        title: string;
        description: string;
        keywords: string;
        canonical: string;
        image: string;
    };
}>();

const page = computed<CompetitorLandingPage>(() => {
    const match = getCompetitorLandingPage(props.kind, props.slug);
    if (!match) {
        throw new Error(`Unknown competitor landing page: ${props.kind}/${props.slug}`);
    }
    return match;
});

const seoTitle = computed(() => props.seo?.title || `ZapTask ${props.kind === 'comparison' ? 'vs' : 'alternative to'} ${page.value.name}`);
const seoDescription = computed(() => props.seo?.description || page.value.subheadline);
const seoKeywords = computed(() => props.seo?.keywords || '');
const seoCanonical = computed(
    () =>
        props.seo?.canonical ||
        `https://www.zaptask.co.uk/${props.kind === 'comparison' ? 'vs' : 'alternative-to'}/${page.value.slug}`,
);
const related = computed(() =>
    COMPETITOR_LANDING_PAGES.filter((entry) => entry.kind === props.kind && entry.slug !== props.slug).slice(0, 4),
);

const hubHref = computed(() => (props.kind === 'comparison' ? '/competitors' : '/alternative-to'));
const hubLabel = computed(() => (props.kind === 'comparison' ? 'All comparisons' : 'All alternatives'));

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    name: seoTitle.value,
    description: seoDescription.value,
    url: seoCanonical.value,
}));
</script>

<template>
    <SeoHead
        :title="seoTitle"
        :description="seoDescription"
        :keywords="seoKeywords"
        :json-ld="jsonLd"
        :canonical="seoCanonical"
        :image="seo?.image || '/zap_icon.png'"
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
                        <a href="/competitors" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Competitors</a>
                        <a href="/alternative-to" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Alternatives</a>
                        <a href="/demo" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Demo</a>
                    </nav>
                    <PublicNavigation />
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16">
            <section class="max-w-4xl mx-auto px-6 mb-12">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-3">
                    {{ kind === 'comparison' ? `ZapTask vs ${page.name}` : `Alternative to ${page.name}` }}
                </p>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-5 leading-tight">
                    {{ page.headline }}
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    {{ page.subheadline }}
                </p>
                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/register"
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

            <section class="max-w-4xl mx-auto px-6 mb-16">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                    Why teams choose ZapTask
                </h2>
                <ul class="space-y-4">
                    <li
                        v-for="point in page.points"
                        :key="point"
                        class="rounded-xl border border-gray-200 bg-white p-5 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        {{ point }}
                    </li>
                </ul>
            </section>

            <section class="max-w-4xl mx-auto px-6 mb-16">
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-8 dark:border-blue-800 dark:bg-blue-950/40">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        {{ page.ctaHeadline }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Keep your current tool for history if you need to. Start the next project, client, or site visit workflow in ZapTask.
                    </p>
                    <Link
                        href="/demo"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Explore the demo
                    </Link>
                </div>
            </section>

            <section class="max-w-4xl mx-auto px-6">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">More pages like this</h2>
                    <Link :href="hubHref" class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        {{ hubLabel }} →
                    </Link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Link
                        v-for="entry in related"
                        :key="`${entry.kind}-${entry.slug}`"
                        :href="kind === 'comparison' ? `/vs/${entry.slug}` : `/alternative-to/${entry.slug}`"
                        class="rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-200 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-800"
                    >
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ kind === 'comparison' ? `ZapTask vs ${entry.name}` : `Alternative to ${entry.name}` }}
                        </p>
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
