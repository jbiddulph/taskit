<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    hasProject: boolean;
    hasBoard: boolean;
    hasVoiceTask: boolean;
    hasMapTask: boolean;
}>();

const { t } = useI18n();
const dismissed = ref(false);

const STORAGE_KEY = 'zaptask_onboarding_dismissed_v1';

onMounted(() => {
    dismissed.value = localStorage.getItem(STORAGE_KEY) === '1';
});

const steps = computed(() => [
    { key: 'project', done: props.hasProject, label: t('onboarding.step_project') },
    { key: 'board', done: props.hasBoard, label: t('onboarding.step_board') },
    { key: 'voice', done: props.hasVoiceTask, label: t('onboarding.step_voice') },
    { key: 'map', done: props.hasMapTask, label: t('onboarding.step_map') },
]);

const completedCount = computed(() => steps.value.filter((step) => step.done).length);
const isComplete = computed(() => completedCount.value === steps.value.length);
const visible = computed(() => !dismissed.value && !isComplete.value);

const dismiss = () => {
    dismissed.value = true;
    localStorage.setItem(STORAGE_KEY, '1');
};
</script>

<template>
    <div
        v-if="visible"
        class="mb-3 rounded-lg border border-violet-200 bg-violet-50 dark:border-violet-800 dark:bg-violet-950/40 p-3"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-violet-900 dark:text-violet-100">{{ t('onboarding.title') }}</p>
                <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">{{ t('onboarding.subtitle') }}</p>
            </div>
            <button
                type="button"
                class="text-violet-500 hover:text-violet-700 dark:hover:text-violet-200"
                :aria-label="t('common.close')"
                @click="dismiss"
            >
                <Icon name="X" class="w-4 h-4" />
            </button>
        </div>

        <ol class="mt-3 space-y-2">
            <li
                v-for="step in steps"
                :key="step.key"
                class="flex items-center gap-2 text-sm"
                :class="step.done ? 'text-emerald-700 dark:text-emerald-300' : 'text-violet-800 dark:text-violet-200'"
            >
                <Icon :name="step.done ? 'CheckCircle2' : 'Circle'" class="w-4 h-4 shrink-0" />
                <span>{{ step.label }}</span>
            </li>
        </ol>

        <p class="mt-2 text-xs text-violet-600 dark:text-violet-400">
            {{ t('onboarding.progress', { done: completedCount, total: steps.length }) }}
        </p>
    </div>
</template>
