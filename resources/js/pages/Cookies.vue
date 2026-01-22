<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <SeoHead
            title="Cookie policy"
            description="Read how ZapTask uses cookies and similar technologies, what they do, and how you can control your preferences."
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
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 pt-24">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Cookie Policy</h1>

                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        <strong>Last updated:</strong> {{ new Date().toLocaleDateString() }}
                    </p>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. What cookies are</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Cookies are small text files stored on your device that help us recognize your browser, remember preferences, and improve performance.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. How we use cookies</h2>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Essential cookies to keep you signed in and secure.</li>
                            <li>Preference cookies to remember UI settings like appearance.</li>
                            <li>Analytics cookies to understand product usage and improve ZapTask.</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. Managing your preferences</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            You can accept or decline analytics cookies in the consent banner. You can also clear or block cookies in your browser settings.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Contact us</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            If you have questions about cookies, contact us at
                            <a href="mailto:support@zaptask.co.uk" class="text-blue-600 hover:text-blue-700">support@zaptask.co.uk</a>.
                        </p>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    window.location.href = `/#${sectionId}`;
};
</script>
