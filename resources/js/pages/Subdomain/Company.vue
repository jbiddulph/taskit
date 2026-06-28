<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Globe, Users, Calendar, CheckCircle, Lock, Activity, Building2, ArrowRight } from 'lucide-vue-next';
import SubdomainLayout from '@/layouts/SubdomainLayout.vue';
import TodoBoard from '@/components/TodoBoard.vue';
import ActivityFeed from '@/components/ActivityFeed.vue';
import { realtimeService } from '@/services/realtimeService';
import { ref, onMounted, computed } from 'vue';
import Icon from '@/components/Icon.vue';

interface BackgroundAttribution {
    name?: string;
    profile_url?: string;
    photo_url?: string;
}

interface HomepageBackground {
    url: string;
    attribution?: BackgroundAttribution;
}

interface Company {
    id: number;
    name: string;
    code: string;
    industry?: string;
    subscription_type: string;
    logo_url?: string;
    subdomain?: string;
    subdomain_url?: string;
    is_public?: boolean;
    about_text?: string | null;
    homepage_tagline?: string | null;
    homepage_background_url?: string | null;
    homepage_background_attribution?: BackgroundAttribution | null;
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
    homepageBackground?: HomepageBackground;
    industryLabel?: string;
    todos?: Todo[];
    projects?: Project[];
    activities?: Activity[];
    selectedProject?: Project;
}

const props = defineProps<Props>();

const showCodeModal = ref(false);
const enteredCode = ref('');
const codeError = ref('');
const isVerifying = ref(false);
const hasValidCode = ref(false);
const showCalendar = ref(false);
const showActivityFeed = ref(false);
const showTodoModal = ref(false);
const selectedTodo = ref<Todo | null>(null);

const loginUrl = computed(() =>
    props.company.subdomain
        ? `https://${props.company.subdomain}.zaptask.co.uk/login`
        : '/login',
);

const heroBackgroundUrl = computed(
    () => props.homepageBackground?.url || props.company.homepage_background_url || '',
);

const heroAttribution = computed(
    () => props.homepageBackground?.attribution || props.company.homepage_background_attribution || null,
);

const heroStyle = computed(() => {
    if (!heroBackgroundUrl.value) {
        return {};
    }

    return {
        backgroundImage: `linear-gradient(rgba(15, 23, 42, 0.72), rgba(15, 23, 42, 0.82)), url(${heroBackgroundUrl.value})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    };
});

const displayTagline = computed(
    () => props.company.homepage_tagline || `Welcome to ${props.company.name}`,
);

const seoDescription = computed(() => {
    if (props.company.about_text) {
        return props.company.about_text.slice(0, 160);
    }

    return `Learn about ${props.company.name} and access the company portal on ZapTask.`;
});

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

const verifyCode = async () => {
    if (!enteredCode.value.trim()) {
        codeError.value = 'Please enter a company code';
        return;
    }

    isVerifying.value = true;
    codeError.value = '';

    await new Promise(resolve => setTimeout(resolve, 500));

    if (enteredCode.value.trim().toLowerCase() === props.company.code.toLowerCase()) {
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

const toggleCalendar = () => {
    showCalendar.value = !showCalendar.value;
};

const toggleActivityFeed = () => {
    showActivityFeed.value = !showActivityFeed.value;
};

const openTodoModal = (todo: Todo) => {
    selectedTodo.value = todo;
    showTodoModal.value = true;
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const shouldBlurContent = computed(() => props.company.is_public && !hasValidCode.value);

onMounted(() => {
    if (props.company.is_public) {
        if (!checkStoredCode()) {
            showCodeModal.value = true;
        }

        realtimeService.init(props.company.id, props.company.id);
    }
});
</script>

<template>
    <SeoHead
        :title="`${company.name} - Company Portal`"
        :description="seoDescription"
        :image="company.logo_url || '/zap_icon.png'"
    />

    <SubdomainLayout>
        <div class="min-h-screen bg-slate-950 text-white">
            <!-- Hero -->
            <section
                class="relative min-h-[42vh] bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900"
                :style="heroStyle"
            >
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent" />

                <div class="relative container mx-auto px-4 py-12 md:py-16">
                    <div class="max-w-4xl">
                        <div class="flex flex-col sm:flex-row items-start gap-6 mb-8">
                            <div class="h-24 w-24 shrink-0 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="company.logo_url"
                                    :src="company.logo_url"
                                    :alt="`${company.name} logo`"
                                    class="h-full w-full object-contain p-2"
                                />
                                <Building2 v-else class="w-10 h-10 text-white/80" />
                            </div>

                            <div class="min-w-0">
                                <p v-if="industryLabel" class="text-sm uppercase tracking-wider text-blue-200/90 mb-2">
                                    {{ industryLabel }}
                                </p>
                                <h1 class="text-3xl md:text-5xl font-bold tracking-tight text-white mb-3">
                                    {{ company.name }}
                                </h1>
                                <p class="text-lg md:text-xl text-slate-200 max-w-2xl">
                                    {{ displayTagline }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="company.about_text"
                            class="prose prose-invert prose-sm md:prose-base max-w-3xl text-slate-200 whitespace-pre-line mb-8"
                        >
                            {{ company.about_text }}
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button as-child size="lg" class="bg-white text-slate-900 hover:bg-slate-100">
                                <a :href="loginUrl">
                                    Employee Login
                                    <ArrowRight class="w-4 h-4 ml-2" />
                                </a>
                            </Button>
                            <Button as-child variant="outline" size="lg" class="border-white/30 text-white hover:bg-white/10">
                                <a href="https://zaptask.co.uk">Visit ZapTask</a>
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="heroAttribution?.name"
                    class="relative container mx-auto px-4 pb-4 text-xs text-slate-400"
                >
                    Photo by
                    <a
                        :href="heroAttribution.profile_url || 'https://unsplash.com'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="underline hover:text-slate-200"
                    >{{ heroAttribution.name }}</a>
                    on
                    <a
                        :href="heroAttribution.photo_url || 'https://unsplash.com'"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="underline hover:text-slate-200"
                    >Unsplash</a>
                </div>
            </section>

            <!-- Public dashboard -->
            <div v-if="company.is_public" class="relative bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen">
                <div class="bg-white dark:bg-slate-800 shadow-sm border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40">
                    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between items-center py-4">
                            <div>
                                <h2 class="text-lg font-semibold">Public Dashboard</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Live project progress</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="toggleCalendar"
                                    :title="showCalendar ? 'Hide Calendar' : 'Show Calendar'"
                                    :class="[
                                        'inline-flex items-center justify-center p-2 transition-colors rounded-md',
                                        showCalendar
                                            ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400',
                                    ]"
                                >
                                    <Calendar class="w-5 h-5" />
                                </button>
                                <button
                                    @click="toggleActivityFeed"
                                    :title="showActivityFeed ? 'Hide Activity Feed' : 'Show Activity Feed'"
                                    :class="[
                                        'inline-flex items-center justify-center p-2 transition-colors rounded-md',
                                        showActivityFeed
                                            ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30'
                                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400',
                                    ]"
                                >
                                    <Activity class="w-5 h-5" />
                                </button>
                                <Button as-child variant="outline" size="sm">
                                    <a :href="loginUrl">Employee Login</a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-semibold">Project Tasks</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">View our current project tasks and progress</p>
                        </div>
                        <div class="p-6">
                            <TodoBoard
                                :todos="todos || []"
                                :projects="projects || []"
                                :selectedProject="selectedProject"
                                :isReadOnly="true"
                                :showCalendar="showCalendar"
                                :showActivityFeed="showActivityFeed"
                                @todo-click="openTodoModal"
                                @toggle-calendar="toggleCalendar"
                                @toggle-activity-feed="toggleActivityFeed"
                            />
                        </div>
                    </div>

                    <div v-if="showActivityFeed" class="mt-8">
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                                <h2 class="text-lg font-semibold">Recent Activity</h2>
                            </div>
                            <div class="p-6">
                                <ActivityFeed :activities="activities || []" :isReadOnly="true" />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="shouldBlurContent"
                    class="absolute inset-0 top-[42vh] bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center"
                >
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
                                    <label for="company-code" class="block text-sm font-medium mb-2">Company Code</label>
                                    <Input
                                        id="company-code"
                                        v-model="enteredCode"
                                        type="text"
                                        placeholder="Enter company code"
                                        class="w-full"
                                        :disabled="isVerifying"
                                        @keydown.enter="verifyCode"
                                    />
                                    <p v-if="codeError" class="text-sm text-red-600 mt-1">{{ codeError }}</p>
                                </div>

                                <Button
                                    @click="verifyCode"
                                    :disabled="isVerifying || !enteredCode.trim()"
                                    class="w-full"
                                >
                                    <Lock class="w-4 h-4 mr-2" />
                                    {{ isVerifying ? 'Verifying...' : 'Access Dashboard' }}
                                </Button>

                                <p class="text-xs text-center text-slate-500">
                                    Contact {{ company.name }} for the company code
                                </p>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Private company landing -->
            <div v-else class="container mx-auto px-4 py-10">
                <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                    <Card class="bg-slate-900/50 border-slate-700 text-white">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-white">
                                <Users class="w-5 h-5" />
                                Employee Access
                            </CardTitle>
                            <CardDescription class="text-slate-300">
                                Sign in to manage tasks, projects, and team collaboration.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button as-child class="w-full">
                                <a :href="loginUrl">Employee Login</a>
                            </Button>
                            <Button as-child variant="outline" class="w-full border-slate-600 text-white hover:bg-slate-800">
                                <a href="https://zaptask.co.uk">Visit Main Site</a>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="bg-slate-900/50 border-slate-700 text-white">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-white">
                                <Globe class="w-5 h-5" />
                                What we use ZapTask for
                            </CardTitle>
                            <CardDescription class="text-slate-300">
                                Task management built for {{ industryLabel || 'your team' }}.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 text-sm text-slate-200">
                                <div class="flex items-center gap-2">
                                    <CheckCircle class="w-4 h-4 text-green-400" />
                                    Task &amp; project tracking
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle class="w-4 h-4 text-green-400" />
                                    Team collaboration
                                </div>
                                <div class="flex items-center gap-2">
                                    <CheckCircle class="w-4 h-4 text-green-400" />
                                    Industry-specific task types
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <footer class="container mx-auto px-4 py-8 text-center text-sm text-slate-400">
                <p>Powered by <a href="https://zaptask.co.uk" class="text-blue-400 hover:underline">ZapTask</a></p>
            </footer>
        </div>

        <Dialog :open="showTodoModal" @update:open="showTodoModal = $event">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Icon name="FileText" class="w-5 h-5 text-blue-600" />
                        Task Details
                    </DialogTitle>
                    <DialogDescription>View detailed information about this task</DialogDescription>
                </DialogHeader>

                <div v-if="selectedTodo" class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">{{ selectedTodo.title }}</h3>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
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

                    <div v-if="selectedTodo.description">
                        <h4 class="text-sm font-medium mb-2">Description</h4>
                        <div class="prose prose-sm max-w-none" v-html="selectedTodo.description" />
                    </div>

                    <div v-if="selectedTodo.subtasks && selectedTodo.subtasks.length > 0">
                        <h4 class="text-sm font-medium mb-3">Subtasks</h4>
                        <div class="space-y-2">
                            <div
                                v-for="subtask in selectedTodo.subtasks"
                                :key="subtask.id"
                                class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg"
                            >
                                <Icon name="CheckCircle" class="w-4 h-4 text-green-500" />
                                <span class="text-sm">{{ subtask.title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </SubdomainLayout>
</template>
