<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MapView from '@/components/MapView.vue';
import { todayApi } from '@/services/todayApi';
import type { Todo } from '@/services/todoApi';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    projectId?: number | null;
    todos: Todo[];
    isReadOnly?: boolean;
    currentUserName?: string;
}>();

const emit = defineEmits<{
    'edit-todo': [todo: Todo];
    'todo-updated': [todo: Todo];
}>();

const { t } = useI18n();
const loading = ref(true);
const spokenSummary = ref('');
const overdueCount = ref(0);
const dueTodayCount = ref(0);
const viewingsCount = ref(0);
const routeCount = ref(0);
const pendingCheckIns = ref(0);

const todayTodos = computed(() => {
    const today = new Date().toISOString().slice(0, 10);

    return props.todos.filter((todo) => {
        if (todo.status === 'done' || !todo.due_date) {
            return false;
        }

        const due = String(todo.due_date).slice(0, 10);
        return due <= today;
    });
});

const loadSummary = async () => {
    loading.value = true;
    try {
        const data = await todayApi.getSummary(props.projectId ?? null);
        spokenSummary.value = data.spoken_summary;
        overdueCount.value = data.overdue.length;
        dueTodayCount.value = data.due_today.length;
        viewingsCount.value = data.viewings.length;
        routeCount.value = data.route_stops.length;
        pendingCheckIns.value = data.pending_check_ins.length;
    } catch {
        spokenSummary.value = t('today.load_error');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    void loadSummary();
});

watch(() => props.projectId, () => {
    void loadSummary();
});
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-violet-100 dark:bg-violet-900/40 p-2 text-violet-700 dark:text-violet-200">
                    <Icon name="Sparkles" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('today.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ loading ? t('common.loading') : spokenSummary }}
                    </p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="rounded-md bg-red-50 dark:bg-red-900/20 px-3 py-2 text-center">
                    <p class="text-lg font-semibold text-red-700 dark:text-red-300">{{ overdueCount }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400">{{ t('today.overdue') }}</p>
                </div>
                <div class="rounded-md bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-center">
                    <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ dueTodayCount }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ t('today.due_today') }}</p>
                </div>
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 px-3 py-2 text-center">
                    <p class="text-lg font-semibold text-blue-700 dark:text-blue-300">{{ viewingsCount }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ t('today.viewings') }}</p>
                </div>
                <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-center">
                    <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">{{ routeCount }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ t('today.route_stops') }}</p>
                </div>
            </div>

            <p v-if="pendingCheckIns > 0" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ t('today.pending_check_ins', { count: pendingCheckIns }) }}
            </p>
        </div>

        <MapView
            :todos="todayTodos"
            :is-read-only="isReadOnly"
            :current-user-name="currentUserName"
            @edit-todo="emit('edit-todo', $event)"
            @todo-updated="emit('todo-updated', $event)"
        />
    </div>
</template>
