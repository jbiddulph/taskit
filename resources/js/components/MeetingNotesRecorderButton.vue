<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { state, toggleRecording, formattedElapsedTime, isBusy } = useMeetingNotesRecorder();

const buttonTitle = computed(() => {
    if (state.value === 'uploading') {
        return t('dashboard.meeting_notes_uploading');
    }
    if (state.value === 'generating') {
        return t('dashboard.meeting_notes_generating');
    }
    if (state.value === 'recording') {
        return t('dashboard.meeting_notes_stop', { time: formattedElapsedTime() });
    }
    return t('dashboard.ai_dashboard_start');
});

const buttonClasses = computed(() => {
    if (state.value === 'recording') {
        return 'text-red-600 dark:text-red-400 animate-pulse';
    }
    if (isBusy()) {
        return 'text-blue-600 dark:text-blue-400 cursor-wait';
    }
    return 'text-violet-600 hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300';
});

const iconName = computed(() => {
    if (state.value === 'recording') {
        return 'Square';
    }
    if (isBusy()) {
        return 'Loader2';
    }
    return 'Sparkles';
});
</script>

<template>
    <button
        @click="toggleRecording"
        :disabled="isBusy()"
        :title="buttonTitle"
        :class="[
            'inline-flex items-center justify-center gap-1.5 p-2 transition-colors',
            buttonClasses,
        ]"
    >
        <Icon
            :name="iconName"
            :class="['w-5 h-5', isBusy() ? 'animate-spin' : '']"
        />
        <span
            v-if="state === 'recording'"
            class="text-xs font-mono tabular-nums hidden sm:inline"
        >
            {{ formattedElapsedTime() }}
        </span>
        <span
            v-else-if="state === 'generating'"
            class="text-xs hidden sm:inline"
        >
            {{ t('dashboard.meeting_notes_generating_short') }}
        </span>
        <span
            v-else-if="state === 'uploading'"
            class="text-xs hidden sm:inline"
        >
            {{ t('dashboard.meeting_notes_uploading_short') }}
        </span>
    </button>
</template>
