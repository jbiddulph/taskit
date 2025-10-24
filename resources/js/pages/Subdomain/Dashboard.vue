<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Globe, Users, Calendar, CheckCircle, LogOut } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

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

interface Props {
    company: Company;
    users: User[];
    todos: Todo[];
    isSubdomain: boolean;
}

const props = defineProps<Props>();

const logout = () => {
    // Handle logout
    window.location.href = '/logout';
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
                        <Button @click="logout" variant="outline" class="flex items-center gap-2">
                            <LogOut class="w-4 h-4" />
                            Logout
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Users Card -->
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

                    <!-- Todos Card -->
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

                    <!-- Company Info Card -->
                    <Card>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Company Plan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-8 h-8 text-purple-600" />
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ company.subscription_type }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Subscription
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Team Members -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle>Team Members</CardTitle>
                        <CardDescription>
                            Current team members in {{ company.name }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-for="user in users" :key="user.id" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Tasks -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Tasks</CardTitle>
                        <CardDescription>
                            Latest tasks for {{ company.name }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="todos.length > 0" class="space-y-3">
                            <div v-for="todo in todos.slice(0, 5)" :key="todo.id" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="w-2 h-2 rounded-full" :class="{
                                    'bg-red-500': todo.priority === 'high',
                                    'bg-yellow-500': todo.priority === 'medium',
                                    'bg-green-500': todo.priority === 'low'
                                }"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ todo.title }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ todo.description || 'No description' }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ todo.status }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <Calendar class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p>No tasks found for this company.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
