<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const companyType = ref('');
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name" placeholder="Full name" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email" placeholder="email@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input id="password" type="password" required :tabindex="3" autocomplete="new-password" name="password" placeholder="Password" />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <!-- Company Selection -->
                <div class="grid gap-4">
                    <Label>Company</Label>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <input
                                id="create_company"
                                type="radio"
                                name="company_type"
                                value="create"
                                v-model="companyType"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label for="create_company" class="text-sm font-medium">I am creating the account for my company</Label>
                        </div>
                        <div v-if="companyType === 'create'" class="ml-6">
                            <Input
                                id="company_name"
                                type="text"
                                name="company_name"
                                placeholder="Enter company name"
                                :tabindex="5"
                            />
                            <InputError :message="errors.company_name" />
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                id="join_company"
                                type="radio"
                                name="company_type"
                                value="join"
                                v-model="companyType"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <Label for="join_company" class="text-sm font-medium">I belong to a company and have a code</Label>
                        </div>
                        <div v-if="companyType === 'join'" class="ml-6">
                            <Input
                                id="company_code"
                                type="text"
                                name="company_code"
                                placeholder="Enter company code"
                                :tabindex="5"
                                maxlength="8"
                                style="text-transform: uppercase;"
                            />
                            <InputError :message="errors.company_code" />
                        </div>
                    </div>
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="5" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="login()" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
