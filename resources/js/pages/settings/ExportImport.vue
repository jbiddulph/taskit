<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { AlertCircle, Download, Upload, Database, FileJson, FileSpreadsheet, Package, MessageSquare, FolderOpen } from 'lucide-vue-next';
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
}

interface Props {
    user: User;
    company?: Company;
}

const props = defineProps<Props>();
const page = usePage();

// Export form
const exportForm = useForm({
    data_type: '',
    format: '',
});

// Import form
const importForm = useForm({
    file: null as File | null,
    import_type: '',
});

const fileInput = ref<HTMLInputElement>();
const downloadMessage = ref('');
const downloadError = ref('');

const exportData = async () => {
    if (!exportForm.data_type || !exportForm.format) {
        return;
    }

    exportForm.processing = true;
    downloadMessage.value = '';
    downloadError.value = '';

    try {
        // Use fetch to trigger proper file download
        const response = await fetch('/settings/export', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                data_type: exportForm.data_type,
                format: exportForm.format,
            }),
        });

        if (response.ok) {
            // Get the filename from the Content-Disposition header
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `export.${exportForm.format}`;
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }

            // Create blob and trigger download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);

            // Show success message
            downloadMessage.value = `✅ ${filename} downloaded successfully!`;
            
            // Reset form
            exportForm.reset();
        } else {
            const errorText = await response.text();
            throw new Error(`Export failed: ${response.status} ${response.statusText}`);
        }
    } catch (error) {
        console.error('Export error:', error);
        downloadError.value = `Failed to download export: ${error.message}`;
    } finally {
        exportForm.processing = false;
    }
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importForm.file = target.files[0];
    }
};

const importData = () => {
    importForm.post('/settings/import', {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    });
};

const errors = computed(() => page.props.errors as Record<string, string> || {});
const successMessage = computed(() => page.props.flash?.success as string || '');

const dataTypeOptions = [
    { value: 'all', label: 'All Data', icon: Database, description: 'Export everything: projects, todos, comments, and users' },
    { value: 'projects_todos', label: 'Projects + To-Dos', icon: FolderOpen, description: 'Export projects with their todos (perfect for restore)' },
    { value: 'projects', label: 'Projects Only', icon: FolderOpen, description: 'Export only project information' },
    { value: 'todos', label: 'To-Dos Only', icon: Package, description: 'Export only task information' },
    { value: 'comments', label: 'Comments Only', icon: MessageSquare, description: 'Export only comment information' },
];

const formatOptions = [
    { value: 'json', label: 'JSON Format', icon: FileJson, description: 'Structured data format, best for re-importing' },
    { value: 'csv', label: 'CSV Format', icon: FileSpreadsheet, description: 'Spreadsheet format, best for analysis' },
];

const importTypeOptions = [
    { value: 'projects_todos', label: 'Projects + To-Dos', icon: FolderOpen, description: 'Import projects with their todos (restore functionality)' },
    { value: 'projects', label: 'Projects Only', icon: FolderOpen, description: 'Import project data only' },
    { value: 'todos', label: 'To-Dos Only', icon: Package, description: 'Import task data only' },
    { value: 'comments', label: 'Comments Only', icon: MessageSquare, description: 'Import comment data only' },
];
</script>

<template>
    <Head title="Export & Import" />
    
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium">Export & Import</h3>
                    <p class="text-sm text-muted-foreground">
                        Export your company data for backup or analysis, and import data from external sources.
                    </p>
                </div>

                <Separator />

                <!-- Success Message -->
                <div v-if="successMessage" class="rounded-md bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Download Success Message -->
                <div v-if="downloadMessage" class="rounded-md bg-blue-50 p-4 border border-blue-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <Download class="h-5 w-5 text-blue-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">{{ downloadMessage }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button @click="downloadMessage = ''" class="text-blue-400 hover:text-blue-600">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Download Error Message -->
                <div v-if="downloadError" class="rounded-md bg-red-50 p-4 border border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <AlertCircle class="h-5 w-5 text-red-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ downloadError }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button @click="downloadError = ''" class="text-red-400 hover:text-red-600">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Export Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Download class="h-5 w-5 text-blue-600" />
                            Export Data
                        </CardTitle>
                        <CardDescription>
                            Download your company data in JSON or CSV format for backup or analysis.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <form @submit.prevent="exportData" class="space-y-4">
                            <!-- Data Type Selection -->
                            <div class="space-y-2">
                                <Label for="data_type">What data would you like to export?</Label>
                                <select
                                    id="data_type"
                                    name="data_type"
                                    v-model="exportForm.data_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select data type...</option>
                                    <option 
                                        v-for="option in dataTypeOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }} - {{ option.description }}
                                    </option>
                                </select>
                                <p v-if="errors.data_type" class="text-sm text-red-600">{{ errors.data_type }}</p>
                            </div>

                            <!-- Format Selection -->
                            <div class="space-y-2">
                                <Label for="format">Export format</Label>
                                <select
                                    id="format"
                                    name="format"
                                    v-model="exportForm.format"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select format...</option>
                                    <option 
                                        v-for="option in formatOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }} - {{ option.description }}
                                    </option>
                                </select>
                                <p v-if="errors.format" class="text-sm text-red-600">{{ errors.format }}</p>
                            </div>

                            <Button 
                                type="submit" 
                                :disabled="exportForm.processing || !exportForm.data_type || !exportForm.format"
                                class="w-full"
                            >
                                <Download class="mr-2 h-4 w-4" />
                                {{ exportForm.processing ? 'Preparing Export...' : 'Export Data' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Import Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Upload class="h-5 w-5 text-green-600" />
                            Import Data
                        </CardTitle>
                        <CardDescription>
                            Upload a JSON or CSV file to import data into your company workspace.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Warning -->
                        <div class="rounded-md bg-yellow-50 p-4 border border-yellow-200">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <AlertCircle class="h-5 w-5 text-yellow-400" />
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">Import Guidelines</h4>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>Ensure your file follows the correct format (JSON/CSV)</li>
                                            <li>Projects will be assigned to you as the owner</li>
                                            <li>Duplicate data may be created if records already exist</li>
                                            <li>Maximum file size: 10MB</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="importData" class="space-y-4">
                            <!-- Import Type Selection -->
                            <div class="space-y-2">
                                <Label for="import_type">What type of data are you importing?</Label>
                                <select
                                    id="import_type"
                                    name="import_type"
                                    v-model="importForm.import_type"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select import type...</option>
                                    <option 
                                        v-for="option in importTypeOptions" 
                                        :key="option.value" 
                                        :value="option.value"
                                    >
                                        {{ option.label }} - {{ option.description }}
                                    </option>
                                </select>
                                <p v-if="errors.import_type" class="text-sm text-red-600">{{ errors.import_type }}</p>
                            </div>

                            <!-- File Upload -->
                            <div class="space-y-2">
                                <Label for="file">Select file to import</Label>
                                <Input
                                    ref="fileInput"
                                    type="file"
                                    accept=".json,.csv,.txt"
                                    @change="handleFileSelect"
                                    class="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Supported formats: JSON, CSV (max 10MB)
                                </p>
                                <p v-if="errors.file" class="text-sm text-red-600">{{ errors.file }}</p>
                            </div>

                            <Button 
                                type="submit" 
                                :disabled="importForm.processing || !importForm.file || !importForm.import_type"
                                class="w-full"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                {{ importForm.processing ? 'Importing...' : 'Import Data' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Company Info -->
                <Card v-if="company">
                    <CardHeader>
                        <CardTitle class="text-sm">Export Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2 text-sm">
                            <div>
                                <dt class="font-medium text-muted-foreground">Company</dt>
                                <dd>{{ company.name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-muted-foreground">Company Code</dt>
                                <dd class="font-mono">{{ company.code }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-muted-foreground">Subscription</dt>
                                <dd>{{ company.subscription_type }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-muted-foreground">Exported by</dt>
                                <dd>{{ user.name }} ({{ user.email }})</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
