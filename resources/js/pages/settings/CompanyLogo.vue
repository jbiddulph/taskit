<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, Info, Upload, Trash2, Image } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { uploadLogoToTaskitBucket } from '@/services/supabaseClient';

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
    logo_url?: string;
}

interface Props {
    user: User;
    company?: Company;
}

const props = defineProps<Props>();
const page = usePage();

// Check if user has access to logo upload (paid plans only)
const hasAccess = computed(() => {
    return props.company && ['MIDI', 'MAXI'].includes(props.company.subscription_type);
});

// Form for logo removal (still uses Laravel backend)
const removeForm = useForm({});

const fileInput = ref<HTMLInputElement>();
const uploading = ref(false);
const removing = ref(false);
const selectedFile = ref<File | null>(null);
const uploadError = ref<string>('');

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
        uploadError.value = '';
    }
};

const uploadLogo = async () => {
    if (!selectedFile.value || !props.company) return;
    
    uploading.value = true;
    uploadError.value = '';
    
    try {
        // Upload directly to Supabase (same method as todo images)
        const logoUrl = await uploadLogoToTaskitBucket(
            selectedFile.value, 
            props.company.name, 
            props.company.code
        );
        
        // Update the backend with the Supabase URL
        router.post('/settings/company-logo/update-url', {
            logo_url: logoUrl
        }, {
            onSuccess: () => {
                selectedFile.value = null;
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
                // Force page refresh to show new logo
                router.reload();
            },
            onError: (errors) => {
                uploadError.value = errors.logo || 'Failed to update logo URL';
            }
        });
        
    } catch (error) {
        console.error('Logo upload failed:', error);
        uploadError.value = 'Failed to upload logo to storage. Please try again.';
    } finally {
        uploading.value = false;
    }
};

const removeLogo = () => {
    removing.value = true;
    
    removeForm.delete('/settings/company-logo/remove', {
        onFinish: () => {
            removing.value = false;
        }
    });
};

// Get error messages
const errors = computed(() => page.props.errors as Record<string, string> || {});
const successMessage = computed(() => page.props.flash?.success as string || '');

// Current logo preview
const currentLogoUrl = computed(() => props.company?.logo_url);
const hasCustomLogo = computed(() => !!currentLogoUrl.value);
</script>

<template>
    <Head title="Company Logo" />
    
    <AppLayout>
        <SettingsLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Company Logo</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Customize your company's branding with a custom logo.
                </p>
            </div>

            <!-- Access Check -->
            <div v-if="!hasAccess" class="mb-6">
                <Card class="border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                            <AlertCircle class="w-5 h-5 flex-shrink-0" />
                            <div>
                                <p class="font-medium">Upgrade Required</p>
                                <p class="text-sm">Company logo customization is available for MIDI and MAXI plans only.</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
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
            <div v-if="errors.logo || uploadError" class="mb-6">
                <Card class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                            <AlertCircle class="w-5 h-5 flex-shrink-0" />
                            <p class="text-sm font-medium">{{ errors.logo || uploadError }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Current Logo Display -->
            <Card v-if="hasAccess">
                <CardHeader>
                    <CardTitle>Current Logo</CardTitle>
                    <CardDescription>
                        This is how your logo currently appears in the application.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <!-- Logo Preview -->
                            <div class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded border">
                                <img 
                                    v-if="hasCustomLogo" 
                                    :src="currentLogoUrl"
                                    :alt="company?.name + ' logo'"
                                    class="object-contain"
                                />
                                <div v-else class="flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400">
                                    <div class="w-6 h-6 bg-blue-600 dark:bg-blue-400 rounded text-white dark:text-gray-900 flex items-center justify-center">
                                        <img src="/zap_icon.png" alt="ZapTask" class="w-4 h-4" />
                                    </div>
                                    <span>ZapTask</span>
                                </div>
                            </div>
                            
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ hasCustomLogo ? 'Custom Logo' : 'Default ZapTask Logo' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ hasCustomLogo ? 'Your company\'s custom logo' : 'Standard ZapTask branding' }}
                                </p>
                            </div>
                        </div>
                        
                        <Button 
                            v-if="hasCustomLogo"
                            @click="removeLogo"
                            :disabled="removing"
                            variant="outline"
                            class="text-red-600 hover:text-red-700 border-red-200 hover:border-red-300"
                        >
                            <Trash2 class="w-4 h-4 mr-2" />
                            {{ removing ? 'Removing...' : 'Remove Logo' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Logo Upload -->
            <Card v-if="hasAccess">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Upload class="w-5 h-5" />
                        Upload New Logo
                    </CardTitle>
                    <CardDescription>
                        Upload a custom logo for your company. The logo will replace the ZapTask branding.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Logo Requirements Info -->
                    <div class="flex items-start gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <Info class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Logo Requirements:</p>
                            <ul class="space-y-1 text-xs">
                                <li>• Recommended size: 216px width × 48px height</li>
                                <li>• Supported formats: PNG, JPG, JPEG, SVG</li>
                                <li>• Maximum file size: 2MB</li>
                                <li>• Filename will be: {{ company?.name?.replace(/\s+/g, '_') }}_{{ company?.code }}_timestamp</li>
                            </ul>
                        </div>
                    </div>

                    <!-- File Input -->
                    <div>
                        <Label for="logo-upload" class="text-sm font-medium">
                            Select Logo File
                        </Label>
                        <Input
                            id="logo-upload"
                            ref="fileInput"
                            type="file"
                            accept="image/png,image/jpg,image/jpeg,image/svg+xml"
                            @change="handleFileSelect"
                            class="mt-1"
                        />
                    </div>

                    <!-- Upload Preview -->
                    <div v-if="selectedFile" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <Image class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ selectedFile.name }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ (selectedFile.size / 1024).toFixed(1) }} KB
                            </p>
                        </div>
                        <Button
                            @click="uploadLogo"
                            :disabled="uploading"
                            class="ml-auto"
                        >
                            {{ uploading ? 'Uploading...' : 'Upload Logo' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
        </SettingsLayout>
    </AppLayout>
</template>
