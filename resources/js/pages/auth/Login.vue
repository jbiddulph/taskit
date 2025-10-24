<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
// import { dashboard, register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Demo Account Info -->
        <div class="mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
            <div class="text-center">
                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-3">
                    Try ZapTask Demo
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between bg-white dark:bg-blue-900/30 px-3 py-2 rounded border">
                        <span class="text-blue-700 dark:text-blue-300">Email:</span>
                        <span class="font-mono text-blue-900 dark:text-blue-100 select-all">demo@zaptask.co.uk</span>
                    </div>
                    <div class="flex items-center justify-between bg-white dark:bg-blue-900/30 px-3 py-2 rounded border">
                        <span class="text-blue-700 dark:text-blue-300">Password:</span>
                        <span class="font-mono text-blue-900 dark:text-blue-100 select-all">zaptask123</span>
                    </div>
                </div>
            </div>
        </div>

        <Form
            action="/login"
            method="post"
            :reset-on-success="['password']"
            @success="$inertia.visit('/dashboard')"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink v-if="canResetPassword" :href="request()" class="text-sm" :tabindex="5"> Forgot password? </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink href="/register" :tabindex="5">Sign up</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
