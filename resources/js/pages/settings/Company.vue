<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, Info, Upload, Trash2, Image, Globe, ExternalLink, CheckCircle } from 'lucide-vue-next';
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
    subdomain?: string;
    subdomain_url?: string;
    is_public?: boolean;
}

interface Props {
    user: User;
    company?: Company;
}

const props = defineProps<Props>();
const page = usePage();

// Check if user has access to company features (paid plans only)
const hasAccess = computed(() => {
    return props.company && ['MIDI', 'MAXI'].includes(props.company.subscription_type);
});

// Form for logo removal
const removeForm = useForm({});

// Form for subdomain creation
const subdomainForm = useForm({
    company_name: props.company?.name || ''
});

// Form for public dashboard toggle
const publicForm = useForm({
    is_public: props.company?.is_public || false
});

// Subdomain validation state
const subdomainValidation = ref({
    checking: false,
    available: null,
    message: '',
    subdomain: '',
    url: ''
});

// Debounced subdomain checking
let subdomainCheckTimeout = null;

const fileInput = ref<HTMLInputElement>();
const uploading = ref(false);
const removing = ref(false);
const creatingSubdomain = ref(false);
const selectedFile = ref<File | null>(null);
const uploadError = ref<string>('');
const subdomainError = ref<string>('');
const checkingPermissions = ref(false);
const apiPermissions = ref(null);

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

const createSubdomain = () => {
    
    if (!props.company) {
        return;
    }
    
    // Use the validated subdomain if available, otherwise use the company name
    const subdomainToUse = subdomainValidation.value.subdomain || subdomainForm.company_name;
    
    creatingSubdomain.value = true;
    subdomainError.value = '';
    
    // Update the form data and submit using Inertia.js
    subdomainForm.company_name = subdomainToUse;
    
    subdomainForm.post('/settings/company/subdomain', {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            subdomainError.value = errors.subdomain || 'Failed to create subdomain';
        },
        onFinish: () => {
            creatingSubdomain.value = false;
        }
    });
};

const checkApiPermissions = async () => {
    checkingPermissions.value = true;
    apiPermissions.value = null;
    
    try {
        const response = await fetch('/settings/company/api-permissions');
        const data = await response.json();
        apiPermissions.value = data;
    } catch (error) {
        console.error('Error checking API permissions:', error);
        apiPermissions.value = { error: 'Failed to check API permissions' };
    } finally {
        checkingPermissions.value = false;
    }
};

// Check subdomain availability
const checkSubdomainAvailability = async (subdomain: string) => {
    if (subdomain.length < 3) {
        subdomainValidation.value = {
            checking: false,
            available: null,
            message: '',
            subdomain: '',
            url: ''
        };
        return;
    }

    subdomainValidation.value.checking = true;
    
    try {
        const response = await fetch(`/settings/company/check-subdomain?subdomain=${encodeURIComponent(subdomain)}`);
        const data = await response.json();
        
        subdomainValidation.value = {
            checking: false,
            available: data.available,
            message: data.message,
            subdomain: data.subdomain || subdomain,
            url: data.url || ''
        };
    } catch (error) {
        console.error('Failed to check subdomain availability:', error);
        subdomainValidation.value = {
            checking: false,
            available: false,
            message: 'Failed to check subdomain availability',
            subdomain: subdomain,
            url: ''
        };
    }
};

// Debounced subdomain checking
const onSubdomainInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;
    
    // Clear previous timeout
    if (subdomainCheckTimeout) {
        clearTimeout(subdomainCheckTimeout);
    }
    
    // Set new timeout
    subdomainCheckTimeout = setTimeout(() => {
        checkSubdomainAvailability(value);
    }, 500); // 500ms delay
};

const togglePublicDashboard = () => {
    publicForm.patch('/settings/company/public', {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            console.error('Failed to update public dashboard setting:', errors);
        }
    });
};

// Get error messages
const errors = computed(() => page.props.errors as Record<string, string> || {});
const successMessage = computed(() => page.props.flash?.success as string || '');

// Current logo preview
const currentLogoUrl = computed(() => props.company?.logo_url);
const hasCustomLogo = computed(() => !!currentLogoUrl.value);

// Subdomain status
const hasSubdomain = computed(() => !!props.company?.subdomain);
const subdomainUrl = computed(() => props.company?.subdomain_url);
</script>

<template>
    <Head title="Company Settings" />
    
    <AppLayout>
        <SettingsLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Company Settings</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage your company's branding and subdomain.
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
                                <p class="text-sm">Company features are available for MIDI and MAXI plans only.</p>
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
            <div v-if="errors.logo || uploadError || subdomainError" class="mb-6">
                <Card class="border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                            <AlertCircle class="w-5 h-5 flex-shrink-0" />
                            <p class="text-sm font-medium">{{ errors.logo || uploadError || subdomainError }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Manual Setup Instructions -->
            <div v-if="errors.instructions" class="mb-6">
                <Card class="border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20">
                    <CardHeader>
                        <CardTitle class="text-blue-700 dark:text-blue-300">
                            {{ errors.instructions.title }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium mb-2">{{ errors.instructions.note }}</p>
                                <ol class="list-decimal list-inside space-y-1">
                                    <li v-for="step in errors.instructions.steps" :key="step">
                                        {{ step }}
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Subdomain Section -->
            <Card v-if="hasAccess">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="w-5 h-5" />
                        Company Subdomain
                    </CardTitle>
                    <CardDescription>
                        Create a custom subdomain for your company using Cloudflare DNS and Heroku. This will allow guests to access your company's dashboard directly.
                    </CardDescription>
                    <div class="mt-4">
                        <Button 
                            @click="checkApiPermissions" 
                            :disabled="checkingPermissions"
                            variant="outline"
                            size="sm"
                        >
                            {{ checkingPermissions ? 'Checking...' : 'Check API Permissions' }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- API Permissions Check Results -->
                    <div v-if="apiPermissions" class="p-4 border rounded-lg" :class="{
                        'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': !apiPermissions.error,
                        'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800': apiPermissions.error
                    }">
                        <div class="flex items-center gap-2 mb-2" :class="{
                            'text-green-700 dark:text-green-300': !apiPermissions.error,
                            'text-red-700 dark:text-red-300': apiPermissions.error
                        }">
                            <Info class="w-5 h-5" />
                            <span class="font-medium">API Permissions Status</span>
                        </div>
                        <div v-if="apiPermissions.error" class="text-sm text-red-600 dark:text-red-400">
                            {{ apiPermissions.error }}
                        </div>
                        <div v-else class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">Cloudflare API:</span>
                                <span :class="apiPermissions.cloudflare_api?.accessible ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ apiPermissions.cloudflare_api?.accessible ? '✓ Accessible' : '✗ Not Accessible' }}
                                </span>
                                <span v-if="!apiPermissions.cloudflare_api?.accessible" class="text-xs text-gray-500">
                                    ({{ apiPermissions.cloudflare_api?.message }})
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">Heroku API:</span>
                                <span :class="apiPermissions.heroku_api?.accessible ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ apiPermissions.heroku_api?.accessible ? '✓ Accessible' : '✗ Not Accessible' }}
                                </span>
                                <span v-if="!apiPermissions.heroku_api?.accessible" class="text-xs text-gray-500">
                                    ({{ apiPermissions.heroku_api?.message }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Current Subdomain Status -->
                    <div v-if="hasSubdomain" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-300 mb-2">
                            <CheckCircle class="w-5 h-5" />
                            <span class="font-medium">Subdomain Active</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-green-600 dark:text-green-400">
                                Your company subdomain: 
                            </span>
                            <a 
                                :href="subdomainUrl" 
                                target="_blank"
                                class="inline-flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                            >
                                {{ subdomainUrl }}
                                <ExternalLink class="w-3 h-3" />
                            </a>
                        </div>
                    </div>

                    <!-- Subdomain Already Linked -->
                    <div v-if="hasSubdomain" class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300 mb-2">
                            <Globe class="w-5 h-5" />
                            <span class="font-medium">Subdomain Active & Locked</span>
                        </div>
                        <p class="text-sm text-amber-600 dark:text-amber-400 mb-3">
                            Your company subdomain is active and cannot be changed once created.
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-amber-600 dark:text-amber-400">
                                Current subdomain: 
                            </span>
                            <a 
                                :href="subdomainUrl" 
                                target="_blank"
                                class="inline-flex items-center gap-1 text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300"
                            >
                                {{ subdomainUrl }}
                                <ExternalLink class="w-3 h-3" />
                            </a>
                        </div>
                        <div class="mt-3 p-3 bg-amber-100 dark:bg-amber-900/30 rounded border border-amber-200 dark:border-amber-700">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div class="text-xs text-amber-700 dark:text-amber-300">
                                    <strong>Important:</strong> Subdomains cannot be modified or deleted once created. This ensures consistent branding and prevents broken links.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Create Subdomain -->
                    <div v-else>
                        <div class="space-y-4">
                            <div>
                                <Label for="company-name" class="text-sm font-medium">
                                    Company Name
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="company-name"
                                        v-model="subdomainForm.company_name"
                                        @input="onSubdomainInput"
                                        type="text"
                                        placeholder="Enter your company name"
                                        class="mt-1 pr-10"
                                        :class="{
                                            'border-green-500 focus:border-green-500': subdomainValidation.available === true,
                                            'border-red-500 focus:border-red-500': subdomainValidation.available === false
                                        }"
                                    />
                                    <!-- Validation icon -->
                                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <div v-if="subdomainValidation.checking" class="animate-spin">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <div v-else-if="subdomainValidation.available === true" class="text-green-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>
                                        <div v-else-if="subdomainValidation.available === false" class="text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Validation message -->
                                <div v-if="subdomainValidation.message" class="mt-2">
                                    <p :class="{
                                        'text-green-600 dark:text-green-400': subdomainValidation.available === true,
                                        'text-red-600 dark:text-red-400': subdomainValidation.available === false
                                    }" class="text-xs">
                                        {{ subdomainValidation.message }}
                                    </p>
                                    <p v-if="subdomainValidation.url" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Your subdomain will be: <span class="font-mono">{{ subdomainValidation.url }}</span>
                                    </p>
                                </div>
                                
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    This will be converted to a subdomain (e.g., "My Company" → "my-company.zaptask.co.uk")
                                </p>
                            </div>

                            <Button
                                @click="createSubdomain"
                                :disabled="creatingSubdomain || !subdomainForm.company_name || subdomainValidation.available === false || subdomainValidation.checking"
                                class="w-full"
                            >
                                <Globe class="w-4 h-4 mr-2" />
                                {{ creatingSubdomain ? 'Creating Subdomain...' : 'Create Company Subdomain' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

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
                                        <!-- Light mode: PNG -->
                                        <img src="/zap_icon.png" alt="ZapTask" class="w-4 h-4 dark:hidden" />
                                        
                                        <!-- Dark mode: White SVG -->
                                        <img src="/zap_icon_white.svg" alt="ZapTask" class="w-4 h-4 hidden dark:block" />
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

            <!-- Public Dashboard Section -->
            <Card v-if="hasAccess">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="w-5 h-5" />
                        Public Dashboard
                    </CardTitle>
                    <CardDescription>
                        Allow guests to view your company's dashboard without logging in. They can see projects and todos but cannot create, edit, or delete anything.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <Label class="text-sm font-medium">Enable Public Dashboard</Label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                When enabled, anyone can view your dashboard at your subdomain without logging in.
                            </p>
                        </div>
                        <Button
                            @click="togglePublicDashboard"
                            :disabled="publicForm.processing"
                            :variant="publicForm.is_public ? 'default' : 'outline'"
                            size="sm"
                        >
                            {{ publicForm.processing ? 'Updating...' : (publicForm.is_public ? 'Enabled' : 'Enable') }}
                        </Button>
                    </div>
                    
                    <div v-if="publicForm.is_public && company?.subdomain_url" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700 dark:text-green-300 mb-2">
                            <CheckCircle class="w-5 h-5" />
                            <span class="font-medium">Public Dashboard Active</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-green-600 dark:text-green-400">
                                    Company page: 
                                </span>
                                <a 
                                    :href="company.subdomain_url" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                                >
                                    {{ company.subdomain_url }}
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-green-600 dark:text-green-400">
                                    Public dashboard: 
                                </span>
                                <a 
                                    :href="`${company.subdomain_url}/public`" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                                >
                                    {{ company.subdomain_url }}/public
                                    <ExternalLink class="w-3 h-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        </SettingsLayout>
    </AppLayout>
</template>
