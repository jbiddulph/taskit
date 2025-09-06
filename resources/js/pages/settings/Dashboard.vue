<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { update as dashboardSettingsUpdate } from '@/routes/dashboard/settings';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { AlertCircle, Info } from 'lucide-vue-next';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Company {
    id: number;
    name: string;
    code: string;
    subscription_type: string;
    prune_completed_tasks: boolean;
}

interface Props {
    user: User;
    company?: Company;
}

const props = defineProps<Props>();
const page = usePage();

// Use a local reactive ref for the checkbox
const pruneCompletedTasks = ref(false);

const form = useForm({
    prune_completed_tasks: false,
});

// Watch for changes in props.company and update both form and local ref
watch(() => props.company?.prune_completed_tasks, (newValue) => {
    const boolValue = newValue || false;
    
    // Update both the form and the local ref
    form.prune_completed_tasks = boolValue;
    pruneCompletedTasks.value = boolValue;
}, { immediate: true }); // immediate: true ensures this runs on component mount

// Watch the local ref and sync changes back to the form
watch(pruneCompletedTasks, (newValue) => {
    form.prune_completed_tasks = newValue;
});

const updateSettings = () => {
    form.patch(dashboardSettingsUpdate().url, {
        preserveScroll: true,
        onSuccess: (page) => {
            // The response data will trigger the watch which will update the form
            // No need to manually update here since watch handles it
        }
    });
};

// Get error and success messages
const errors = computed(() => page.props.errors as Record<string, string> || {});
const successMessage = computed(() => page.props.flash?.success as string || '');
</script>

<template>
    <Head title="Dashboard Settings" />
    
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard Settings</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Configure your dashboard preferences and automation settings.
                    </p>
                </div>

                <!-- Success Message -->
                <div v-if="successMessage" class="mb-6">
                    <Card class="border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                <Info class="w-5 h-5 flex-shrink-0" />
                                <p class="text-sm font-medium">{{ successMessage }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Error Messages -->
                <div v-if="errors.error" class="mb-6">
                    <Card class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                        <CardContent class="p-4">
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                <AlertCircle class="w-5 h-5 flex-shrink-0" />
                                <p class="text-sm font-medium">{{ errors.error }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Task Management Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle>Task Management</CardTitle>
                        <CardDescription>
                            Configure automatic task management and cleanup settings.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Prune Completed Tasks Setting -->
                        <div class="flex items-start space-x-3">
                            <Checkbox 
                                id="prune_completed_tasks"
                                v-model:checked="pruneCompletedTasks"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label 
                                    for="prune_completed_tasks"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Prune all Completed tasks in the Done column (daily at midnight)
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Automatically delete all completed tasks and their comments daily at midnight. 
                                    This helps keep your workspace clean and organized.
                                </p>
                            </div>
                        </div>

                        <!-- Warning Info -->
                        <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <AlertCircle class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                            <div class="text-sm text-amber-700 dark:text-amber-300">
                                <p class="font-medium mb-1">Important:</p>
                                <p class="text-xs">
                                    Once tasks are pruned, they cannot be recovered. Make sure you have exported any important data before enabling this feature.
                                </p>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex items-center pt-4">
                            <Button 
                                @click="updateSettings"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Saving...' : 'Save Settings' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
