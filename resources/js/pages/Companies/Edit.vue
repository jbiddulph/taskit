<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Icon from '@/components/Icon.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    company: {
        id: number;
        name: string;
        code: string;
        description?: string;
        subscription_type: string;
    };
    errors?: Record<string, string>;
}

const props = defineProps<Props>();

const form = ref({
    name: props.company.name,
    description: props.company.description || '',
});

const processing = ref(false);

const updateCompany = () => {
    processing.value = true;
    
    router.put(`/companies/${props.company.id}`, form.value, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AppSidebarLayout :company="company">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading>Edit Company</Heading>
                <Link 
                    :href="`/companies/${company.id}`"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                >
                    <Icon name="ArrowLeft" class="w-4 h-4" />
                    Back to Company
                </Link>
            </div>

            <!-- Edit Form -->
            <div class="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="Edit" class="w-5 h-5" />
                            Company Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="updateCompany" class="space-y-6">
                            <!-- Company Name -->
                            <div>
                                <Label for="name">Company Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError v-if="errors?.name" :message="errors.name" class="mt-2" />
                            </div>

                            <!-- Company Code (Read-only) -->
                            <div>
                                <Label for="code">Company Code</Label>
                                <Input
                                    id="code"
                                    :value="company.code"
                                    type="text"
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                    readonly
                                />
                                <p class="mt-1 text-sm text-gray-500">Company code cannot be changed</p>
                            </div>

                            <!-- Description -->
                            <div>
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Enter a description for your company..."
                                ></textarea>
                                <InputError v-if="errors?.description" :message="errors.description" class="mt-2" />
                            </div>

                            <!-- Subscription Type (Read-only) -->
                            <div>
                                <Label for="subscription">Subscription Plan</Label>
                                <Input
                                    id="subscription"
                                    :value="company.subscription_type"
                                    type="text"
                                    class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                    readonly
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    To change your subscription plan, please visit the 
                                    <Link href="/subscription" class="text-black hover:text-gray-900 dark:text-white dark:hover:text-gray-100">subscription page</Link>
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center gap-4">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="inline-flex items-center gap-2 rounded-md px-6 py-2 text-sm font-medium border transition-colors cursor-pointer bg-black text-white hover:bg-gray-900 hover:border-gray-900 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Icon v-if="processing" name="Loader2" class="w-4 h-4 animate-spin" />
                                    <Icon v-else name="Save" class="w-4 h-4" />
                                    {{ processing ? 'Saving...' : 'Save Changes' }}
                                </button>

                                <Link 
                                    :href="`/companies/${company.id}`"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                                >
                                    Cancel
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppSidebarLayout>
</template>
