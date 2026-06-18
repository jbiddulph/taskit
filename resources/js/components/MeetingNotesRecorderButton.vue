<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { state, toggleRecording, formattedElapsedTime, maxDurationMinutes } = useMeetingNotesRecorder();

const buttonTitle = computed(() => {
    if (state.value === 'processing') {
        return t('dashboard.meeting_notes_processing');
    }
    if (state.value === 'recording') {
        return t('dashboard.meeting_notes_stop', { time: formattedElapsedTime() });
    }
    return t('dashboard.meeting_notes_start', { minutes: maxDurationMinutes });
});

const buttonClasses = computed(() => {
    if (state.value === 'recording') {
        return 'text-red-600 dark:text-red-400 animate-pulse';
    }
    if (state.value === 'processing') {
        return 'text-blue-600 dark:text-blue-400 opacity-60 cursor-wait';
    }
    return 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100';
});
</script>

<template>
    <button
        @click="toggleRecording"
        :disabled="state === 'processing'"
        :title="buttonTitle"
        :class="[
            'inline-flex items-center justify-center gap-1.5 p-2 transition-colors',
            buttonClasses,
        ]"
    >
        <Icon
            :name="state === 'recording' ? 'Square' : 'Mic'"
            class="w-5 h-5"
        />
        <span
            v-if="state === 'recording'"
            class="text-xs font-mono tabular-nums hidden sm:inline"
        >
            {{ formattedElapsedTime() }}
        </span>
    </button>
</template>
