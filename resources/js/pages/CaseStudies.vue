<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';

const studies = [
    {
        title: 'Brighton estate agency',
        industry: 'Estate agents',
        before: 'Negotiators juggled viewings in WhatsApp and spreadsheets. Routes were rebuilt every morning.',
        after: 'Viewings on a map with a daily route, voice tasks between appointments, and check-in at each listing.',
        metric: '4 viewings per negotiator per day → 6+ with less admin',
    },
    {
        title: 'South Coast maintenance team',
        industry: 'Trades & maintenance',
        before: 'Job sheets lived in email. Overdue repairs were easy to miss when crews were on the road.',
        after: 'Maintenance board per contract, overdue alerts with addresses, and offline check-ins when signal drops.',
        metric: 'Same-day job updates without calling the office',
    },
];
</script>

<template>
    <SeoHead
        title="Case studies | ZapTask"
        description="How estate agents and field teams use ZapTask for viewings, routes, voice tasks, and on-site check-in."
        image="/zap_icon.png"
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

        <main class="pt-24 pb-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Case studies</h1>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                    Real workflows for teams on the move — not generic project management.
                </p>

                <div class="mt-10 space-y-8">
                    <article
                        v-for="study in studies"
                        :key="study.title"
                        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm"
                    >
                        <p class="text-sm font-medium text-violet-600 dark:text-violet-300">{{ study.industry }}</p>
                        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ study.title }}</h2>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Before</p>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ study.before }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">After ZapTask</p>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ study.after }}</p>
                            </div>
                        </div>

                        <p class="mt-4 rounded-md bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                            {{ study.metric }}
                        </p>
                    </article>
                </div>

                <div class="mt-12 text-center">
                    <Link
                        href="/register"
                        class="inline-flex items-center rounded-lg bg-black px-6 py-3 text-sm font-semibold text-white hover:bg-gray-900 dark:bg-white dark:text-black"
                    >
                        Start free — see it on your next viewing day
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
