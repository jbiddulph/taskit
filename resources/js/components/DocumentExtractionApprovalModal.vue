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
import { documentExtractionApi, type DocumentExtractionProposal } from '@/services/operationalSiteApi';
import { todoApi, type Project } from '@/services/todoApi';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const isOpen = ref(false);
const loading = ref(false);
const submitting = ref(false);
const proposal = ref<DocumentExtractionProposal | null>(null);
const projects = ref<Project[]>([]);
const selectedProjectId = ref<number | null>(null);

const notify = (type: 'success' | 'error', title: string, message: string) => {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
};

const fieldRows = computed(() => {
    const data = proposal.value?.extracted_data ?? {};
    const skip = new Set(['suggested_tasks', 'summary', 'confidence', 'source']);
    const order = ['document_type', 'label', 'expiry_date', 'issue_date', 'certificate_number', 'engineer_name', 'address'];
    const keys = [
        ...order.filter((key) => key in data),
        ...Object.keys(data).filter((key) => !order.includes(key) && !skip.has(key)),
    ];

    return keys.map((key) => {
        const value = data[key];
        return {
            key,
            label: key.replace(/_/g, ' '),
            value: value == null || value === '' ? '—' : typeof value === 'string' ? value : JSON.stringify(value),
        };
    });
});

const suggestedTasks = computed(() => {
    const tasks = proposal.value?.extracted_data?.suggested_tasks;
    return Array.isArray(tasks) ? tasks.filter((task) => task && typeof task === 'object' && 'title' in (task as object)) : [];
});

const openProposal = async (proposalId: number) => {
    loading.value = true;
    isOpen.value = true;

    try {
        if (projects.value.length === 0) {
            projects.value = await todoApi.getProjects();
            selectedProjectId.value = projects.value[0]?.id ?? null;
        }

        const pending = await documentExtractionApi.getPending();
        proposal.value = pending.find((p) => p.id === proposalId) ?? null;

        if (!proposal.value) {
            throw new Error('Proposal not found');
        }

        if (proposal.value.project_id) {
            selectedProjectId.value = proposal.value.project_id;
        }
    } catch (error: any) {
        notify('error', 'Load failed', error.message || 'Could not load extraction proposal.');
        isOpen.value = false;
        proposal.value = null;
    } finally {
        loading.value = false;
    }
};

const approve = async () => {
    if (!proposal.value) return;
    if (!selectedProjectId.value) {
        notify('warning', 'Project required', 'Select a board for reminder tasks.');
        return;
    }
    submitting.value = true;
    try {
        const result = await documentExtractionApi.approve(proposal.value.id, selectedProjectId.value);
        notify('success', 'Applied', result.message || 'Certificate details saved and tasks created.');
        isOpen.value = false;
        proposal.value = null;
        window.dispatchEvent(new CustomEvent('document-extraction-reviewed'));
        window.dispatchEvent(new CustomEvent('todoChanged'));
    } catch (error: any) {
        notify('error', 'Failed', error.response?.data?.message || 'Could not apply extraction.');
    } finally {
        submitting.value = false;
    }
};

const dismiss = async () => {
    if (!proposal.value) return;
    submitting.value = true;
    try {
        await documentExtractionApi.dismiss(proposal.value.id);
        isOpen.value = false;
        proposal.value = null;
        window.dispatchEvent(new CustomEvent('document-extraction-reviewed'));
    } catch (error: any) {
        notify('error', 'Failed', error.response?.data?.message || 'Could not dismiss.');
    } finally {
        submitting.value = false;
    }
};

const onOpenEvent = (event: Event) => {
    const detail = (event as CustomEvent).detail;
    if (detail?.proposalId) {
        openProposal(detail.proposalId);
    }
};

onMounted(() => {
    window.addEventListener('open-document-extraction', onOpenEvent);
});

onUnmounted(() => {
    window.removeEventListener('open-document-extraction', onOpenEvent);
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Review certificate extraction</DialogTitle>
                <DialogDescription>
                    OpenAI read this document for your company. Confirm the details before updating compliance.
                </DialogDescription>
            </DialogHeader>

            <div v-if="loading" class="py-8 text-center text-sm text-gray-500">Loading…</div>

            <div v-else-if="proposal" class="space-y-4">
                <p v-if="proposal.site?.name" class="text-xs uppercase tracking-wide text-gray-500">{{ proposal.site.name }}</p>
                <p v-if="proposal.summary" class="text-sm text-gray-600 dark:text-gray-400">{{ proposal.summary }}</p>

                <dl class="grid grid-cols-1 gap-2 text-sm">
                    <div v-for="row in fieldRows" :key="row.key" class="flex justify-between gap-4 border-b border-gray-100 dark:border-gray-800 py-2">
                        <dt class="text-gray-500 capitalize">{{ row.label }}</dt>
                        <dd class="font-medium text-right">{{ row.value }}</dd>
                    </div>
                </dl>

                <div v-if="suggestedTasks.length" class="text-sm">
                    <div class="font-medium mb-1">Suggested reminder tasks</div>
                    <ul class="list-disc pl-5 space-y-1 text-gray-600 dark:text-gray-400">
                        <li v-for="(task, index) in suggestedTasks" :key="index">
                            {{ (task as any).title }}
                            <span v-if="(task as any).due_date"> — {{ (task as any).due_date }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="projects.length">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reminder tasks board *</label>
                    <select
                        v-model="selectedProjectId"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                        required
                    >
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.key }} — {{ project.name }}
                        </option>
                    </select>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="submitting" @click="dismiss">Dismiss</Button>
                <Button :disabled="submitting || !proposal" @click="approve">Apply to site</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
