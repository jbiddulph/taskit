<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Globe, Users, Calendar, CheckCircle, LogOut, Plus, FolderOpen } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import TodoBoard from '@/components/TodoBoard.vue';

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    logo_url?: string;
    subdomain?: string;
    subdomain_url?: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Todo {
    id: number;
    title: string;
    description?: string;
    status: string;
    priority: string;
    due_date?: string;
}

interface Project {
    id: number;
    name: string;
    description?: string;
    color: string;
    is_active: boolean;
    owner: User;
}

interface Props {
    company: Company;
    users: User[];
    todos: Todo[];
    projects: Project[];
    isSubdomain: boolean;
}

const props = defineProps<Props>();

const logout = () => {
    // Handle logout
    window.location.href = '/logout';
};

const goToMainSite = () => {
    // Redirect to main site for full functionality
    window.location.href = 'https://zaptask.co.uk/dashboard';
};
</script>

<template>
    <Head :title="`Dashboard - ${company.name}`" />
    
    <AppLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 shadow-sm border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                <Globe class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ company.name }}
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Company Dashboard
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button @click="goToMainSite" class="flex items-center gap-2">
                                <Globe class="w-4 h-4" />
                                Full Dashboard
                            </Button>
                            <Button @click="logout" variant="outline" class="flex items-center gap-2">
                                <LogOut class="w-4 h-4" />
                                Logout
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Company Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ company.name }} Portal
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Welcome to your company's task management portal
                    </p>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Team Members
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2">
                                <Users class="w-8 h-8 text-blue-600" />
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ users.length }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Active members
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total Tasks
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2">
                                <Calendar class="w-8 h-8 text-green-600" />
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ todos.length }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Company tasks
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Projects
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2">
                                <FolderOpen class="w-8 h-8 text-purple-600" />
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ projects.length }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Active projects
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Todo Board -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Task Management
                        </h2>
                        <Button @click="goToMainSite" class="flex items-center gap-2">
                            <Plus class="w-4 h-4" />
                            Full Dashboard
                        </Button>
                    </div>
                    <TodoBoard />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
