<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { state, elapsedSeconds, formattedElapsedTime } = useMeetingNotesRecorder();

const isOpen = ref(false);
const dismissed = ref(false);
const elapsedDisplay = computed(() => {
    elapsedSeconds.value;
    return formattedElapsedTime();
});

watch(state, (newState) => {
    if (newState === 'recording') {
        dismissed.value = false;
        isOpen.value = true;
        return;
    }

    isOpen.value = false;
    dismissed.value = false;
});

const minimize = () => {
    dismissed.value = true;
    isOpen.value = false;
};

const reopen = () => {
    dismissed.value = false;
    isOpen.value = true;
};
</script>

<template>
    <button
        v-if="state === 'recording' && dismissed"
        type="button"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-950 shadow-lg transition hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900"
        @click="reopen"
    >
        <Icon name="Mic" class="h-4 w-4 text-red-500 animate-pulse" />
        {{ t('dashboard.meeting_notes_tips_show_again') }}
    </button>

    <Dialog
        :open="isOpen && state === 'recording'"
        @update:open="(value) => { if (!value) minimize(); }"
    >
        <DialogContent class="sm:max-w-lg border-amber-200 dark:border-amber-800">
            <DialogHeader>
                <div class="flex items-center gap-2 pr-8">
                    <Icon name="Mic" class="h-5 w-5 text-red-500 animate-pulse" />
                    <DialogTitle>{{ t('dashboard.meeting_notes_tips_title') }}</DialogTitle>
                </div>
                <DialogDescription>
                    {{ t('dashboard.meeting_notes_tips_recording', { time: elapsedDisplay }) }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 text-sm">
                <p class="text-muted-foreground">{{ t('dashboard.meeting_notes_tips_intro') }}</p>
                <ul class="list-disc space-y-2 pl-5">
                    <li>{{ t('dashboard.meeting_notes_tip_count') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_priority') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_project') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_column') }}</li>
                    <li>{{ t('dashboard.meeting_notes_tip_due_date') }}</li>
                </ul>
                <p class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs italic text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                    {{ t('dashboard.meeting_notes_tip_example') }}
                </p>
            </div>

            <DialogFooter>
                <Button class="w-full sm:w-auto" @click="minimize">
                    {{ t('dashboard.meeting_notes_tips_got_it') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
