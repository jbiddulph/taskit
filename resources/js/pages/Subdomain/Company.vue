<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Globe, Users, Calendar, CheckCircle, Plus, Filter, Bell, Settings, Lock, Eye, Activity, CheckSquare } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';
import TodoBoard from '@/components/TodoBoard.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';
import { realtimeService } from '@/services/realtimeService';
import { ref, onMounted, computed } from 'vue';
import Icon from '@/components/Icon.vue';

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

interface Todo {
    id: number;
    title: string;
    description?: string;
    status: string;
    priority: string;
    type?: string;
    due_date?: string;
    project_id?: number;
    user_id: number;
    parent_task_id?: number;
    subtasks?: Todo[];
    created_at: string;
    updated_at: string;
}

interface Project {
    id: number;
    name: string;
    description?: string;
    color: string;
    is_active: boolean;
    viewing_order: number;
    todos_count: number;
    completed_todos_count: number;
}

interface Activity {
    id: number;
    type: string;
    description: string;
    actor_name: string;
    created_at: string;
}

interface Props {
    company: Company;
    isSubdomain: boolean;
    todos?: Todo[];
    projects?: Project[];
    activities?: Activity[];
    selectedProject?: Project;
}

const props = defineProps<Props>();

// Company code verification state
const showCodeModal = ref(false);
const enteredCode = ref('');
const codeError = ref('');
const isVerifying = ref(false);
const hasValidCode = ref(false);

// Dashboard action states
const showCalendar = ref(false);
const showActivityFeed = ref(false);
const isSelectMode = ref(false);

// Todo details modal state
const showTodoModal = ref(false);
const selectedTodo = ref<Todo | null>(null);

// Check if company code is stored in localStorage
const checkStoredCode = () => {
    if (typeof window !== 'undefined') {
        const storedCode = localStorage.getItem('company_key');
        if (storedCode === props.company.code) {
            hasValidCode.value = true;
            return true;
        }
    }
    return false;
};

// Verify company code
const verifyCode = async () => {
    if (!enteredCode.value.trim()) {
        codeError.value = 'Please enter a company code';
        return;
    }

    isVerifying.value = true;
    codeError.value = '';

    // Simulate verification delay
    await new Promise(resolve => setTimeout(resolve, 500));

    if (enteredCode.value.trim().toLowerCase() === props.company.code.toLowerCase()) {
        // Store the correct code in localStorage
        if (typeof window !== 'undefined') {
            localStorage.setItem('company_key', props.company.code);
        }
        hasValidCode.value = true;
        showCodeModal.value = false;
        enteredCode.value = '';
    } else {
        codeError.value = 'Invalid company code. Please try again.';
    }

    isVerifying.value = false;
};

// Clear stored code (for testing purposes)
const clearStoredCode = () => {
    if (typeof window !== 'undefined') {
        localStorage.removeItem('company_key');
        hasValidCode.value = false;
        showCodeModal.value = true;
    }
};

// Dashboard action toggle functions
const toggleCalendar = () => {
    showCalendar.value = !showCalendar.value;
};

const toggleActivityFeed = () => {
    showActivityFeed.value = !showActivityFeed.value;
};

const toggleSelectMode = () => {
    isSelectMode.value = !isSelectMode.value;
};

// Todo modal functions
const openTodoModal = (todo: Todo) => {
    selectedTodo.value = todo;
    showTodoModal.value = true;
};

const closeTodoModal = () => {
    showTodoModal.value = false;
    selectedTodo.value = null;
};

// Format date helper function
const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Computed property to determine if content should be blurred
const shouldBlurContent = computed(() => {
    return props.company.is_public && !hasValidCode.value;
});

// Initialize on mount
onMounted(() => {
    if (props.company.is_public) {
        if (!checkStoredCode()) {
            showCodeModal.value = true;
        }
        
        // Initialize real-time service for public dashboard
        // Use company ID as both user ID and company ID for public access
        console.log('🔥 Initializing real-time service for public dashboard with company ID:', props.company.id);
        realtimeService.init(props.company.id, props.company.id);
        
        // Add test functions to window for debugging
        (window as any).testRealtimeEvents = () => {
            realtimeService.testRealtimeEvents();
        };
        (window as any).testDatabaseRealtime = () => {
            realtimeService.testDatabaseRealtime();
        };
        (window as any).testSupabaseConnection = () => {
            realtimeService.testSupabaseConnection();
        };
        (window as any).testDeleteEvents = () => {
            realtimeService.testDeleteEvents();
        };
    }
});
</script>

<template>
    <Head :title="`${company.name} - Company Portal`" />
    
    <SubdomainLayout>
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
            <div class="container mx-auto px-4 py-8">

                <!-- Public Dashboard for Public Companies -->
                <div v-if="company.is_public" class="min-h-screen bg-gray-50 dark:bg-gray-900 relative">
                    <!-- Public Dashboard Header -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm border-b">
                        <div class="px-4 sm:px-6 lg:px-8">
                            <div class="flex justify-between items-center py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-20 w-auto flex items-center justify-center overflow-hidden">
                                        <img 
                                            v-if="company.logo_url" 
                                            :src="company.logo_url" 
                                            :alt="`${company.name} logo`"
                                            class="w-auto h-full object-contain"
                                        />
                                        <Globe v-else class="w-8 h-8 text-blue-600" />
                                    </div>
                                    <div>
                                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                            {{ company.name }}
                                        </h1>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Public Dashboard
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- Dashboard Action Buttons -->
                                    <button
                                        @click="toggleCalendar"
                                        :title="showCalendar ? 'Hide Calendar' : 'Show Calendar'"
                                        :class="[
                                            'inline-flex items-center justify-center p-2 transition-colors',
                                            showCalendar 
                                                ? 'text-blue-600 dark:text-blue-400' 
                                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                                        ]"
                                    >
                                        <Calendar class="w-5 h-5" />
                                    </button>

                                    <button
                                        @click="toggleActivityFeed"
                                        :title="showActivityFeed ? 'Hide Activity Feed' : 'Show Activity Feed'"
                                        :class="[
                                            'inline-flex items-center justify-center p-2 transition-colors',
                                            showActivityFeed 
                                                ? 'text-blue-600 dark:text-blue-400' 
                                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                                        ]"
                                    >
                                        <Activity class="w-5 h-5" />
                                    </button>

                                    <button
                                        @click="toggleSelectMode"
                                        :title="isSelectMode ? 'Exit Select Mode' : 'Select for Bulk Update'"
                                        :class="[
                                            'inline-flex items-center justify-center p-2 transition-colors',
                                            isSelectMode 
                                                ? 'text-blue-600 dark:text-blue-400' 
                                                : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100'
                                        ]"
                                    >
                                        <CheckSquare class="w-5 h-5" />
                                    </button>

                                    <Button as-child variant="outline" size="sm">
                                        <a :href="`https://${company.subdomain}.zaptask.co.uk/login`">
                                            Employee Login
                                        </a>
                                    </Button>
                                    <Button as-child variant="outline" size="sm">
                                        <a href="https://zaptask.co.uk">
                                            Visit Main Site
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Dashboard Content -->
                    <div class="px-4 sm:px-6 lg:px-8 py-8">
                        <!-- Full Width Todo Board -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                            <div class="p-6 border-b">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Project Tasks
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    View our current project tasks and progress
                                </p>
                            </div>
                            <div class="p-6">
                                <!-- Read-only Todo Board -->
                                <TodoBoard 
                                    :todos="todos || []"
                                    :projects="projects || []"
                                    :selectedProject="selectedProject"
                                    :isReadOnly="true"
                                    :showCalendar="showCalendar"
                                    :showActivityFeed="showActivityFeed"
                                    :isSelectMode="isSelectMode"
                                    @todo-click="openTodoModal"
                                    @toggle-calendar="toggleCalendar"
                                    @toggle-activity-feed="toggleActivityFeed"
                                    @toggle-select-mode="toggleSelectMode"
                                />
                            </div>
                        </div>

                        <!-- Activity Feed Below -->
                        <div v-if="showActivityFeed" class="mt-8">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                                <div class="p-6 border-b">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        Recent Activity
                                    </h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Latest updates from our team
                                    </p>
                                </div>
                                <div class="p-6">
                                    <!-- Activity Feed -->
                                    <ActivityFeed 
                                        :activities="activities || []"
                                        :isReadOnly="true"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Blur Overlay -->
                    <div 
                        v-if="shouldBlurContent"
                        class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center"
                    >
                        <!-- Company Code Verification Modal -->
                        <Dialog :open="showCodeModal" @update:open="showCodeModal = $event">
                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle class="flex items-center gap-2">
                                        <Lock class="w-5 h-5 text-blue-600" />
                                        Company Access Required
                                    </DialogTitle>
                                    <DialogDescription>
                                        Please enter the company code to view {{ company.name }}'s public dashboard.
                                    </DialogDescription>
                                </DialogHeader>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="company-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Company Code
                                        </label>
                                        <Input
                                            id="company-code"
                                            v-model="enteredCode"
                                            type="text"
                                            placeholder="Enter company code"
                                            class="w-full"
                                            :disabled="isVerifying"
                                            @keydown.enter="verifyCode"
                                        />
                                        <p v-if="codeError" class="text-sm text-red-600 dark:text-red-400 mt-1">
                                            {{ codeError }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex gap-3">
                                        <Button 
                                            @click="verifyCode" 
                                            :disabled="isVerifying || !enteredCode.trim()"
                                            class="flex-1"
                                        >
                                            <Lock class="w-4 h-4 mr-2" />
                                            {{ isVerifying ? 'Verifying...' : 'Access Dashboard' }}
                                        </Button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Contact {{ company.name }} for the company code
                                        </p>
                                        <!-- Debug button for testing -->
                                        <button 
                                            @click="clearStoredCode"
                                            class="text-xs text-blue-600 hover:underline mt-2"
                                        >
                                            Clear stored code (for testing)
                                        </button>
                                    </div>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div v-else>
                    <!-- Action Cards for Private Companies -->
                    <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                        <!-- Login Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="w-5 h-5" />
                                    Employee Access
                                </CardTitle>
                                <CardDescription>
                                    Login to access your company dashboard
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Button as-child class="w-full">
                                    <a :href="`https://${company.subdomain}.zaptask.co.uk/login`">
                                        Employee Login
                                    </a>
                                </Button>
                                <Button as-child variant="outline" class="w-full">
                                    <a href="https://zaptask.co.uk">
                                        Visit Main Site
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Features Card -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Calendar class="w-5 h-5" />
                                    Company Features
                                </CardTitle>
                                <CardDescription>
                                    Access your company's task management system
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Task Management
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Team Collaboration
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-600" />
                                        Project Tracking
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8 text-gray-500 dark:text-gray-400">
                    <p>Powered by <a href="https://zaptask.co.uk" class="text-blue-600 hover:underline">ZapTask</a></p>
                </div>
            </div>
        </div>

        <!-- Todo Details Modal -->
        <Dialog :open="showTodoModal" @update:open="showTodoModal = $event">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Icon name="FileText" class="w-5 h-5 text-blue-600" />
                        Task Details
                    </DialogTitle>
                    <DialogDescription>
                        View detailed information about this task
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedTodo" class="space-y-6">
                    <!-- Task Title -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ selectedTodo.title }}
                        </h3>
                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <Icon name="Calendar" class="w-4 h-4" />
                                {{ selectedTodo.due_date ? formatDate(selectedTodo.due_date) : 'No due date' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <Icon name="Flag" class="w-4 h-4" />
                                {{ selectedTodo.priority }}
                            </span>
                            <span class="flex items-center gap-1">
                                <Icon name="Circle" class="w-4 h-4" />
                                {{ selectedTodo.type || 'Task' }}
                            </span>
                        </div>
                    </div>

                    <!-- Task Description -->
                    <div v-if="selectedTodo.description">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Description</h4>
                        <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300" v-html="selectedTodo.description"></div>
                    </div>

                    <!-- Task Metadata -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Created:</span>
                            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ formatDate(selectedTodo.created_at) }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Last Updated:</span>
                            <span class="text-gray-600 dark:text-gray-400 ml-2">{{ formatDate(selectedTodo.updated_at) }}</span>
                        </div>
                    </div>

                    <!-- Subtasks -->
                    <div v-if="selectedTodo.subtasks && selectedTodo.subtasks.length > 0">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Subtasks</h4>
                        <div class="space-y-2">
                            <div 
                                v-for="subtask in selectedTodo.subtasks" 
                                :key="subtask.id"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <Icon name="CheckCircle" class="w-4 h-4 text-green-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ subtask.title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </SubdomainLayout>
</template>
