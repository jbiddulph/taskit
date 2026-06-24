import { meetingNoteProposalApi } from '@/services/meetingNoteProposalApi';
import { submitMeetingNotes } from '@/services/meetingNotesApi';
import { todoApi } from '@/services/todoApi';
import { voiceCommandApi, type VoiceCommandPendingDelete } from '@/services/voiceCommandApi';
import { getActiveProjectId } from '@/composables/useDashboardProjectContext';
import { ref } from 'vue';

const MAX_DURATION_SECONDS = 30 * 60;
const COMMAND_MAX_DURATION_SECONDS = 90;
const POLL_INTERVAL_MS = 3000;
const POLL_TIMEOUT_MS = 5 * 60 * 1000;

export type RecordingState = 'idle' | 'recording' | 'uploading' | 'generating';
export type StoppedReason = 'manual' | 'timeout';

const state = ref<RecordingState>('idle');
const elapsedSeconds = ref(0);
const errorMessage = ref<string | null>(null);
const recordingTemplate = ref<string | null>(null);
const pendingDelete = ref<VoiceCommandPendingDelete | null>(null);

let mediaRecorder: MediaRecorder | null = null;
let mediaStream: MediaStream | null = null;
let audioChunks: Blob[] = [];
let timerInterval: number | null = null;
let recordedAt: string | null = null;
let stoppedReason: StoppedReason = 'manual';
let pollTimeout: number | null = null;
let pollInterval: number | null = null;
let speechRecognition: any = null;
let speechTranscript = '';

function getPreferredMimeType(): string {
    const types = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/ogg;codecs=opus',
        'audio/mp4',
    ];

    for (const type of types) {
        if (MediaRecorder.isTypeSupported(type)) {
            return type;
        }
    }

    return '';
}

function formatElapsedTime(seconds: number): string {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function notify(type: 'success' | 'error' | 'warning' | 'info', title: string, message: string) {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
}

function getSpeechRecognition(): any {
    const win = window as any;
    return win.SpeechRecognition ?? win.webkitSpeechRecognition ?? null;
}

function looksLikeTaskCommand(text: string): boolean {
    const lower = text.toLowerCase().trim();
    if (!lower) {
        return false;
    }

    if (/\b(delete|remove|drop)\b/.test(lower) && /\b(task|todo)\b/.test(lower)) {
        return true;
    }

    if (/\b(create|add|make|new)\b/.test(lower) && /\b(task|todo|reminder)\b/.test(lower)) {
        return true;
    }

    if (/\b(update|change|edit|modify|move|set)\b/.test(lower)) {
        return true;
    }

    if (/\b(priority|due date|location|column|status)\b/.test(lower) && /\b(task|todo)\b/.test(lower)) {
        return true;
    }

    return /\b[a-z]{2,6}-\d+\b/i.test(lower)
        && /\b(change|move|delete|update|set|priority|due)\b/i.test(lower);
}

function dispatchVoiceResult(detail: Record<string, unknown>) {
    window.dispatchEvent(new CustomEvent('voiceCommandApplied', { detail }));
}

function stopSpeechRecognition() {
    if (!speechRecognition) {
        return;
    }

    try {
        speechRecognition.stop();
    } catch {
        // ignore
    }
    speechRecognition = null;
}

function startSpeechRecognition() {
    const SpeechRecognitionCtor = getSpeechRecognition();
    if (!SpeechRecognitionCtor) {
        return;
    }

    speechTranscript = '';
    speechRecognition = new SpeechRecognitionCtor();
    speechRecognition.continuous = true;
    speechRecognition.interimResults = true;
    speechRecognition.lang = 'en-GB';

    speechRecognition.onresult = (event: any) => {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                speechTranscript += `${event.results[i][0].transcript} `;
            }
        }
    };

    speechRecognition.onerror = () => {
        // Meeting notes still work from audio when speech recognition fails.
    };

    try {
        speechRecognition.start();
    } catch {
        speechRecognition = null;
    }
}

async function tryVoiceCommand(transcript: string, durationSeconds: number): Promise<boolean> {
    const text = transcript.trim();
    if (!text || durationSeconds > COMMAND_MAX_DURATION_SECONDS || !looksLikeTaskCommand(text)) {
        return false;
    }

    const projectId = getActiveProjectId();
    if (!projectId) {
        notify('warning', 'Voice command', 'Select a project on your board, then try again.');
        return true;
    }

    state.value = 'uploading';

    try {
        const result = await voiceCommandApi.process(text, projectId);

        if (!result.success) {
            notify('error', 'Voice command', result.message || 'Could not process that command.');
            return true;
        }

        if (result.action === 'confirm_delete' && result.pending_delete) {
            pendingDelete.value = result.pending_delete;
            return true;
        }

        if (result.action === 'created' || result.action === 'updated') {
            dispatchVoiceResult({ todo: result.todo, action: result.action });
            notify('success', 'Voice command', result.message || 'Task updated.');
            return true;
        }

        if (result.action === 'deleted') {
            dispatchVoiceResult({ deletedTodoId: result.deleted_todo_id, action: 'deleted' });
            notify('success', 'Voice command', result.message || 'Task deleted.');
            return true;
        }

        notify('error', 'Voice command', result.message || 'Could not process that command.');
        return true;
    } catch (error: any) {
        const message = error.response?.data?.message || error.message || 'Failed to process voice command.';
        notify('error', 'Voice command', message);
        return true;
    }
}

function clearTimer() {
    if (timerInterval !== null) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function cleanupStream() {
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
}

function clearPollers() {
    if (pollTimeout !== null) {
        clearTimeout(pollTimeout);
        pollTimeout = null;
    }
    if (pollInterval !== null) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function waitForProposal(submittedRecordedAt: string): Promise<number | null> {
    return new Promise((resolve) => {
        const startedAt = Date.now();
        let resolved = false;

        const finish = (proposalId: number | null) => {
            if (resolved) {
                return;
            }
            resolved = true;
            clearPollers();
            resolve(proposalId);
        };

        const check = async () => {
            try {
                const pending = await meetingNoteProposalApi.getPending();
                const match = pending.find(
                    (proposal) => proposal.metadata?.recorded_at === submittedRecordedAt
                );

                if (match) {
                    finish(match.id);
                    return;
                }

                if (Date.now() - startedAt >= POLL_TIMEOUT_MS) {
                    finish(null);
                }
            } catch {
                if (Date.now() - startedAt >= POLL_TIMEOUT_MS) {
                    finish(null);
                }
            }
        };

        void check();
        pollInterval = window.setInterval(() => void check(), POLL_INTERVAL_MS);
        pollTimeout = window.setTimeout(() => finish(null), POLL_TIMEOUT_MS);
    });
}

async function processRecording() {
    stopSpeechRecognition();

    const submittedDuration = elapsedSeconds.value;
    const transcript = speechTranscript.trim();
    speechTranscript = '';

    const handledAsCommand = await tryVoiceCommand(transcript, submittedDuration);
    if (handledAsCommand) {
        state.value = 'idle';
        elapsedSeconds.value = 0;
        recordedAt = null;
        stoppedReason = 'manual';
        audioChunks = [];
        return;
    }

    state.value = 'uploading';

    const mimeType = mediaRecorder?.mimeType || 'audio/webm';
    const audioBlob = new Blob(audioChunks, { type: mimeType });
    audioChunks = [];

    const submittedRecordedAt = recordedAt || new Date().toISOString();

    if (audioBlob.size === 0) {
        state.value = 'idle';
        errorMessage.value = 'No audio was captured.';
        notify('error', 'Recording Failed', errorMessage.value);
        return;
    }

    try {
        const projects = await todoApi.getProjects();
        const availableProjects = projects.map((project) => ({
            id: project.id,
            name: project.name,
        }));

        const response = await submitMeetingNotes({
            audio: audioBlob,
            durationSeconds: submittedDuration,
            stoppedReason,
            recordedAt: submittedRecordedAt,
            availableProjects,
            recordingTemplate: recordingTemplate.value,
        });

        if (!response.success) {
            throw new Error(response.message || 'Failed to process meeting notes.');
        }

        state.value = 'generating';

        const proposalId = await waitForProposal(submittedRecordedAt);
        clearPollers();

        if (proposalId) {
            window.dispatchEvent(new CustomEvent('openMeetingNoteApproval', {
                detail: { proposalId },
            }));
        } else {
            notify(
                'warning',
                'Still Processing',
                'Meeting notes are taking longer than expected. Check your notifications shortly.'
            );
        }
    } catch (error: any) {
        const message =
            error.response?.data?.message ||
            error.message ||
            'Failed to process meeting notes recording.';
        errorMessage.value = message;
        notify('error', 'Processing Failed', message);
    } finally {
        clearPollers();
        state.value = 'idle';
        elapsedSeconds.value = 0;
        recordedAt = null;
        stoppedReason = 'manual';
    }
}

async function stopRecording(reason: StoppedReason = 'manual') {
    if (state.value !== 'recording' || !mediaRecorder) {
        return;
    }

    stoppedReason = reason;
    clearTimer();
    stopSpeechRecognition();

    await new Promise<void>((resolve) => {
        if (!mediaRecorder) {
            resolve();
            return;
        }

        mediaRecorder.onstop = () => resolve();
        mediaRecorder.stop();
    });

    cleanupStream();
    mediaRecorder = null;
    await processRecording();
}

async function startRecording() {
    if (state.value !== 'idle') {
        return;
    }

    errorMessage.value = null;

    if (!window.isSecureContext) {
        notify(
            'error',
            'Secure Context Required',
            'Meeting notes recording requires HTTPS or localhost.'
        );
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
        notify('error', 'Browser Not Supported', 'Your browser does not support audio recording.');
        return;
    }

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const mimeType = getPreferredMimeType();

        mediaRecorder = mimeType
            ? new MediaRecorder(mediaStream, { mimeType })
            : new MediaRecorder(mediaStream);

        audioChunks = [];
        recordedAt = new Date().toISOString();
        elapsedSeconds.value = 0;
        stoppedReason = 'manual';

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onerror = () => {
            errorMessage.value = 'An error occurred while recording.';
            notify('error', 'Recording Error', errorMessage.value);
            clearTimer();
            cleanupStream();
            mediaRecorder = null;
            state.value = 'idle';
            elapsedSeconds.value = 0;
        };

        mediaRecorder.start(1000);
        state.value = 'recording';
        startSpeechRecognition();

        timerInterval = window.setInterval(() => {
            elapsedSeconds.value++;

            if (elapsedSeconds.value >= MAX_DURATION_SECONDS) {
                stopRecording('timeout');
            }
        }, 1000);
    } catch (error: any) {
        cleanupStream();
        mediaRecorder = null;
        state.value = 'idle';

        let message = 'Failed to start recording. Please try again.';
        if (error.name === 'NotAllowedError') {
            message = 'Microphone access was denied. Please allow microphone access and try again.';
        } else if (error.name === 'NotFoundError') {
            message = 'No microphone found. Please connect a microphone and try again.';
        }

        errorMessage.value = message;
        notify('error', 'Recording Failed', message);
    }
}

async function toggleRecording() {
    if (state.value === 'recording') {
        await stopRecording('manual');
    } else if (state.value === 'idle') {
        await startRecording();
    }
}

function isBusy() {
    return state.value === 'uploading' || state.value === 'generating';
}

function formattedElapsedTime() {
    return formatElapsedTime(elapsedSeconds.value);
}

function setRecordingTemplate(templateId: string | null) {
    recordingTemplate.value = templateId;
}

async function confirmPendingDelete() {
    if (!pendingDelete.value) {
        return;
    }

    const projectId = getActiveProjectId();
    if (!projectId) {
        notify('warning', 'Voice command', 'Select a project on your board, then confirm deletion.');
        return;
    }

    const todoId = pendingDelete.value.todo_id;
    state.value = 'uploading';

    try {
        const result = await voiceCommandApi.confirmDelete(todoId, projectId);
        pendingDelete.value = null;

        if (result.success) {
            dispatchVoiceResult({ deletedTodoId: result.deleted_todo_id ?? todoId, action: 'deleted' });
            notify('success', 'Voice command', result.message || 'Task deleted.');
        } else {
            notify('error', 'Voice command', result.message || 'Could not delete task.');
        }
    } catch (error: any) {
        notify('error', 'Voice command', error.response?.data?.message || error.message);
    } finally {
        state.value = 'idle';
    }
}

function dismissPendingDelete() {
    pendingDelete.value = null;
    notify('info', 'Voice command', 'Deletion cancelled.');
}

export function useMeetingNotesRecorder() {
    return {
        state,
        elapsedSeconds,
        errorMessage,
        recordingTemplate,
        pendingDelete,
        setRecordingTemplate,
        toggleRecording,
        confirmPendingDelete,
        dismissPendingDelete,
        formattedElapsedTime,
        maxDurationMinutes: MAX_DURATION_SECONDS / 60,
        isBusy,
    };
}
