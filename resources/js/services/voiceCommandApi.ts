import axios from 'axios';
import type { Todo } from '@/services/todoApi';

export interface VoiceCommandPendingDelete {
    todo_id: number;
    title: string;
    reference_id?: string;
}

export interface VoiceCommandResult {
    success: boolean;
    action?: 'created' | 'updated' | 'deleted' | 'confirm_delete';
    message?: string;
    todo?: Todo;
    pending_delete?: VoiceCommandPendingDelete;
    deleted_todo_id?: number;
    transcript?: string;
}

class VoiceCommandApiService {
    async process(transcript: string, projectId: number): Promise<VoiceCommandResult> {
        const response = await axios.post<VoiceCommandResult>('/voice-commands/process', {
            transcript,
            project_id: projectId,
        });
        return response.data;
    }

    async confirmDelete(todoId: number, projectId: number): Promise<VoiceCommandResult> {
        const response = await axios.post<VoiceCommandResult>('/voice-commands/process', {
            project_id: projectId,
            confirm_delete: true,
            delete_todo_id: todoId,
            transcript: '',
        });
        return response.data;
    }
}

export const voiceCommandApi = new VoiceCommandApiService();
