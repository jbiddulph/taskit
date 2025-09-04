<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>
        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-1 font-medium">Let's get started</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                        TaskIT is a powerful task management system. <br />Get started with these features.
                    </p>
                    <ul class="mb-4 flex flex-col lg:mb-6">
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-1/2 before:bottom-0 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span class="relative bg-white py-1 dark:bg-[#161615]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                </span>
                            </span>
                            <span>
                                Read the
                                <a
                                    href="#"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                                >
                                    <span>Dashboard</span>
                                    <svg
                                        width="{10}"
                                        height="{11}"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-2.5 w-2.5"
                                    >
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001" stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:top-0 before:bottom-1/2 before:left-[0.4rem] before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A]"
                        >
                            <span class="relative bg-white py-1 dark:bg-[#161615]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                </span>
                            </span>
                            <span>
                                Create your first project at
                                <a
                                    href="/dashboard"
                                    class="ml-1 inline-flex items-center space-x-1 font-medium text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                                >
                                    <span>Dashboard</span>
                                    <svg
                                        width="{10}"
                                        height="{11}"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-2.5 w-2.5"
                                    >
                                        <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001" stroke="currentColor" stroke-linecap="square" />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
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
                    <!-- TaskIT Logo and Branding -->
                    <div class="flex flex-col items-center justify-center h-full p-8 text-center">
                        <!-- TaskIT Icon -->
                        <div class="mb-6">
                            <svg 
                                class="w-24 h-24 text-blue-600 dark:text-blue-400 opacity-100 transition-all duration-750 starting:translate-y-6 starting:opacity-0" 
                                xmlns="http://www.w3.org/2000/svg" 
                                viewBox="0 0 24 24" 
                                fill="currentColor"
                            >
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                            </svg>
                        </div>
                        
                        <!-- TaskIT Text -->
                        <div class="space-y-2 opacity-100 transition-all delay-300 duration-750 starting:translate-y-4 starting:opacity-0">
                            <h2 class="text-4xl font-bold text-gray-900 dark:text-white">
                                TaskIT
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
        
        <!-- Pricing Section -->
        <section class="w-full max-w-6xl mx-auto px-6 py-16 lg:py-24">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                    Choose Your Plan
                </h2>
                <p class="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto">
                    Select the perfect plan for your team size and project needs
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- FREE Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">FREE</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£0</div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Forever free</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-3">Perfect for:</div>
                        <div class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Individuals - unlimited projects & todos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Small companies - up to 5 members</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Up to 10 projects</span>
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
                        :href="register()"
                        class="w-full inline-block text-center rounded-lg border border-[#19140035] px-6 py-3 text-sm font-medium text-[#1b1b18] hover:border-[#1915014a] hover:bg-gray-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b] dark:hover:bg-[#1a1a19] transition-colors duration-200"
                    >
                        Get Started Free
                    </Link>
                </div>
                
                <!-- MIDI Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border-2 border-blue-500 hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-medium">Most Popular</span>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">MIDI</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£6</div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">per month</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-3">Perfect for:</div>
                        <div class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Small-medium companies - up to 10 members</span>
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
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Custom logo upload</span>
                            </div>
                        </div>
                    </div>
                    
                    <Link
                        :href="register()"
                        class="w-full inline-block text-center rounded-lg bg-blue-500 px-6 py-3 text-sm font-medium text-white hover:bg-blue-600 transition-colors duration-200"
                    >
                        Start MIDI Plan
                    </Link>
                </div>
                
                <!-- MAXI Plan -->
                <div class="relative bg-white dark:bg-[#161615] rounded-lg p-8 shadow-[0_4px_20px_rgba(0,0,0,0.1)] border border-[#e3e3e0] dark:border-[#3E3E3A] hover:shadow-[0_8px_30px_rgba(0,0,0,0.15)] transition-shadow duration-300">
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">MAXI</h3>
                        <div class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-1">£9</div>
                        <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">per month</div>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-3">Perfect for:</div>
                        <div class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Medium-large companies - unlimited members</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Unlimited projects</span>
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
                                <span>Custom logo upload</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Custom color themes</span>
                            </div>
                        </div>
                    </div>
                    
                    <Link
                        :href="register()"
                        class="w-full inline-block text-center rounded-lg border border-[#19140035] px-6 py-3 text-sm font-medium text-[#1b1b18] hover:border-[#1915014a] hover:bg-gray-50 dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b] dark:hover:bg-[#1a1a19] transition-colors duration-200"
                    >
                        Start MAXI Plan
                    </Link>
                </div>
            </div>
            
            <div class="text-center mt-12 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                <p>All plans include our core features: Kanban boards, real-time collaboration, file attachments, and email notifications.</p>
            </div>
        </section>
        
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>