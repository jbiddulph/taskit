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
import {
    meetingNoteProposalApi,
    type MeetingNoteProposal,
    type ReviewActionItem,
} from '@/services/meetingNoteProposalApi';
import { todoApi, type Project } from '@/services/todoApi';
import { onMounted, onUnmounted, ref, watch } from 'vue';

const isOpen = ref(false);
const loading = ref(false);
const submitting = ref(false);
const proposal = ref<MeetingNoteProposal | null>(null);
const projects = ref<Project[]>([]);
const selectedProjectId = ref<number | null>(null);
const reviewItems = ref<ReviewActionItem[]>([]);
const showTranscript = ref(false);

const notify = (type: 'success' | 'error' | 'warning', title: string, message: string) => {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
};

const resetState = () => {
    proposal.value = null;
    reviewItems.value = [];
    selectedProjectId.value = null;
    showTranscript.value = false;
};

const buildReviewItems = (data: MeetingNoteProposal) => {
    reviewItems.value = data.action_items.map((item, index) => ({
        index,
        approved: true,
        title: item.title,
        priority: item.priority || 'Medium',
        assignee: item.assignee || '',
        description: item.notes || '',
    }));
};

const openProposal = async (proposalId: number) => {
    loading.value = true;
    isOpen.value = true;

    try {
        if (projects.value.length === 0) {
            projects.value = await todoApi.getProjects();
        }

        const data = await meetingNoteProposalApi.getProposal(proposalId);
        proposal.value = data;
        buildReviewItems(data);
        selectedProjectId.value = projects.value[0]?.id ?? null;
    } catch {
        notify('error', 'Load Failed', 'Could not load meeting note proposal.');
        isOpen.value = false;
        resetState();
    } finally {
        loading.value = false;
    }
};

const checkPendingOnLoad = async () => {
    try {
        const pending = await meetingNoteProposalApi.getPending();
        if (pending.length > 0) {
            await openProposal(pending[0].id);
        }
    } catch {
        // ignore
    }
};

const handleOpenEvent = (event: Event) => {
    const detail = (event as CustomEvent).detail;
    if (detail?.proposalId) {
        openProposal(detail.proposalId);
    }
};

const closeModal = () => {
    isOpen.value = false;
    resetState();
};

const dismissAll = async () => {
    if (!proposal.value || submitting.value) {
        return;
    }

    submitting.value = true;
    try {
        await meetingNoteProposalApi.dismiss(proposal.value.id);
        notify('info', 'Dismissed', 'Meeting note proposal dismissed.');
        closeModal();
    } catch (error: any) {
        notify('error', 'Dismiss Failed', error.response?.data?.message || error.message);
    } finally {
        submitting.value = false;
    }
};

const approveSelected = async () => {
    if (!proposal.value || !selectedProjectId.value || submitting.value) {
        return;
    }

    const approvedCount = reviewItems.value.filter((item) => item.approved).length;
    if (approvedCount === 0) {
        notify('warning', 'Nothing Selected', 'Select at least one action item to approve.');
        return;
    }

    submitting.value = true;
    try {
        const result = await meetingNoteProposalApi.approve(
            proposal.value.id,
            selectedProjectId.value,
            reviewItems.value.map((item) => ({
                index: item.index,
                approved: item.approved,
                title: item.title.trim(),
                priority: item.priority,
                assignee: item.assignee.trim() || null,
                description: item.description.trim() || null,
            }))
        );

        notify(
            'success',
            'Todos Created',
            `${result.created_count} todo(s) created from your meeting notes.`
        );
        closeModal();
    } catch (error: any) {
        notify('error', 'Approval Failed', error.response?.data?.message || error.message);
    } finally {
        submitting.value = false;
    }
};

watch(isOpen, (open) => {
    if (!open) {
        resetState();
    }
});

onMounted(() => {
    window.addEventListener('openMeetingNoteApproval', handleOpenEvent);
    checkPendingOnLoad();
});

onUnmounted(() => {
    window.removeEventListener('openMeetingNoteApproval', handleOpenEvent);
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="(value) => { if (!value) closeModal(); else isOpen = value; }">
        <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Review meeting action items</DialogTitle>
                <DialogDescription>
                    Approve, edit, or reject proposed todos before they are created in your project.
                </DialogDescription>
            </DialogHeader>

            <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">
                Loading proposal...
            </div>

            <div v-else-if="proposal" class="space-y-4">
                <div v-if="proposal.meeting_summary" class="rounded-md border p-3 text-sm">
                    <p class="font-medium mb-1">Summary</p>
                    <p class="text-muted-foreground whitespace-pre-wrap">{{ proposal.meeting_summary }}</p>
                </div>

                <button
                    v-if="proposal.transcript"
                    type="button"
                    class="text-sm text-blue-600 hover:underline dark:text-blue-400"
                    @click="showTranscript = !showTranscript"
                >
                    {{ showTranscript ? 'Hide transcript' : 'Show transcript' }}
                </button>
                <div
                    v-if="showTranscript && proposal.transcript"
                    class="rounded-md border p-3 text-xs text-muted-foreground max-h-40 overflow-y-auto whitespace-pre-wrap"
                >
                    {{ proposal.transcript }}
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Create todos in project</label>
                    <select
                        v-model="selectedProjectId"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                </div>

                <div v-if="reviewItems.length === 0" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                    No action items were detected in this recording. You can dismiss this proposal.
                </div>

                <div v-else class="space-y-3">
                    <p class="text-sm font-medium">Proposed action items</p>
                    <div
                        v-for="item in reviewItems"
                        :key="item.index"
                        class="rounded-md border p-3 space-y-2"
                    >
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input v-model="item.approved" type="checkbox" class="rounded" />
                            Include this todo
                        </label>
                        <input
                            v-model="item.title"
                            type="text"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Todo title"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <select
                                v-model="item.priority"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                            <input
                                v-model="item.assignee"
                                type="text"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Assignee (optional)"
                            />
                        </div>
                        <textarea
                            v-model="item.description"
                            rows="2"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Description (optional)"
                        />
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" :disabled="submitting" @click="dismissAll">
                    Dismiss all
                </Button>
                <Button
                    :disabled="submitting || !proposal || reviewItems.length === 0 || !selectedProjectId"
                    @click="approveSelected"
                >
                    {{ submitting ? 'Creating...' : 'Approve selected' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
