<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import SeoHead from '@/components/SeoHead.vue';

interface Props {
    company: {
        id: number;
        name: string;
        code: string;
        description?: string;
        subscription_type: string;
        created_at: string;
        current_member_count: number;
        member_limit: number;
        current_project_count: number;
        project_limit: number;
    };
    members: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
    clients: Array<{
        id: number;
        name: string;
    }>;
    projects: Array<{
        id: number;
        name: string;
        key: string;
        color?: string | null;
        total_todos?: number;
        client?: {
            id: number;
            name: string;
        };
        owner: {
            id: number;
            name: string;
        };
        created_at: string;
    }>;
}

const props = defineProps<Props>();

// Inline editing state
const editingName = ref(false);
const editingNameText = ref('');
const nameInput = ref<HTMLInputElement>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const getSubscriptionTypeBadge = (type: string) => {
    const badges = {
        'FREE': 'bg-gray-100 text-gray-800',
        'MIDI': 'bg-blue-100 text-blue-800',
        'MAXI': 'bg-green-100 text-green-800',
    };
    return badges[type as keyof typeof badges] || badges.FREE;
};

const startEditName = async () => {
    editingName.value = true;
    editingNameText.value = props.company.name;
    await nextTick();
    nameInput.value?.focus();
    nameInput.value?.select();
};

const saveName = () => {
    if (editingNameText.value.trim() && editingNameText.value !== props.company.name) {
        router.put(`/companies/${props.company.id}`, {
            name: editingNameText.value.trim()
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }
    editingName.value = false;
};

const cancelEditName = () => {
    editingName.value = false;
    editingNameText.value = '';
};

const selectedClientFilter = ref<string>('all');
const selectedClientName = ref<string>('');

const updateProjectClient = async (projectId: number, event: Event) => {
    const target = event.target as HTMLSelectElement;
    const value = target.value;
    const clientId = value === '' ? null : Number(value);

    try {
        const tokenElement = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
        const csrfToken = tokenElement?.content ?? '';

        await fetch(`/api/projects/${projectId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify({ client_id: clientId }),
        });
    } catch (e) {
        console.error('Failed to update project client', e);
    }
};
</script>

<template>
    <AppSidebarLayout :company="company">
        <SeoHead
            :title="company.name"
            :description="`Company overview, members, and projects for ${company.name}.`"
            image="/zap_icon.png"
        />
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <!-- Company name + code + inline project filter -->
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <div v-if="!editingName" class="flex items-center gap-2">
                            <Heading :title="company.name">{{ company.name }}</Heading>
                            <button
                                @click="startEditName"
                                class="p-1 text-gray-400 hover:text-blue-500 transition-colors"
                                title="Edit company name"
                            >
                                <Icon name="Edit3" class="w-5 h-5" />
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <input
                                v-model="editingNameText"
                                @keydown.enter="saveName"
                                @keydown.escape="cancelEditName"
                                @blur="saveName"
                                class="text-3xl font-bold text-gray-900 dark:text-gray-100 bg-transparent border-b-2 border-blue-500 focus:outline-none focus:border-blue-600"
                                placeholder="Enter company name"
                                ref="nameInput"
                            />
                            <button
                                @click="saveName"
                                class="p-1 text-green-500 hover:text-green-600 transition-colors"
                                title="Save"
                            >
                                <Icon name="Check" class="w-5 h-5" />
                            </button>
                            <button
                                @click="cancelEditName"
                                class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                title="Cancel"
                            >
                                <Icon name="X" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                        <span>
                            Company Code: 
                            <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ company.code }}</span>
                        </span>
                        <div v-if="clients.length > 0" class="flex items-center gap-2">
                            <span>Project Filter:</span>
                            <select
                                class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                :value="selectedClientFilter"
                                @change="handleClientFilterChange"
                            >
                                <option value="all">All Projects</option>
                                <option
                                    v-for="client in clients"
                                    :key="client.id"
                                    :value="client.id"
                                >
                                    {{ client.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Active client filter label (desktop, right side) -->
                <div v-if="selectedClientName" class="text-sm text-gray-600 dark:text-gray-300">
                    Viewing projects for 
                    <span class="font-semibold">{{ selectedClientName }}</span>
                </div>
            </div>

            <!-- Company Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="Building" class="w-5 h-5" />
                            Company Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Company Name</label>
                            <p class="text-lg font-semibold">{{ company.name }}</p>
                        </div>
                        <div v-if="company.description">
                            <label class="text-sm font-medium text-gray-500">Description</label>
                            <p class="text-gray-700">{{ company.description }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Created</label>
                            <p>{{ formatDate(company.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Subscription Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="CreditCard" class="w-5 h-5" />
                            Subscription Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Plan</label>
                            <div class="flex items-center gap-2">
                                <span 
                                    :class="getSubscriptionTypeBadge(company.subscription_type)"
                                    class="px-2 py-1 rounded-full text-sm font-medium"
                                >
                                    {{ company.subscription_type }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Members</label>
                            <p class="text-lg">
                                {{ company.current_member_count }} / 
                                <span v-if="company.subscription_type === 'BUSINESS'">Unlimited</span>
                                <span v-else-if="company.subscription_type === 'MAXI'">20</span>
                                <span v-else-if="company.subscription_type === 'MIDI' || company.subscription_type === 'LTD_TEAM'">5</span>
                                <span v-else-if="company.subscription_type === 'LTD_AGENCY'">20</span>
                                <span v-else-if="company.subscription_type === 'LTD_BUSINESS'">50</span>
                                <span v-else-if="company.subscription_type === 'LTD_SOLO'">1</span>
                                <span v-else>{{ company.member_limit || 1 }}</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Projects</label>
                            <p class="text-lg">
                                {{ company.current_project_count }} / 
                                <span v-if="company.subscription_type === 'BUSINESS'">Unlimited</span>
                                <span v-else-if="company.subscription_type === 'MAXI'">100</span>
                                <span v-else-if="company.subscription_type === 'MIDI'">20</span>
                                <span v-else-if="company.subscription_type === 'LTD_TEAM'">20 per client</span>
                                <span v-else-if="company.subscription_type === 'LTD_AGENCY'">40 per client</span>
                                <span v-else-if="company.subscription_type === 'LTD_BUSINESS'">100 per client</span>
                                <span v-else-if="company.subscription_type === 'LTD_SOLO'">10</span>
                                <span v-else>{{ company.project_limit || 3 }}</span>
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Members List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="Users" class="w-5 h-5" />
                        Team Members ({{ members.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-hidden">
                        <div class="space-y-3">
                            <div 
                                v-for="member in members" 
                                :key="member.id"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ member.name }}</p>
                                    <p class="text-sm text-gray-500">{{ member.email }}</p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Joined {{ formatDate(member.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Projects List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon name="FolderOpen" class="w-5 h-5" />
                        Projects ({{ projects.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-hidden">
                        <!-- 4-column grid on desktop, stacks on smaller screens -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div 
                                v-for="project in projects" 
                                :key="project.id"
                                :class="[
                                    'p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors',
                                    project.color ? '' : 'border-l-blue-500'
                                ]"
                                :style="project.color ? {
                                    borderLeftColor: project.color,
                                    borderTopColor: project.color,
                                    borderRightColor: project.color,
                                    borderBottomColor: project.color,
                                    borderLeftWidth: '6px',
                                    borderTopWidth: '1px',
                                    borderRightWidth: '1px',
                                    borderBottomWidth: '1px'
                                } : {}"
                                @click="() => {
                                    // Set project in localStorage
                                    localStorage.setItem('currentProjectId', project.id.toString());
                                    
                                    // Dispatch custom event for TodoBoard to listen to
                                    window.dispatchEvent(new CustomEvent('projectSelected', {
                                        detail: { projectId: project.id }
                                    }));
                                    
                                    // Navigate to dashboard
                                    router.visit('/dashboard', {
                                        preserveState: false,
                                        preserveScroll: false,
                                        only: []
                                    });
                                }"
                            >
                                <div class="space-y-2">
                                    <p class="font-medium flex items-center gap-2">
                                        {{ project.name }}
                                        <span class="inline-flex items-center rounded-full bg-gray-200 dark:bg-gray-600 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-200">
                                            {{ project.total_todos ?? 0 }} {{ (project.total_todos ?? 0) === 1 ? 'Todo' : 'Todos' }}
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-500">{{ project.key }}</p>
                                    <p v-if="project.client" class="text-sm text-blue-600">{{ project.client.name }}</p>
                                    <!-- Optional Client selector (inline, non-blocking) -->
                                    <div v-if="clients.length > 0" class="mt-2" @click.stop>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                            Client (Optional)
                                        </label>
                                        <select
                                            class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                            :value="project.client ? project.client.id : ''"
                                            @change="(event) => updateProjectClient(project.id, event)"
                                        >
                                            <option value="">No Client</option>
                                            <option
                                                v-for="client in clients"
                                                :key="client.id"
                                                :value="client.id"
                                            >
                                                {{ client.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppSidebarLayout>
</template>
