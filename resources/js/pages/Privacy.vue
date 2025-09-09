<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Head title="Privacy Policy" />
        
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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Privacy Policy</h1>
                
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        <strong>Last updated:</strong> {{ new Date().toLocaleDateString() }}
                    </p>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Information We Collect</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            We collect information you provide directly to us, such as when you create an account, use our services, or contact us for support.
                        </p>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Account information (name, email address, password)</li>
                            <li>Profile information and company details</li>
                            <li>Project and task data you create using our service</li>
                            <li>Communications and feedback you send to us</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. How We Use Your Information</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            We use the information we collect to:
                        </p>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Provide, maintain, and improve our services</li>
                            <li>Process transactions and send related information</li>
                            <li>Send technical notices and support messages</li>
                            <li>Respond to your comments and questions</li>
                            <li>Monitor and analyze usage patterns</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. Information Sharing</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            We do not sell, trade, or otherwise transfer your personal information to third parties except as described in this policy:
                        </p>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>With your consent or at your direction</li>
                            <li>To comply with legal obligations</li>
                            <li>To protect our rights and prevent fraud</li>
                            <li>With service providers who assist us in operating our service</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Data Security</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">5. Data Retention</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We retain your personal information for as long as necessary to provide our services and fulfill the purposes outlined in this policy, unless a longer retention period is required by law.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">6. Your Rights</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            You have the right to:
                        </p>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Access, update, or delete your personal information</li>
                            <li>Object to or restrict certain processing of your data</li>
                            <li>Data portability</li>
                            <li>Withdraw consent where we rely on consent to process your data</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">7. Cookies and Tracking</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We use cookies and similar tracking technologies to enhance your experience and analyze usage patterns. You can control cookie settings through your browser preferences.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">8. Contact Us</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            If you have any questions about this Privacy Policy, please contact us through our 
                            <Link href="/contact" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                contact form
                            </Link>.
                        </p>
                    </section>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-gray-400">&copy; 2025 ZapTask.co.uk. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    // Navigate to homepage with anchor
    window.location.href = `/#${sectionId}`;
};
</script>
