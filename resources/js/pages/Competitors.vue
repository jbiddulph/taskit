<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Head title="Competitors - ZapTask" />
        
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
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ZapTask vs Competitors</h1>
                <p class="text-gray-600 dark:text-gray-300 text-base md:text-lg max-w-3xl">
                    See how ZapTask compares to other project and task management tools. We focus on clarity, speed, and a distraction-free experience for teams that just want to ship work.
                </p>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <!-- Trello -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ZapTask vs Trello</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Trello is great for simple boards, but it can get messy as teams and projects grow.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>✅ ZapTask has opinionated workflows (Todo → In Progress → Q&A → Done)</li>
                        <li>✅ Built-in project limits and guardrails to keep boards focused</li>
                        <li>✅ Real-time collaboration and activity feed out of the box</li>
                    </ul>
                </div>

                <!-- Asana -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ZapTask vs Asana</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Asana is powerful but can feel heavy for teams that just need a clear board.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>✅ ZapTask keeps the interface lean and focused on tasks</li>
                        <li>✅ Less configuration, more doing – get started in minutes</li>
                        <li>✅ Transparent pricing with simple tiers</li>
                    </ul>
                </div>

                <!-- Jira -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ZapTask vs Jira</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Jira is powerful for big enterprises, but often overkill for product teams and agencies.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>✅ ZapTask is easier to onboard clients and non-technical stakeholders</li>
                        <li>✅ No complex workflows or admin overhead</li>
                        <li>✅ Fast, clean UI tuned for everyday execution</li>
                    </ul>
                </div>
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
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    // Navigate to homepage with anchor
    window.location.href = `/#${sectionId}`;
};
</script>


