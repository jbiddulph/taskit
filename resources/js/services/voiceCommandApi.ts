import axios from 'axios';
import type { Todo } from '@/services/todoApi';

export interface VoiceCommandPendingDelete {
    todo_id: number;
    title: string;
    reference_id?: string;
}

export interface VoiceCommandCandidate {
    todo_id: number;
    title: string;
    reference_id?: string;
    project_name?: string;
}

export interface VoiceCommandPendingTask {
    intent: 'update' | 'delete' | 'move_board';
    payload: Record<string, unknown>;
    candidates: VoiceCommandCandidate[];
}

export interface TodaySummaryData {
    overdue: Array<Record<string, unknown>>;
    due_today: Array<Record<string, unknown>>;
    viewings: Array<Record<string, unknown>>;
    route_stops: Array<Record<string, unknown>>;
    pending_check_ins: Array<Record<string, unknown>>;
    spoken_summary: string;
}

export interface VoiceCommandResult {
    success: boolean;
    action?: 'created' | 'updated' | 'deleted' | 'confirm_delete' | 'confirm_task' | 'summary';
    message?: string;
    todo?: Todo;
    pending_delete?: VoiceCommandPendingDelete;
    pending_task?: VoiceCommandPendingTask;
    deleted_todo_id?: number;
    summary?: TodaySummaryData;
    transcript?: string;
}

class VoiceCommandApiService {
    async process(transcript: string, projectId?: number | null): Promise<VoiceCommandResult> {
        const response = await axios.post<VoiceCommandResult>('/voice-commands/process', {
            transcript,
            ...(projectId ? { project_id: projectId } : {}),
        });
        return response.data;
    }

    async confirmDelete(todoId: number, projectId?: number | null): Promise<VoiceCommandResult> {
        const response = await axios.post<VoiceCommandResult>('/voice-commands/process', {
            confirm_delete: true,
            delete_todo_id: todoId,
            transcript: '',
            ...(projectId ? { project_id: projectId } : {}),
        });
        return response.data;
    }

    async confirmTask(
        todoId: number,
        intent: VoiceCommandPendingTask['intent'],
        payload: Record<string, unknown>,
        projectId?: number | null,
    ): Promise<VoiceCommandResult> {
        const response = await axios.post<VoiceCommandResult>('/voice-commands/process', {
            confirm_task_id: todoId,
            confirmed_intent: intent,
            confirmed_payload: payload,
            transcript: '',
            ...(projectId ? { project_id: projectId } : {}),
        });
        return response.data;
    }
}

export const voiceCommandApi = new VoiceCommandApiService();
