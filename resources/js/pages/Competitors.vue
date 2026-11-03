<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <SeoHead
            title="ZapTask vs Trello, Asana & Monday"
            description="Compare ZapTask with other tools for estate agents and small teams. Get location tasks, maps, and simple Kanban—without enterprise bloat or dev-team jargon."
            keywords="ZapTask vs Trello, task management for estate agents, small business project tool comparison"
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
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ZapTask vs other task tools</h1>
                <p class="text-gray-600 dark:text-gray-300 text-base md:text-lg max-w-3xl">
                    Most project tools are built for software teams. ZapTask is different: location tasks on a map, routes for field staff, and boards simple enough for estate agents, trades, and small businesses.
                </p>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <Link
                    v-for="comparison in comparisons"
                    :key="comparison.slug"
                    :href="`/vs/${comparison.slug}`"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:border-blue-300 dark:hover:border-blue-700 transition-colors"
                >
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        ZapTask vs {{ comparison.name }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        {{ comparison.summary }}
                    </p>
                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        Read the full comparison →
                    </span>
                </Link>
            </section>

            <section class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center gap-6">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Try ZapTask alongside your current tool</h2>
                    <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 mb-4 max-w-2xl">
                        You don't need to migrate everything at once. Start with a single project or client in ZapTask and see how your team feels about the focused workflow.
                    </p>
                </div>
                <div>
                    <Link
                        href="/demo"
                        class="inline-flex items-center justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors"
                    >
                        Explore the Demo
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

const comparisons = [
    {
        slug: 'trello',
        name: 'Trello',
        summary: 'Trello is great for simple boards, but it can get messy as teams and projects grow.',
    },
    {
        slug: 'asana',
        name: 'Asana',
        summary: 'Asana is powerful but can feel heavy for teams that just need a clear board.',
    },
    {
        slug: 'monday',
        name: 'Monday.com',
        summary: 'Monday.com can feel like a full work OS when you mainly need jobs, maps, and reminders.',
    },
    {
        slug: 'jira',
        name: 'Jira',
        summary: 'Jira is powerful for big enterprises, but often overkill for product teams and agencies.',
    },
];

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    // Navigate to homepage with anchor
    window.location.href = `/#${sectionId}`;
};
</script>
