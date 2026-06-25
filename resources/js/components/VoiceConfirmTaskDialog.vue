<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useMeetingNotesRecorder } from '@/composables/useMeetingNotesRecorder';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const { pendingTask, confirmPendingTask, dismissPendingTask, isBusy } = useMeetingNotesRecorder();

const isOpen = computed(() => pendingTask.value !== null);

const handleSelect = (todoId: number) => {
    void confirmPendingTask(todoId);
};

const handleDismiss = () => {
    dismissPendingTask();
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(open) => { if (!open) handleDismiss(); }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('dashboard.voice_task_confirm_title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('dashboard.voice_task_confirm_body') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="pendingTask" class="space-y-2">
                <button
                    v-for="candidate in pendingTask.candidates"
                    :key="candidate.todo_id"
                    type="button"
                    class="w-full rounded-md border bg-muted/40 p-3 text-left text-sm hover:bg-muted transition-colors"
                    :disabled="isBusy()"
                    @click="handleSelect(candidate.todo_id)"
                >
                    <p class="font-medium">{{ candidate.title }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ candidate.reference_id }}
                        <span v-if="candidate.project_name"> · {{ candidate.project_name }}</span>
                    </p>
                </button>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="isBusy()" @click="handleDismiss">
                    {{ t('common.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
