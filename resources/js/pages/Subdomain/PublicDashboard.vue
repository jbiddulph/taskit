<template>
    <Head :title="`${company.name} - Public Dashboard`" />
    
    <SubdomainLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 shadow-sm border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <Globe class="w-6 h-6 text-white" />
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
                            <Button as-child variant="outline" size="sm">
                                <a href="https://zaptask.co.uk">
                                    Visit Main Site
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Company Info -->
                <div class="mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <Globe class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ company.name }}
                                </h2>
                                <p class="text-gray-600 dark:text-gray-400">
                                    View our projects and tasks
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects and Todos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Projects -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Projects
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="p-6">
                            <div v-if="projects.length === 0" class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-4">
                                    <FolderOpen class="w-6 h-6 text-gray-400" />
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">No projects available</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="project in projects" 
                                    :key="project.id"
                                    class="p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-4 h-4 rounded-full"
                                            :style="{ backgroundColor: project.color }"
                                        ></div>
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                {{ project.name }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ project.todos.length }} task{{ project.todos.length !== 1 ? 's' : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Todos -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Recent Tasks
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ todos.length }} task{{ todos.length !== 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="p-6">
                            <div v-if="todos.length === 0" class="text-center py-8">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mx-auto mb-4">
                                    <CheckSquare class="w-6 h-6 text-gray-400" />
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">No tasks available</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="todo in todos.slice(0, 10)" 
                                    :key="todo.id"
                                    class="p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                    @click="selectedTodo = todo"
                                >
                                    <div class="flex items-start gap-3">
                                        <div 
                                            class="w-3 h-3 rounded-full mt-2 flex-shrink-0"
                                            :style="{ backgroundColor: todo.project?.color || '#6b7280' }"
                                        ></div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                                {{ todo.title }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ todo.project?.name || 'No Project' }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                {{ formatDate(todo.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Todo Detail Modal -->
        <Dialog v-model:open="showTodoModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <div 
                            class="w-3 h-3 rounded-full"
                            :style="{ backgroundColor: selectedTodo?.project?.color || '#6b7280' }"
                        ></div>
                        {{ selectedTodo?.title }}
                    </DialogTitle>
                </DialogHeader>
                <div class="space-y-4">
                    <div v-if="selectedTodo?.description" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Description</h4>
                        <p class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
                            {{ selectedTodo.description }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Project:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-300">
                                {{ selectedTodo?.project?.name || 'No Project' }}
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Status:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-300">
                                {{ selectedTodo?.status || 'Not Set' }}
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Created:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-300">
                                {{ formatDate(selectedTodo?.created_at) }}
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">Updated:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-300">
                                {{ formatDate(selectedTodo?.updated_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </SubdomainLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Globe, FolderOpen, CheckSquare } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';

interface Project {
    id: number;
    name: string;
    color: string;
    todos: Todo[];
}

interface Todo {
    id: number;
    title: string;
    description?: string;
    status?: string;
    created_at: string;
    updated_at: string;
    project?: Project;
    user?: {
        name: string;
    };
}

interface Company {
    id: number;
    name: string;
    code: string;
}

interface Props {
    company: Company;
    projects: Project[];
    todos: Todo[];
    isSubdomain: boolean;
    isGuest: boolean;
}

const props = defineProps<Props>();

const selectedTodo = ref<Todo | null>(null);
const showTodoModal = computed(() => selectedTodo.value !== null);

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
