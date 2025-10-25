<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Globe, Users, Calendar, CheckCircle } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';
import { onMounted } from 'vue';

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

// Redirect to public dashboard if company is public
onMounted(() => {
    if (props.company.is_public) {
        router.visit(`/public`, {
            method: 'get',
            preserveState: false,
            preserveScroll: false
        });
    }
});
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
                <div v-if="company.is_public">
                    <!-- Redirect to public dashboard -->
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
                        <p class="text-gray-600 dark:text-gray-300">Loading public dashboard...</p>
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
