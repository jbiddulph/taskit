import axios from 'axios';

export interface MeetingActionItem {
    title: string;
    assignee?: string | null;
    priority?: 'Low' | 'Medium' | 'High' | 'Critical';
    status?: 'todo' | 'in-progress' | 'qa-testing' | 'done';
    due_date?: string | null;
    project_name?: string | null;
    notes?: string | null;
}

export interface MeetingNoteProposal {
    id: number;
    user_id: number;
    company_id?: number | null;
    status: 'pending' | 'approved' | 'dismissed';
    meeting_summary?: string | null;
    transcript?: string | null;
    action_items: MeetingActionItem[];
    metadata?: {
        duration_seconds?: number;
        stopped_reason?: string;
        recorded_at?: string;
        key_decisions?: string[];
        follow_ups?: string[];
        suggested_project_name?: string | null;
    } | null;
    project_id?: number | null;
    created_at: string;
}

export interface ReviewActionItem {
    index: number;
    approved: boolean;
    title: string;
    priority: string;
    status: string;
    due_date: string;
    assignee: string;
    description: string;
}

class MeetingNoteProposalApiService {
    async getPending(): Promise<MeetingNoteProposal[]> {
        const response = await axios.get('/meeting-notes/proposals/pending');
        return response.data.data;
    }

    async getProposal(id: number): Promise<MeetingNoteProposal> {
        const response = await axios.get(`/meeting-notes/proposals/${id}`);
        return response.data.data;
    }

    async approve(proposalId: number, projectId: number, items: ReviewActionItem[]): Promise<{ created_count: number }> {
        const response = await axios.post(`/meeting-notes/proposals/${proposalId}/approve`, {
            project_id: projectId,
            items,
        });
        return response.data.data;
    }

    async dismiss(proposalId: number): Promise<void> {
        await axios.post(`/meeting-notes/proposals/${proposalId}/dismiss`);
    }
}

export const meetingNoteProposalApi = new MeetingNoteProposalApiService();
