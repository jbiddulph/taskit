<script setup lang="ts">
import { register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';
import {
    SEO_ESTATE_AGENTS_DESCRIPTION,
    SEO_ESTATE_AGENTS_TITLE,
} from '@/constants/seo';

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: 'ZapTask for Estate Agents',
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    description: SEO_ESTATE_AGENTS_DESCRIPTION,
    offers: {
        '@type': 'Offer',
        price: '0',
        priceCurrency: 'GBP',
    },
    featureList: [
        'Property location tasks on a map',
        'Daily viewing routes for negotiators',
        'On-site check-in at listings',
        'Kanban board for sales progression',
        'Voice and meeting notes for morning briefings',
    ],
};
</script>

<template>
    <SeoHead
        :title="SEO_ESTATE_AGENTS_TITLE"
        :description="SEO_ESTATE_AGENTS_DESCRIPTION"
        keywords="estate agent task management, property viewing planner, estate agency software UK, viewing route planner, property task app"
        :json-ld="jsonLd"
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
                    Built for estate agents
                </p>
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-5 leading-tight">
                    Plan viewings, route your team, and never lose track of a listing
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8">
                    ZapTask is simple task management with a map—not another bloated CRM. Pin tasks to properties,
                    see today's viewings on a route, check in when you arrive, and keep sales progression visible on a
                    clear Kanban board. No technical setup required.
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link
                        :href="register()"
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

            <section class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">📍 Tasks on the map</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Attach viewings, valuations, and inspections to property addresses. See every job with a location
                        on one map—perfect for branch managers and negotiators on the road.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">🗺️ My route today</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Filter the map to tasks assigned to you, plan a driving route between viewings, and open Apple or
                        Google Maps with one tap.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">✅ Check in on site</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Negotiators check in when they arrive at a property so the office knows viewings are happening
                        without chasing updates on WhatsApp.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">📋 Sales progression board</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Track offers, surveys, exchanges, and completions on a Kanban board everyone understands—no
                        scrum training required.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">🎙️ Morning briefing notes</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Record your team huddle and let AI turn it into tasks—with locations picked up from speech like
                        "viewing at Brighton Road tomorrow."
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">👥 Small agency friendly</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Free for solo agents. Affordable MIDI and MAXI plans for branches with multiple negotiators,
                        clients, and listing portfolios.
                    </p>
                </div>
            </section>

            <section class="max-w-4xl mx-auto px-6">
                <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-8 md:p-10 text-center text-white">
                    <h2 class="text-2xl md:text-3xl font-bold mb-3">Replace sticky notes and scattered spreadsheets</h2>
                    <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                        Independent agents, lettings teams, and high-street branches use ZapTask to coordinate work
                        without enterprise software complexity.
                    </p>
                    <Link
                        :href="register()"
                        class="inline-flex items-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                    >
                        Create your free account
                    </Link>
                </div>
            </section>
        </main>
    </div>
</template>
