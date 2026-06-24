import { voiceCommandApi, type VoiceCommandPendingDelete } from '@/services/voiceCommandApi';
import { ref } from 'vue';

const MAX_DURATION_SECONDS = 60;

export type VoiceAssistantState = 'idle' | 'listening' | 'processing';

const state = ref<VoiceAssistantState>('idle');
const elapsedSeconds = ref(0);
const interimTranscript = ref('');
const pendingDelete = ref<VoiceCommandPendingDelete | null>(null);

let recognition: any = null;
let timerInterval: number | null = null;
let accumulatedTranscript = '';

function notify(type: 'success' | 'error' | 'warning' | 'info', title: string, message: string) {
    if ((window as any).$notify) {
        (window as any).$notify({ type, title, message });
    }
}

function clearTimer() {
    if (timerInterval !== null) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function getSpeechRecognition(): any {
    const win = window as any;
    return win.SpeechRecognition ?? win.webkitSpeechRecognition ?? null;
}

function dispatchVoiceResult(detail: Record<string, unknown>) {
    window.dispatchEvent(new CustomEvent('voiceCommandApplied', { detail }));
}

async function processTranscript(transcript: string, projectId: number | null) {
    const text = transcript.trim();
    if (!text) {
        notify('warning', 'Voice Assistant', 'No speech was detected. Please try again.');
        return;
    }

    if (!projectId) {
        notify('warning', 'Voice Assistant', 'Select a project first, then try your voice command again.');
        return;
    }

    state.value = 'processing';

    try {
        const result = await voiceCommandApi.process(text, projectId);

        if (!result.success) {
            notify('error', 'Voice Assistant', result.message || 'Could not process that command.');
            return;
        }

        if (result.action === 'confirm_delete' && result.pending_delete) {
            pendingDelete.value = result.pending_delete;
            return;
        }

        if (result.action === 'created' || result.action === 'updated') {
            dispatchVoiceResult({ todo: result.todo, action: result.action });
            notify('success', 'Voice Assistant', result.message || 'Task updated.');
            return;
        }

        if (result.action === 'deleted') {
            dispatchVoiceResult({ deletedTodoId: result.deleted_todo_id, action: 'deleted' });
            notify('success', 'Voice Assistant', result.message || 'Task deleted.');
        }
    } catch (error: any) {
        const message = error.response?.data?.message || error.message || 'Failed to process voice command.';
        notify('error', 'Voice Assistant', message);
    } finally {
        state.value = 'idle';
        interimTranscript.value = '';
    }
}

async function stopListening(projectId: number | null) {
    if (!recognition || state.value !== 'listening') {
        return;
    }

    clearTimer();
    state.value = 'processing';
    recognition.stop();
    recognition = null;

    await processTranscript(accumulatedTranscript, projectId);
    accumulatedTranscript = '';
    elapsedSeconds.value = 0;
}

function startListening(projectId: number | null) {
    if (state.value !== 'idle') {
        return;
    }

    if (!window.isSecureContext) {
        notify('error', 'Secure Context Required', 'Voice commands require HTTPS or localhost.');
        return;
    }

    const SpeechRecognitionCtor = getSpeechRecognition();
    if (!SpeechRecognitionCtor) {
        notify('error', 'Browser Not Supported', 'Voice commands need Chrome, Edge, or Safari.');
        return;
    }

    if (!projectId) {
        notify('warning', 'Voice Assistant', 'Select a project before using voice commands.');
        return;
    }

    accumulatedTranscript = '';
    interimTranscript.value = '';
    elapsedSeconds.value = 0;

    recognition = new SpeechRecognitionCtor();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-GB';

    recognition.onresult = (event: any) => {
        let interim = '';
        let finalText = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const part = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                finalText += part + ' ';
            } else {
                interim += part;
            }
        }

        if (finalText) {
            accumulatedTranscript += finalText;
        }
        interimTranscript.value = (accumulatedTranscript + interim).trim();
    };

    recognition.onerror = (event: any) => {
        if (event.error === 'aborted' || event.error === 'no-speech') {
            return;
        }

        notify('error', 'Voice Assistant', event.error === 'not-allowed'
            ? 'Microphone access was denied. Allow microphone access and try again.'
            : 'Speech recognition failed. Please try again.');
        clearTimer();
        state.value = 'idle';
        recognition = null;
    };

    recognition.onend = () => {
        if (state.value === 'listening') {
            void stopListening(projectId);
        }
    };

    try {
        recognition.start();
        state.value = 'listening';

        timerInterval = window.setInterval(() => {
            elapsedSeconds.value++;
            if (elapsedSeconds.value >= MAX_DURATION_SECONDS) {
                void stopListening(projectId);
            }
        }, 1000);
    } catch {
        notify('error', 'Voice Assistant', 'Could not start listening. Please try again.');
        state.value = 'idle';
        recognition = null;
    }
}

async function toggleListening(projectId: number | null) {
    if (state.value === 'listening') {
        await stopListening(projectId);
    } else if (state.value === 'idle') {
        startListening(projectId);
    }
}

async function confirmPendingDelete(projectId: number | null) {
    if (!pendingDelete.value || !projectId) {
        return;
    }

    state.value = 'processing';
    const todoId = pendingDelete.value.todo_id;

    try {
        const result = await voiceCommandApi.confirmDelete(todoId, projectId);
        pendingDelete.value = null;

        if (result.success) {
            dispatchVoiceResult({ deletedTodoId: result.deleted_todo_id ?? todoId, action: 'deleted' });
            notify('success', 'Voice Assistant', result.message || 'Task deleted.');
        } else {
            notify('error', 'Voice Assistant', result.message || 'Could not delete task.');
        }
    } catch (error: any) {
        notify('error', 'Voice Assistant', error.response?.data?.message || error.message);
    } finally {
        state.value = 'idle';
    }
}

function dismissPendingDelete() {
    pendingDelete.value = null;
    notify('info', 'Voice Assistant', 'Deletion cancelled.');
}

function formattedElapsedTime() {
    const mins = Math.floor(elapsedSeconds.value / 60);
    const secs = elapsedSeconds.value % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

export function useVoiceAssistant() {
    return {
        state,
        elapsedSeconds,
        interimTranscript,
        pendingDelete,
        toggleListening,
        confirmPendingDelete,
        dismissPendingDelete,
        formattedElapsedTime,
        maxDurationSeconds: MAX_DURATION_SECONDS,
        isBusy: () => state.value === 'processing',
    };
}
