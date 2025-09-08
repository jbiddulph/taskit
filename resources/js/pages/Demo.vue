<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineOptions({
    layout: false,
});
</script>

<template>
    <Head title="Demo - ZapTask">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <!-- Header -->
        <header class="w-full border-b border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <AppLogoIcon class="w-8 h-8" />
                    <span class="text-xl font-bold">ZapTask</span>
                </Link>
                <nav class="flex items-center gap-4">
                    <Link 
                        :href="login()" 
                        class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors"
                    >
                        Login
                    </Link>
                    <Link 
                        :href="register()" 
                        class="text-sm bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        Sign Up
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Hero Section -->
            <section class="w-full bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/30 py-16">
                <div class="max-w-4xl mx-auto px-6 text-center">
                    <h1 class="text-4xl lg:text-5xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-6">
                        Try ZapTask Demo
                    </h1>
                    <p class="text-xl text-[#706f6c] dark:text-[#A1A09A] mb-8 max-w-2xl mx-auto">
                        Experience ZapTask with our pre-configured demo account. Explore all features and see how easy project management can be.
                    </p>
                    
                    <!-- Demo Login Credentials -->
                    <div class="bg-white dark:bg-[#161615] rounded-lg p-8 max-w-md mx-auto border border-blue-300 dark:border-blue-700 mb-8">
                        <div class="space-y-4">
                            <div class="text-lg font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-4">Demo Login Credentials</div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-[#1a1a19] px-4 py-3 rounded">
                                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Email:</span>
                                    <span class="text-sm font-mono text-[#1b1b18] dark:text-[#EDEDEC] select-all font-medium">demo@zaptask.co.uk</span>
                                </div>
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-[#1a1a19] px-4 py-3 rounded">
                                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Password:</span>
                                    <span class="text-sm font-mono text-[#1b1b18] dark:text-[#EDEDEC] select-all font-medium">zaptask123</span>
                                </div>
                            </div>
                        </div>
                        <Link
                            :href="login()"
                            class="mt-6 w-full inline-block text-center rounded-lg bg-blue-500 px-6 py-3 text-sm font-medium text-white hover:bg-blue-600 transition-colors duration-200"
                        >
                            Try Demo Now
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Getting Started Guide -->
            <section class="w-full py-16">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                            Complete ZapTask Guide
                        </h2>
                        <p class="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto">
                            Learn everything you need to know about ZapTask, from your first login to advanced features.
                        </p>
                    </div>

                    <!-- Step-by-step Guide -->
                    <div class="space-y-12">
                        <!-- Step 1: First Login -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-lg flex-shrink-0">
                                    1
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        🚀 First Login & Project Creation
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        When you first login to ZapTask, you'll need to create your first project to get started.
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                            Click the <strong>"+ New Project"</strong> button in the left sidebar
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                            Give your project a name and choose a color
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                            Your new project will appear in the left sidebar
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Project Management -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 font-bold text-lg flex-shrink-0">
                                    2
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        📂 Managing Projects
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Organize and navigate between your projects efficiently.
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            <strong>View all projects:</strong> All your projects are listed in the left sidebar
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            <strong>Reorder projects:</strong> Click and drag the colored dot next to project names to reorder
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            <strong>Switch projects:</strong> Use the search filter bar at the top or click project names in sidebar
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            <strong>Edit projects:</strong> Right-click project names to edit or delete
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Creating To-Dos -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-lg flex-shrink-0">
                                    3
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        ✅ Creating & Managing To-Dos
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Add tasks and organize them using our intuitive Kanban board.
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                            <strong>Add To-Do:</strong> Click "Add To-Do" button in any column (To Do, In Progress, Q&A/Testing, Done)
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                            <strong>Drag & Drop:</strong> Click and drag tasks between columns to update their status
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                            <strong>Set priorities:</strong> Choose High, Medium, or Low priority levels
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                            <strong>Due dates:</strong> Set deadlines to keep track of important tasks
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                            <strong>Assign tasks:</strong> Assign to team members for collaboration
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Enhanced Features -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 font-bold text-lg flex-shrink-0">
                                    4
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        🔥 Advanced Features
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Enhance your productivity with powerful features.
                                    </p>
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">📷 Images & Attachments</h4>
                                            <ul class="space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                <li>• Click the image icon in task descriptions</li>
                                                <li>• Upload screenshots, documents, or files</li>
                                                <li>• Images display directly in tasks</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">💬 Comments & Notifications</h4>
                                            <ul class="space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                <li>• Add comments to any task</li>
                                                <li>• Task owners get instant notifications</li>
                                                <li>• Check notifications in the top-right bell icon</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Subtasks -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 font-bold text-lg flex-shrink-0">
                                    5
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        🌳 Subtasks & Organization
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Break down complex tasks into manageable subtasks.
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                            <strong>Create subtasks:</strong> Use the dropdown when creating tasks to add subtasks
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                            <strong>Visual hierarchy:</strong> Subtasks are indented and connected with tree lines
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                            <strong>Collapse/Expand:</strong> Use "Hide/Show Subtasks" buttons to manage view
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></span>
                                            <strong>Unique IDs:</strong> Each task has a unique ID (e.g., PROJ-123) for easy reference
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 6: Team Collaboration -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 dark:bg-pink-900 text-pink-600 dark:text-pink-400 font-bold text-lg flex-shrink-0">
                                    6
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        👥 Team Collaboration
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Work together seamlessly with your team members.
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-pink-500 rounded-full"></span>
                                            <strong>Assign to teammates:</strong> Select team members when creating or editing tasks
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-pink-500 rounded-full"></span>
                                            <strong>Real-time updates:</strong> See changes instantly as team members work
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-pink-500 rounded-full"></span>
                                            <strong>Notifications:</strong> Get notified when assigned new tasks or receive comments
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-pink-500 rounded-full"></span>
                                            <strong>User avatars:</strong> See who's assigned to each task with user initials
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 7: Company Settings -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 font-bold text-lg flex-shrink-0">
                                    7
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        🏢 Company Settings & Branding
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Customize your workspace with company branding (MIDI & MAXI plans).
                                    </p>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            <strong>Custom logos:</strong> Click "Company Logo" in Settings (MIDI/MAXI plans only)
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            <strong>Upload guidelines:</strong> Recommended size 216px × 48px for best results
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            <strong>Logo display:</strong> Your logo replaces "ZapTask" in the header and sidebar
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                                            <strong>Revert option:</strong> Easily switch back to default ZapTask branding
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 8: Subscription Management -->
                        <div class="bg-white dark:bg-[#161615] rounded-lg p-8 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400 font-bold text-lg flex-shrink-0">
                                    8
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        💳 Subscription & Plan Management
                                    </h3>
                                    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                        Manage your subscription and upgrade as your team grows.
                                    </p>
                                    <div class="grid md:grid-cols-3 gap-4 mb-4">
                                        <div class="bg-gray-50 dark:bg-[#1a1a19] p-4 rounded-lg">
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">FREE Plan</h4>
                                            <ul class="text-xs text-[#706f6c] dark:text-[#A1A09A] space-y-1">
                                                <li>• Up to 5 members</li>
                                                <li>• Up to 10 projects</li>
                                                <li>• Unlimited todos</li>
                                            </ul>
                                        </div>
                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">MIDI Plan - £6/month</h4>
                                            <ul class="text-xs text-[#706f6c] dark:text-[#A1A09A] space-y-1">
                                                <li>• Up to 10 members</li>
                                                <li>• Up to 20 projects</li>
                                                <li>• Custom logo upload</li>
                                            </ul>
                                        </div>
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">MAXI Plan - £9/month</h4>
                                            <ul class="text-xs text-[#706f6c] dark:text-[#A1A09A] space-y-1">
                                                <li>• Unlimited members</li>
                                                <li>• Unlimited projects</li>
                                                <li>• Custom logo & themes</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <ul class="space-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            <strong>Upgrade/Downgrade:</strong> Visit the Subscription page in Settings
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            <strong>Plan limits:</strong> Get warnings when approaching member/project limits
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                            <strong>Current plan:</strong> View your plan type in the top-right corner of dashboard
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 9: Tips & Tricks -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/30 rounded-lg p-8 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white font-bold text-lg flex-shrink-0">
                                    💡
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                        Pro Tips & Keyboard Shortcuts
                                    </h3>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">⚡ Quick Actions</h4>
                                            <ul class="space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                <li>• Copy task IDs by clicking them</li>
                                                <li>• Use filters to find specific tasks</li>
                                                <li>• Export/import data for backups</li>
                                                <li>• Set up automated task pruning</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">🎯 Best Practices</h4>
                                            <ul class="space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                <li>• Set due dates for important tasks</li>
                                                <li>• Use comments for status updates</li>
                                                <li>• Organize with subtasks for complex work</li>
                                                <li>• Regularly review Q&A/Testing column</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="w-full bg-[#1b1b18] dark:bg-[#0a0a09] py-16">
                <div class="max-w-4xl mx-auto px-6 text-center">
                    <h2 class="text-3xl font-bold text-white mb-4">
                        Ready to Get Started?
                    </h2>
                    <p class="text-gray-300 text-lg mb-8 max-w-2xl mx-auto">
                        Try the demo account to experience all these features, or create your own account to start managing your projects today.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            :href="login()"
                            class="inline-block rounded-lg bg-blue-500 px-8 py-3 text-lg font-medium text-white hover:bg-blue-600 transition-colors duration-200"
                        >
                            Try Demo Account
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-block rounded-lg border border-white px-8 py-3 text-lg font-medium text-white hover:bg-white hover:text-[#1b1b18] transition-colors duration-200"
                        >
                            Create Free Account
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="w-full bg-[#1b1b18] dark:bg-[#0a0a09] py-8 border-t border-gray-700">
            <div class="max-w-6xl mx-auto px-6 text-center">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <AppLogoIcon class="w-6 h-6" />
                    <span class="text-lg font-bold text-white">ZapTask</span>
                </div>
                <div class="text-gray-400 text-sm">
                    © 2025 ZapTask.co.uk. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
