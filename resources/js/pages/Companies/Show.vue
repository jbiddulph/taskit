<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import Heading from '@/components/Heading.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import Icon from '@/components/Icon.vue';
import { Link } from '@inertiajs/vue3';

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
    projects: Array<{
        id: number;
        name: string;
        key: string;
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
</script>

<template>
    <AppSidebarLayout :company="company">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading>{{ company.name }}</Heading>
                <Link 
                    :href="`/companies/${company.id}/edit`"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    <Icon name="Edit" class="w-4 h-4" />
                    Edit Company
                </Link>
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
                        <div>
                            <label class="text-sm font-medium text-gray-500">Company Code</label>
                            <p class="text-lg font-mono">{{ company.code }}</p>
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
                                <span v-if="company.member_limit === 2147483647">Unlimited</span>
                                <span v-else>{{ company.member_limit }}</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Projects</label>
                            <p class="text-lg">
                                {{ company.current_project_count }} / 
                                <span v-if="company.project_limit === 2147483647">Unlimited</span>
                                <span v-else>{{ company.project_limit }}</span>
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
                        <div class="space-y-3">
                            <div 
                                v-for="project in projects" 
                                :key="project.id"
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ project.name }}</p>
                                    <p class="text-sm text-gray-500">{{ project.key }}</p>
                                    <p v-if="project.client" class="text-sm text-blue-600">{{ project.client.name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Owner: {{ project.owner.name }}</p>
                                    <p class="text-sm text-gray-500">{{ formatDate(project.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppSidebarLayout>
</template>
