<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useVoiceAssistant } from '@/composables/useVoiceAssistant';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    projectId: number | null;
}>();

const { t } = useI18n();
const {
    state,
    interimTranscript,
    toggleListening,
    formattedElapsedTime,
    isBusy,
} = useVoiceAssistant();

const buttonTitle = computed(() => {
    if (state.value === 'processing') {
        return t('dashboard.voice_assistant_processing');
    }
    if (state.value === 'listening') {
        return t('dashboard.voice_assistant_stop', { time: formattedElapsedTime() });
    }
    return t('dashboard.voice_assistant_start');
});

const buttonClasses = computed(() => {
    if (state.value === 'listening') {
        return 'text-red-600 dark:text-red-400 animate-pulse';
    }
    if (isBusy()) {
        return 'text-blue-600 dark:text-blue-400 cursor-wait';
    }
    return 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100';
});

const iconName = computed(() => {
    if (state.value === 'listening') {
        return 'Square';
    }
    if (isBusy()) {
        return 'Loader2';
    }
    return 'Mic';
});

const handleClick = () => {
    void toggleListening(props.projectId);
};
</script>

<template>
    <button
        type="button"
        :disabled="isBusy()"
        :title="buttonTitle"
        :class="[
            'inline-flex items-center justify-center gap-1.5 p-2 transition-colors',
            buttonClasses,
        ]"
        @click="handleClick"
    >
        <Icon
            :name="iconName"
            :class="['w-5 h-5', isBusy() ? 'animate-spin' : '']"
        />
        <span
            v-if="state === 'listening'"
            class="hidden text-xs font-mono tabular-nums sm:inline"
        >
            {{ formattedElapsedTime() }}
        </span>
        <span
            v-else-if="state === 'processing'"
            class="hidden text-xs sm:inline"
        >
            {{ t('dashboard.voice_assistant_processing_short') }}
        </span>
        <span
            v-if="state === 'listening' && interimTranscript"
            class="sr-only"
        >
            {{ interimTranscript }}
        </span>
    </button>
</template>
