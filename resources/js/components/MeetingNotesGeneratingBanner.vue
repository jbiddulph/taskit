<script setup lang="ts">
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import Icon from '@/components/Icon.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { state } = useMeetingNotesRecorder();
</script>

<template>
    <div
        v-if="state === 'generating' || state === 'uploading'"
        class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100"
    >
        <Icon name="Loader2" class="h-5 w-5 shrink-0 animate-spin" />
        <div>
            <p class="font-medium">
                {{ state === 'uploading' ? t('dashboard.meeting_notes_uploading') : t('dashboard.meeting_notes_generating') }}
            </p>
            <p class="text-blue-700 dark:text-blue-300">
                {{ t('dashboard.meeting_notes_generating_hint') }}
            </p>
        </div>
    </div>
</template>
