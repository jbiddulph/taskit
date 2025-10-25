<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Globe, Users, Calendar, CheckCircle, Plus, Filter, Bell, Settings } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';
import TodoBoard from '@/components/TodoBoard.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    logo_url?: string;
    subdomain?: string;
    subdomain_url?: string;
    is_public?: boolean;
}

interface Todo {
    id: number;
    title: string;
    description?: string;
    status: string;
    priority: string;
    due_date?: string;
    project_id?: number;
    user_id: number;
    parent_task_id?: number;
    subtasks?: Todo[];
    created_at: string;
    updated_at: string;
}

interface Project {
    id: number;
    name: string;
    description?: string;
    color: string;
    is_active: boolean;
    viewing_order: number;
    todos_count: number;
    completed_todos_count: number;
}

interface Activity {
    id: number;
    type: string;
    description: string;
    actor_name: string;
    created_at: string;
}

interface Props {
    company: Company;
    isSubdomain: boolean;
    todos?: Todo[];
    projects?: Project[];
    activities?: Activity[];
    selectedProject?: Project;
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="`${company.name} - Company Portal`" />
    
    <SubdomainLayout>
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-4">
                        <div class="h-20 w-auto flex items-center justify-center overflow-hidden">
                            <img 
                                v-if="company.logo_url" 
                                :src="company.logo_url" 
                                :alt="`${company.name} logo`"
                                class="w-auto h-full object-contain"
                            />
                            <Globe v-else class="w-8 h-8 text-white" />
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ company.name }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">
                        {{ company.is_public ? `Welcome to ${company.name}` : 'Company Portal' }}
                    </p>
                </div>

                <!-- Public Dashboard for Public Companies -->
                <div v-if="company.is_public" class="min-h-screen bg-gray-50 dark:bg-gray-900">
                    <!-- Public Dashboard Header -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm border-b">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div class="flex justify-between items-center py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-20 w-auto flex items-center justify-center overflow-hidden">
                                        <img 
                                            v-if="company.logo_url" 
                                            :src="company.logo_url" 
                                            :alt="`${company.name} logo`"
                                            class="w-auto h-full object-contain"
                                        />
                                        <Globe v-else class="w-8 h-8 text-blue-600" />
                                    </div>
                                    <div>
                                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            {{ company.name }}
                                        </h1>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Public Dashboard
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Button as-child variant="outline" size="sm">
                                        <a :href="`https://${company.subdomain}.zaptask.co.uk/login`">
                                            Employee Login
                                        </a>
                                    </Button>
                                    <Button as-child variant="outline" size="sm">
                                        <a href="https://zaptask.co.uk">
                                            Visit Main Site
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Dashboard Content -->
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                            <!-- Todo Board (3 columns) -->
                            <div class="lg:col-span-3">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                                    <div class="p-6 border-b">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            Project Tasks
                                        </h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            View our current project tasks and progress
                                        </p>
                                    </div>
                                    <div class="p-6">
                                        <!-- Read-only Todo Board -->
                                        <TodoBoard 
                                            :todos="todos || []"
                                            :projects="projects || []"
                                            :selectedProject="selectedProject"
                                            :isReadOnly="true"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Feed (1 column) -->
                            <div class="lg:col-span-1">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                                    <div class="p-6 border-b">
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            Recent Activity
                                        </h2>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Latest updates from our team
                                        </p>
                                    </div>
                                    <div class="p-6">
                                        <!-- Activity Feed -->
                                        <ActivityFeed 
                                            :activities="activities || []"
                                            :isReadOnly="true"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <!-- Action Cards for Private Companies -->
                    <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        <!-- Login Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="w-5 h-5" />
                                    Employee Access
                                </CardTitle>
                                <CardDescription>
                                    Login to access your company dashboard
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button as-child class="w-full">
                                    <a :href="`https://${company.subdomain}.zaptask.co.uk/login`">
                                        Employee Login
                                    </a>
                                </Button>
                                <Button as-child variant="outline" class="w-full">
                                    <a href="https://zaptask.co.uk">
                                        Visit Main Site
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Features Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Calendar class="w-5 h-5" />
                                    Company Features
                                </CardTitle>
                                <CardDescription>
                                    Access your company's task management system
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Task Management
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Team Collaboration
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Project Tracking
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8 text-gray-500 dark:text-gray-400">
                    <p>Powered by <a href="https://zaptask.co.uk" class="text-blue-600 hover:underline">ZapTask</a></p>
                </div>
            </div>
        </div>
    </SubdomainLayout>
</template>
