<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { state } = useMeetingNotesRecorder();
</script>

<template>
    <div
        v-if="state === 'recording'"
        class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100"
    >
        <div class="flex items-start gap-3">
            <Icon name="Mic" class="h-5 w-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
            <div class="space-y-2">
                <p class="font-medium">{{ t('dashboard.meeting_notes_tips_title') }}</p>
                <p class="text-amber-800 dark:text-amber-200 mb-2">{{ t('dashboard.meeting_notes_tips_intro') }}</p>
                <ul class="list-disc pl-5 space-y-1 text-amber-900 dark:text-amber-100">
                    <li>{{ t('dashboard.meeting_notes_tip_count') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_priority') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_project') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_column') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_due_date') }}</li>
                </ul>
                <p class="mt-3 text-xs text-amber-700 dark:text-amber-300 italic">
                    {{ t('dashboard.meeting_notes_tip_example') }}
                </p>
            </div>
        </div>
    </div>
</template>
