<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import PublicNavigation from '@/components/PublicNavigation.vue';

// Smooth scroll function
const scrollToSection = (sectionId: string) => {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
};

// Handle URL anchors on page load
onMounted(() => {
    // Check if there's a hash in the URL
    const hash = window.location.hash;
    if (hash) {
        // Remove the # and scroll to the section
        const sectionId = hash.substring(1);
        // Add a small delay to ensure the page is fully loaded
        setTimeout(() => {
            scrollToSection(sectionId);
        }, 100);
    }
});
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Fixed Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center space-x-2">
                            <img src="/zap_icon.png" alt="" class="w-8 h-8 dark:bg-white dark:rounded-md dark:p-1" aria-hidden="true">
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
        <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] text-[#1b1b18] lg:justify-center lg:pt-8 lg:mt-20 dark:bg-[#0a0a0a] pt-24">
        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-4 pb-8 text-[15px] leading-[22px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-12 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-2 text-lg font-medium">Welcome to ZapTask</h1>
                    <p class="mb-4 text-[15px] text-[#706f6c] dark:text-[#A1A09A]">
                        The ultimate task management platform designed for teams that want to get things done efficiently.
                    </p>
                    
                    <!-- Features Section -->
                    <div id="features" class="mb-6 space-y-3">
                        <div class="flex items-start gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 flex-shrink-0">
                                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-base text-[#1b1b18] dark:text-[#EDEDEC]">Kanban Board Management</h3>
                                <p class="text-[14px] text-[#706f6c] dark:text-[#A1A09A]">Organize tasks with intuitive drag-and-drop columns: To Do, In Progress, Q&A/Testing, and Done.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 flex-shrink-0">
                                <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-base text-[#1b1b18] dark:text-[#EDEDEC]">Real-time Collaboration</h3>
                                <p class="text-[14px] text-[#706f6c] dark:text-[#A1A09A]">Work together seamlessly with live updates, comments, and notifications.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 flex-shrink-0">
                                <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-medium text-base text-[#1b1b18] dark:text-[#EDEDEC]">Advanced Features</h3>
                                <p class="text-[14px] text-[#706f6c] dark:text-[#A1A09A]">Subtasks, file attachments, due dates, priorities, custom logos, and powerful filtering.</p>
                            </div>
                        </div>
                    </div>
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a
                                href="/dashboard"
                                class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                            >
                                Get Started
                            </a>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-blue-50 to-indigo-100 lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:from-blue-900/20 dark:to-indigo-900/30"
                >
                    <!-- ZapTask Logo and Branding -->
                    <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                        <!-- ZapTask Icon -->
                        <div class="mb-6">
                            <!-- Light mode: PNG -->
                            <img 
                                src="/zap_icon.png" 
                                alt="ZapTask Logo"
                                class="w-24 h-24 opacity-100 transition-all duration-750 starting:translate-y-6 starting:opacity-0 dark:hidden" 
                            />
                            
                            <!-- Dark mode: White SVG -->
                            <img 
                                src="/zap_icon_white.svg" 
                                alt="ZapTask Logo"
                                class="w-24 h-24 opacity-100 transition-all duration-750 starting:translate-y-6 starting:opacity-0 hidden dark:block" 
                            />
                        </div>
                        
                        <!-- ZapTask Text -->
                        <div class="space-y-2 opacity-100 transition-all delay-300 duration-750 starting:translate-y-4 starting:opacity-0">
                            <h2 class="text-4xl font-bold text-gray-900 dark:text-white">
                                ZapTask
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300 font-medium">
                                Task Management Made Simple
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                Organize your projects, track progress, and collaborate with your team effortlessly.
                            </p>
                            
                        </div>
                        
                        <!-- Decorative Elements -->
                        <div class="absolute top-4 right-4 w-16 h-16 bg-blue-200 dark:bg-blue-800 rounded-full opacity-20"></div>
                        <div class="absolute bottom-8 left-8 w-12 h-12 bg-indigo-200 dark:bg-indigo-800 rounded-full opacity-30"></div>
                        <div class="absolute top-1/2 left-4 w-8 h-8 bg-purple-200 dark:bg-purple-800 rounded-full opacity-25"></div>
                    </div>
                </div>
            </main>
        </div>
        
        <!-- Company Domain & Languages Section -->
        <section class="w-full max-w-6xl mx-auto px-6 py-12 mt-12">
            <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-blue-900/20 dark:via-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-8 md:p-12 border border-blue-200 dark:border-blue-700 shadow-lg">
                <div class="text-center mb-8">
                    <div class="flex items-center justify-center gap-3 mb-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600">
                            <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                        Get Your Own Company Domain
                    </h2>
                    <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-3">
                        Sign up your company on a MIDI or MAXI plan to get your FREE domain
                    </p>
                    <p class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-indigo-400 mb-6">
                        your-company.zaptask.co.uk
                    </p>
                </div>
                
                <div class="border-t border-blue-200 dark:border-blue-700 pt-8">
                    <h3 class="text-2xl md:text-3xl font-bold text-center text-[#1b1b18] dark:text-[#EDEDEC] mb-6">
                        Now Available in 13 Languages
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇬🇧</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">English (en)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇪🇸</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Spanish (es)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇫🇷</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">French (fr)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇩🇪</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">German (de)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇵🇹</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Portuguese (pt)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇮🇹</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Italian (it)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇳🇱</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Dutch (nl)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇷🇺</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Russian (ru)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇯🇵</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Japanese (ja)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇰🇷</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Korean (ko)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇨🇳</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Chinese (zh)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow">
                            <span class="text-2xl">🇸🇦</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Arabic (ar)</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-[#161615] rounded-lg shadow col-span-2 md:col-span-1">
                            <span class="text-2xl">🇮🇱</span>
                            <span class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Hebrew (he)</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Additional Features Section -->
        <section id="features-advanced" class="w-full py-12 mt-12">
            <div class="max-w-6xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- TODO: Replace placeholder PNGs in public/images with real marketing screenshots -->
                    <!-- Calendar Feature -->
                    <div class="bg-white dark:bg-[#161615] rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-center mb-4">
                            <img src="/images/calendar.png" alt="Illustration showcasing the calendar scheduling view" class="rounded-lg shadow-md w-full h-auto max-h-48 object-contain">
                        </div>
                        <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">📅 Calendar View</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Visualize your tasks across the month. Add events directly from the calendar with quick "+ Add" buttons on any day. Current day is highlighted, and tasks with due dates automatically appear on the calendar.
                        </p>
                    </div>
                    
                    <!-- Activity Feed Feature -->
                    <div class="bg-white dark:bg-[#161615] rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-center mb-4">
                            <img src="/images/activity-feed.png" alt="Illustration showing activity feed updates" class="rounded-lg shadow-md w-full h-auto max-h-48 object-contain">
                        </div>
                        <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">📊 Activity Feed</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Track all activity in your workspace with real-time updates. See create, update, and delete actions instantly with visual indicators and timestamps. Filter activities by type using the dropdown menu.
                        </p>
                    </div>
                    
                    <!-- Bulk Editor Feature -->
                    <div class="bg-white dark:bg-[#161615] rounded-lg p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-center mb-4">
                            <img src="/images/bulk-editor.png" alt="Illustration of the bulk editing workflow" class="rounded-lg shadow-md w-full h-auto max-h-48 object-contain">
                        </div>
                        <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">✏️ Bulk Editor</h3>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Edit multiple tasks at once to save time. Select tasks with checkboxes to trigger the bulk editor bar. Change status, priority, assignee, type, due date, or tags for all selected tasks simultaneously.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Pricing Section -->
        <section id="pricing" class="w-full py-8 lg:py-12 mt-12" style="background-color: #E1E7FF;">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl lg:text-4xl font-bold text-[#1b1b18] dark:text-[#A1A09A] mb-4">
                        Choose Your Plan
                    </h2>
                    <p class="text-lg text-[#4a4a45] dark:text-[#D0D0CB] max-w-2xl mx-auto">
                        Select the perfect plan for your team size and project needs
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- FREE Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">⭐ FREE</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£0</div>
                        <div class="text-sm text-[#4a4a45] dark:text-[#D0D0CB]">Forever free</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="space-y-2 text-sm text-[#4a4a45] dark:text-[#D0D0CB]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>1 user</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>3 projects</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>200 todos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Basic features</span>
                            </div>
                        </div>
                    </div>
                    
                    <Link
                        :href="register({ query: { plan: 'FREE' } })"
                        class="w-full inline-block text-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400"
                    >
                        Get Started Free
                    </Link>
                </div>
                
                <!-- MIDI Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">⭐ MIDI</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£6</div>
                        <div class="text-sm text-[#4a4a45] dark:text-[#D0D0CB]">per month</div>
                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">or £60/year (save £12)</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="space-y-2 text-sm text-[#4a4a45] dark:text-[#D0D0CB]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Up to 5 members</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Up to 20 projects</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Unlimited todos</span>
                            </div>
                        </div>
                    </div>
                    
                    <Link
                        :href="register({ query: { plan: 'MIDI' } })"
                        class="w-full inline-block text-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400"
                    >
                        Start MIDI Plan
                    </Link>
                </div>
                
                <!-- MAXI Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border-2 border-amber-500 hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-amber-400 text-[#1b1b18] px-4 py-1 rounded-full text-sm font-medium">Best Value</span>
                    </div>
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">⭐ MAXI</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£12</div>
                        <div class="text-sm text-[#4a4a45] dark:text-[#D0D0CB]">per month</div>
                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">or £120/year (save £24)</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="space-y-2 text-sm text-[#4a4a45] dark:text-[#D0D0CB]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Up to 20 members</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Up to 100 projects</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Unlimited todos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Integrations</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Automations</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Priority support</span>
                            </div>
                        </div>
                    </div>
                    
                    <Link
                        :href="register({ query: { plan: 'MAXI' } })"
                        class="w-full inline-block text-center rounded-lg bg-amber-500 px-6 py-3 text-sm font-medium text-[#1b1b18] hover:bg-amber-600 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                    >
                        Start MAXI Plan
                    </Link>
                </div>
            </div>
            
                <div class="text-center mt-12 text-sm text-[#4a4a45] dark:text-[#D0D0CB]">
                    <p>All plans include our core features: Kanban boards, real-time collaboration, file attachments, and email notifications.</p>
                </div>
            </div>
        </section>
        
        <!-- Demo Account Section -->
        <section class="w-full max-w-4xl mx-auto px-6 py-12">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/30 rounded-lg p-8 text-center border border-blue-200 dark:border-blue-800">
                <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                    Try ZapTask Demo
                </h3>
                <p class="text-lg text-[#4a4a45] dark:text-[#D0D0CB] mb-6">
                    Experience ZapTask with our pre-configured demo account
                </p>
                <div class="bg-white dark:bg-[#161615] rounded-lg p-6 max-w-md mx-auto border border-blue-300 dark:border-blue-700">
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-2">Demo Login Credentials:</div>
                        <div class="text-left space-y-2">
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-[#1a1a19] px-3 py-2 rounded">
                                <span class="text-sm text-[#4a4a45] dark:text-[#D0D0CB]">Email:</span>
                                <span class="text-sm font-mono text-[#1b1b18] dark:text-[#EDEDEC] select-all">demo@zaptask.co.uk</span>
                            </div>
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-[#1a1a19] px-3 py-2 rounded">
                                <span class="text-sm text-[#4a4a45] dark:text-[#D0D0CB]">Password:</span>
                                <span class="text-sm font-mono text-[#1b1b18] dark:text-[#EDEDEC] select-all">zaptask123</span>
                            </div>
                        </div>
                    </div>
                    <Link
                        href="/login"
                        class="mt-4 w-full inline-block text-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-400"
                    >
                        Try Demo Now
                    </Link>
                </div>
            </div>
        </section>
        
        <!-- Footer -->
        <footer class="w-full bg-[#1b1b18] dark:bg-[#0a0a09] py-12 mt-16">
            <div class="max-w-6xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Company Info -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2 mb-4">
                            <img src="/zap_icon_white.svg" alt="" class="w-8 h-8" aria-hidden="true" />
                            <span class="text-xl font-bold text-white">ZapTask</span>
                        </div>
                        <p class="text-gray-300 text-sm mb-4 max-w-md">
                            The ultimate task management platform designed for teams that want to get things done efficiently. Organize projects, track progress, and collaborate seamlessly.
                        </p>
                    </div>
                    
                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link href="/login" class="text-gray-300 hover:text-white transition-colors">
                                    Login
                                </Link>
                            </li>
                            <li>
                                <Link href="/register" class="text-gray-300 hover:text-white transition-colors">
                                    Sign Up
                                </Link>
                            </li>
                            <li>
                                <a href="#pricing" class="text-gray-300 hover:text-white transition-colors">
                                    Pricing
                                </a>
                            </li>
                            <li>
                                <a href="/demo" class="text-gray-300 hover:text-white transition-colors">
                                    Demo Guide
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Support -->
                    <div>
                        <h4 class="text-white font-semibold mb-4">Support</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <a href="/contact" class="text-gray-300 hover:text-white transition-colors">
                                    Contact Support
                                </a>
                            </li>
                            <li>
                                <a href="/privacy" class="text-gray-300 hover:text-white transition-colors">
                                    Privacy Policy
                                </a>
                            </li>
                            <li>
                                <a href="/terms" class="text-gray-300 hover:text-white transition-colors">
                                    Terms of Service
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Bottom Bar -->
                <div class="border-t border-gray-700 mt-8 pt-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="text-gray-400 text-sm mb-4 md:mb-0">
                            Made with ❤️ for productive teams worldwide
                        </div>
                        <div class="text-gray-400 text-sm">
                            ZapTask &copy; 2025 ZapTask.co.uk. All rights reserved.
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        </div>
    </div>
</template>