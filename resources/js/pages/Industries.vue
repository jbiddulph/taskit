<script setup lang="ts">
import { register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';
import { INDUSTRY_LANDING_PAGES } from '@/constants/industryLandingPages';

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: 'ZapTask by industry',
    description: 'Industry-specific task management for estate agents, trades, hospitality, and more.',
};
</script>

<template>
    <SeoHead
        title="Task Management by Industry | ZapTask"
        description="ZapTask for estate agents, electricians, restaurants, schools, and 16 more industries. Location tasks, maps, routes, and Kanban boards built for teams on the move."
        keywords="industry task management, field team software, trade business task app, estate agent software UK"
        :json-ld="jsonLd"
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
                    <PublicNavigation />
                </div>
            </div>
        </header>

        <main class="pt-24 pb-16 max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    ZapTask for your industry
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Same simple product—maps, routes, voice tasks, and Kanban boards—with examples and workflows
                    tailored to how your team actually works.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <Link
                    v-for="industry in INDUSTRY_LANDING_PAGES"
                    :key="industry.slug"
                    :href="`/${industry.slug}`"
                    class="rounded-xl border border-gray-200 bg-white p-5 hover:shadow-md hover:border-blue-200 transition-shadow dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-800"
                >
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">
                        {{ industry.features[0]?.icon }} {{ industry.label }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-3">
                        {{ industry.headline }}
                    </p>
                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        See {{ industry.label.toLowerCase() }} workflows →
                    </span>
                </Link>
            </div>

            <div class="mt-12 text-center">
                <Link
                    :href="register()"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Start free — no card needed
                </Link>
            </div>
        </main>
    </div>
</template>
