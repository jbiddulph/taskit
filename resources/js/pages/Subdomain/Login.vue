<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, Globe, Lock } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    logo_url?: string;
    subdomain?: string;
    subdomain_url?: string;
}

interface Props {
    company: Company;
    isSubdomain: boolean;
}

const props = defineProps<Props>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    console.log('Subdomain login form submitting...', form.data());
    form.post('/login', {
        onFinish: () => form.reset('password'),
        onSuccess: (page) => {
            console.log('Login successful, redirecting...', page);
        },
        onError: (errors) => {
            console.log('Login failed with errors:', errors);
        }
    });
};
</script>

<template>
    <Head :title="`Login - ${company.name}`" />
    
    <SubdomainLayout>
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 flex items-center justify-center">
            <div class="w-full max-w-md">
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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ company.name }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        Employee Login Portal
                    </p>
                </div>

                <!-- Login Form -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Lock class="w-5 h-5" />
                            Employee Login
                        </CardTitle>
                        <CardDescription>
                            Enter your credentials to access your company dashboard
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <!-- Email -->
                            <div>
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    required
                                    autofocus
                                />
                                <div v-if="form.errors.email" class="flex items-center gap-1 mt-1 text-sm text-red-600">
                                    <AlertCircle class="w-4 h-4" />
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Password -->
                            <div>
                                <Label for="password">Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1"
                                    :class="{ 'border-red-500': form.errors.password }"
                                    required
                                />
                                <div v-if="form.errors.password" class="flex items-center gap-1 mt-1 text-sm text-red-600">
                                    <AlertCircle class="w-4 h-4" />
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Signing in...' : 'Sign In' }}
                            </Button>
                        </form>

                        <!-- Footer -->
                        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            <p>Powered by <a href="https://zaptask.co.uk" class="text-blue-600 hover:underline">ZapTask</a></p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </SubdomainLayout>
</template>
