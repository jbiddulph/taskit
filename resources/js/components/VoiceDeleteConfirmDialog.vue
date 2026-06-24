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
import { useVoiceAssistant } from '@/composables/useVoiceAssistant';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    projectId: number | null;
}>();

const { t } = useI18n();
const { pendingDelete, confirmPendingDelete, dismissPendingDelete, isBusy } = useVoiceAssistant();

const isOpen = computed(() => pendingDelete.value !== null);

const handleConfirm = () => {
    void confirmPendingDelete(props.projectId);
};

const handleDismiss = () => {
    dismissPendingDelete();
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="(open) => { if (!open) handleDismiss(); }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('dashboard.voice_delete_confirm_title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('dashboard.voice_delete_confirm_body') }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="pendingDelete" class="rounded-md border bg-muted/40 p-3 text-sm">
                <p class="font-medium">{{ pendingDelete.title }}</p>
                <p v-if="pendingDelete.reference_id" class="mt-1 text-xs text-muted-foreground">
                    {{ pendingDelete.reference_id }}
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" :disabled="isBusy()" @click="handleDismiss">
                    {{ t('common.cancel') }}
                </Button>
                <Button variant="destructive" :disabled="isBusy()" @click="handleConfirm">
                    {{ isBusy() ? t('common.loading') : t('common.delete') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
