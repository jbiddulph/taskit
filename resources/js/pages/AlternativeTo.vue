<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <SeoHead
            title="Alternative to project management tools"
            description="Compare ZapTask with Trello, Asana, and Jira for a simpler, opinionated Kanban workflow."
            image="/zap_icon.png"
        />
        
        <!-- Fixed Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center space-x-2">
                            <img src="/zap_icon.png" alt="ZapTask" class="w-8 h-8 dark:bg-white dark:rounded-md dark:p-1">
                            <span class="text-xl font-bold text-gray-900 dark:text-white">ZapTask</span>
                        </Link>
                    </div>
                    <nav class="hidden md:flex items-center space-x-8">
                        <button @click="scrollToSection('features')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
                            Features
                        </button>
                        <button @click="scrollToSection('pricing')" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer">
                            Pricing
                        </button>
                        <a href="/competitors" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Competitors
                        </a>
                        <a href="/alternative-to" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Alternative to
                        </a>
                        <a href="/demo#guide" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Guide
                        </a>
                        <a href="/demo" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Demo
                        </a>
                        <a href="/contact" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                            Contact
                        </a>
                    </nav>
                    <PublicNavigation />
                </div>
            </div>
        </header>

        <!-- Main Content with padding for fixed header -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pt-24">
            <section class="mb-12">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Looking for an alternative to your current tool?</h1>
                <p class="text-gray-600 dark:text-gray-300 text-base md:text-lg max-w-3xl">
                    ZapTask is a focused, opinionated alternative to complex project management tools. If you're feeling overwhelmed by configuration and endless views, ZapTask keeps everyone aligned around a single, clear board.
                </p>
            </section>

            <section class="space-y-8 mb-12">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">Alternative to Trello</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                        Trello is simple, but boards can quickly turn into a wall of cards with no structure.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>ZapTask has a clear, opinionated workflow with built-in columns.</li>
                        <li>Powerful filtering, bulk editing, and activity feed for growing teams.</li>
                        <li>Simple pricing: start free, then upgrade when your team needs more.</li>
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">Alternative to Asana</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                        Asana offers many views and workflows, which can be overwhelming for smaller teams.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>ZapTask keeps everyone on one board with just enough structure.</li>
                        <li>Fast to onboard clients and non-technical stakeholders.</li>
                        <li>Designed for product teams, agencies, and founders.</li>
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">Alternative to Jira</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                        Jira is built for large enterprises and can feel like too much for everyday product work.
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>ZapTask removes admin overhead and complex workflows.</li>
                        <li>Lightweight enough for fast-moving teams, but powerful where it matters.</li>
                        <li>Great for agencies that need a clean client-facing board.</li>
                    </ul>
                </div>
            </section>

            <section class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-xl p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center gap-6">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Try ZapTask as your next step</h2>
                    <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 mb-4 max-w-2xl">
                        Keep your existing tool for historical projects, and start your next sprint, client, or product in ZapTask. You can always export later if you fully switch.
                    </p>
                </div>
                <div>
                    <Link
                        href="/demo"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                    >
                        See ZapTask in Action
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-8 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-gray-400">&copy; 2025 ZapTask.co.uk. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    // Navigate to homepage with anchor
    window.location.href = `/#${sectionId}`;
};
</script>

