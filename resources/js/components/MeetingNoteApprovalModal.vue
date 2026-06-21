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
    type MeetingActionItem,
    type MeetingNoteProposal,
    type ReviewActionItem,
} from '@/services/meetingNoteProposalApi';
import { todoApi, type Project } from '@/services/todoApi';
import { matchProjectByName, matchProjectFromTranscript } from '@/utils/matchProject';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const isOpen = ref(false);
const loading = ref(false);
const submitting = ref(false);
const proposal = ref<MeetingNoteProposal | null>(null);
const projects = ref<Project[]>([]);
const selectedProjectId = ref<number | null>(null);
const reviewItems = ref<ReviewActionItem[]>([]);
const showTranscript = ref(false);

const statusOptions = [
    { value: 'todo', label: 'To Do' },
    { value: 'in-progress', label: 'In Progress' },
    { value: 'qa-testing', label: 'Q&A / Testing' },
    { value: 'done', label: 'Done' },
];

const sortedReviewItems = computed(() =>
    [...reviewItems.value].sort((a, b) => (a.confidence ?? 1) - (b.confidence ?? 1)),
);

const notify = (type: 'success' | 'error' | 'warning', title: string, message: string) => {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
};

const confidenceLabel = (confidence?: number | null) => {
    const value = confidence ?? 0.75;
    if (value >= 0.85) return { text: 'High confidence', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' };
    if (value >= 0.65) return { text: 'Medium confidence', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' };
    return { text: 'Low confidence — review first', class: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' };
};

const resetState = () => {
    proposal.value = null;
    reviewItems.value = [];
    selectedProjectId.value = null;
    showTranscript.value = false;
};

const resolveSuggestedProjectId = (data: MeetingNoteProposal): number | null => {
    const candidates = [
        data.metadata?.suggested_project_name,
        ...data.action_items.map((item) => item.project_name),
    ];

    for (const name of candidates) {
        const matchedId = matchProjectByName(projects.value, name);
        if (matchedId) {
            return matchedId;
        }
    }

    return matchProjectFromTranscript(projects.value, data.transcript);
};

const buildReviewItem = (item: MeetingActionItem, index: number): ReviewActionItem => ({
    index,
    approved: true,
    title: item.title,
    priority: item.priority || 'Medium',
    status: item.status || 'todo',
    due_date: item.due_date || '',
    assignee: item.assignee || '',
    description: item.notes || '',
    location_query: item.location_query || item.location_name || '',
    location_name: item.location_name ?? null,
    location_address: item.location_address ?? null,
    latitude: item.latitude ?? null,
    longitude: item.longitude ?? null,
    confidence: item.confidence ?? null,
});

const buildReviewItems = (data: MeetingNoteProposal) => {
    reviewItems.value = data.action_items.map((item, index) => buildReviewItem(item, index));
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
        selectedProjectId.value =
            resolveSuggestedProjectId(data) ?? projects.value[0]?.id ?? null;
    } catch (error: any) {
        const message = error.response?.data?.message || 'Could not load meeting note proposal.';
        notify('error', 'Load Failed', message);
        isOpen.value = false;
        resetState();
    } finally {
        loading.value = false;
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
                status: item.status,
                due_date: item.due_date.trim() || null,
                assignee: item.assignee.trim() || null,
                description: item.description.trim() || null,
                location_query: item.location_query.trim() || null,
                location_name: item.location_name ?? null,
                location_address: item.location_address ?? null,
                latitude: item.latitude ?? null,
                longitude: item.longitude ?? null,
            }))
        );

        notify(
            'success',
            'Todos Created',
            `${result.created_count} todo(s) created from your meeting notes.`
        );

        if (result.todos?.length) {
            window.dispatchEvent(new CustomEvent('meetingNotesTodosCreated', {
                detail: {
                    todos: result.todos,
                    projectId: result.project_id,
                },
            }));
        }

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
                    Items are sorted by confidence — review low-confidence items first. Locations are geocoded when you approve.
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
                    <p
                        v-if="proposal.metadata?.suggested_project_name"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        AI suggested: {{ proposal.metadata.suggested_project_name }}
                    </p>
                </div>

                <div v-if="reviewItems.length === 0" class="rounded-md border border-dashed p-4 text-sm text-muted-foreground">
                    No action items were detected in this recording. You can dismiss this proposal.
                </div>

                <div v-else class="space-y-3">
                    <p class="text-sm font-medium">Proposed action items (low confidence first)</p>
                    <div
                        v-for="item in sortedReviewItems"
                        :key="item.index"
                        class="rounded-md border p-3 space-y-2"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input v-model="item.approved" type="checkbox" class="rounded" />
                                Include this todo
                            </label>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="confidenceLabel(item.confidence).class"
                            >
                                {{ confidenceLabel(item.confidence).text }}
                                <span v-if="item.confidence != null" class="opacity-75">
                                    ({{ Math.round(item.confidence * 100) }}%)
                                </span>
                            </span>
                        </div>
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
                            <select
                                v-model="item.status"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input
                                v-model="item.due_date"
                                type="date"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <input
                                v-model="item.assignee"
                                type="text"
                                class="rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Assignee (optional)"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Location (from speech)</label>
                            <input
                                v-model="item.location_query"
                                type="text"
                                class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="e.g. Brighton Pier — geocoded on approval"
                            />
                            <p
                                v-if="item.location_address || item.latitude"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                Preview: {{ item.location_address || item.location_name }}
                            </p>
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
