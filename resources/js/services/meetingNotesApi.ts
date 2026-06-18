import axios from 'axios';

export interface MeetingNotesResponse {
    success: boolean;
    message: string;
    data?: {
        transcript: string;
    };
}

export interface SubmitMeetingNotesParams {
    audio: Blob;
    durationSeconds: number;
    stoppedReason: 'manual' | 'timeout';
    recordedAt: string;
    availableProjects?: { id: number; name: string }[];
}

export async function submitMeetingNotes(params: SubmitMeetingNotesParams): Promise<MeetingNotesResponse> {
    const formData = new FormData();
    formData.append('audio', params.audio, 'meeting-notes.webm');
    formData.append('duration_seconds', params.durationSeconds.toString());
    formData.append('stopped_reason', params.stoppedReason);
    formData.append('recorded_at', params.recordedAt);
    if (params.availableProjects?.length) {
        formData.append('available_projects', JSON.stringify(params.availableProjects));
    }

    const response = await axios.post<MeetingNotesResponse>('/meeting-notes', formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest',
        },
        withCredentials: true,
        timeout: 300000,
    });

    return response.data;
}
