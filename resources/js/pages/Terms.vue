<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <SeoHead
            title="Terms of service"
            description="Review the ZapTask terms of service for using the platform."
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
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Terms of Service</h1>
                
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        <strong>Last updated:</strong> {{ new Date().toLocaleDateString() }}
                    </p>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Acceptance of Terms</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            By accessing and using ZapTask, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. Service Description</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            ZapTask is a project management and task tracking platform that allows teams to collaborate, organize projects, and manage workflows efficiently.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. User Accounts</h2>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>You must provide accurate and complete registration information</li>
                            <li>You are responsible for maintaining the security of your account</li>
                            <li>You are responsible for all activities that occur under your account</li>
                            <li>You must notify us immediately of any unauthorized use of your account</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Acceptable Use</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">You agree not to use the service to:</p>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Upload, post, or transmit any unlawful, harmful, or objectionable content</li>
                            <li>Violate any applicable laws or regulations</li>
                            <li>Infringe on the rights of others</li>
                            <li>Attempt to gain unauthorized access to our systems</li>
                            <li>Use the service for any commercial purpose without authorization</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">5. Subscription Plans</h2>
                        <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                            <li>Free plans are subject to usage limitations</li>
                            <li>Paid subscriptions are billed in advance on a monthly basis</li>
                            <li>You may cancel your subscription at any time</li>
                            <li>Refunds are provided according to our refund policy</li>
                            <li>We reserve the right to change pricing with reasonable notice</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">6. Data Ownership</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            You retain ownership of all data, content, and materials you upload to or create within ZapTask. We do not claim ownership of your content, but you grant us a limited license to use it to provide our services.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">7. Service Availability</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            While we strive to maintain high availability, we do not guarantee uninterrupted service. We may perform maintenance, updates, or modifications that temporarily affect service availability.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">8. Limitation of Liability</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            ZapTask shall not be liable for any indirect, incidental, special, or consequential damages arising out of or in connection with your use of the service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">9. Termination</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We may terminate or suspend your account and access to the service at our sole discretion, without prior notice, for conduct that we believe violates these Terms of Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">10. Modifications</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We reserve the right to modify these terms at any time. We will notify users of significant changes via email or through the service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">11. Contact Information</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            If you have any questions about these Terms of Service, please contact us through our 
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
import { Link } from '@inertiajs/vue3';
import PublicNavigation from '@/components/PublicNavigation.vue';
import SeoHead from '@/components/SeoHead.vue';

// Navigate to homepage section function
const scrollToSection = (sectionId: string) => {
    // Navigate to homepage with anchor
    window.location.href = `/#${sectionId}`;
};
</script>
