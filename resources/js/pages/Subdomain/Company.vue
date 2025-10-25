<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Globe, Users, Calendar, CheckCircle } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';

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

interface Props {
    company: Company;
    isSubdomain: boolean;
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
                        <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center overflow-hidden">
                            <img 
                                v-if="company.logo_url" 
                                :src="company.logo_url" 
                                :alt="`${company.name} logo`"
                                class="w-full h-full object-cover"
                            />
                            <Globe v-else class="w-8 h-8 text-white" />
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ company.name }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">
                        Company Portal
                    </p>
                </div>

                <!-- Company Info Card -->
                <Card class="max-w-2xl mx-auto mb-8">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Globe class="w-5 h-5" />
                            Company Information
                        </CardTitle>
                        <CardDescription>
                            Welcome to {{ company.name }}'s company portal
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-5 h-5 text-green-600" />
                                <span class="font-medium">Company Code:</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ company.code }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-5 h-5 text-green-600" />
                                <span class="font-medium">Subscription:</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ company.subscription_type }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CheckCircle class="w-5 h-5 text-green-600" />
                                <span class="font-medium">Subdomain:</span>
                                <span class="text-gray-600 dark:text-gray-300">{{ company.subdomain_url }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Cards -->
                <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                    <!-- Public Dashboard Card (only if company is public) -->
                    <Card v-if="company.is_public">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Globe class="w-5 h-5" />
                                Public Dashboard
                            </CardTitle>
                            <CardDescription>
                                View our projects and tasks without logging in
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button as-child class="w-full">
                                <a :href="`https://${company.subdomain}.zaptask.co.uk/public`">
                                    View Public Dashboard
                                </a>
                            </Button>
                            <Button as-child variant="outline" class="w-full">
                                <a href="https://zaptask.co.uk">
                                    Visit Main Site
                                </a>
                            </Button>
                        </CardContent>
                    </Card>

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

                <!-- Footer -->
                <div class="text-center mt-8 text-gray-500 dark:text-gray-400">
                    <p>Powered by <a href="https://zaptask.co.uk" class="text-blue-600 hover:underline">ZapTask</a></p>
                </div>
            </div>
        </div>
    </SubdomainLayout>
</template>
