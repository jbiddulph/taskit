import { submitMeetingNotes } from '@/services/meetingNotesApi';
import { onUnmounted, ref } from 'vue';

const MAX_DURATION_SECONDS = 30 * 60;

export type RecordingState = 'idle' | 'recording' | 'processing';
export type StoppedReason = 'manual' | 'timeout';

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

export function useMeetingNotesRecorder() {
    const state = ref<RecordingState>('idle');
    const elapsedSeconds = ref(0);
    const errorMessage = ref<string | null>(null);

    let mediaRecorder: MediaRecorder | null = null;
    let mediaStream: MediaStream | null = null;
    let audioChunks: Blob[] = [];
    let timerInterval: number | null = null;
    let recordedAt: string | null = null;
    let stoppedReason: StoppedReason = 'manual';

    const notify = (type: 'success' | 'error' | 'warning', title: string, message: string) => {
        if ((window as any).$notify) {
            (window as any).$notify({ type, title, message });
        }
    };

    const clearTimer = () => {
        if (timerInterval !== null) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    };

    const cleanupStream = () => {
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }
    };

    const processRecording = async () => {
        state.value = 'processing';

        const mimeType = mediaRecorder?.mimeType || 'audio/webm';
        const audioBlob = new Blob(audioChunks, { type: mimeType });
        audioChunks = [];

        if (audioBlob.size === 0) {
            state.value = 'idle';
            errorMessage.value = 'No audio was captured.';
            notify('error', 'Recording Failed', errorMessage.value);
            return;
        }

        try {
            const response = await submitMeetingNotes({
                audio: audioBlob,
                durationSeconds: elapsedSeconds.value,
                stoppedReason,
                recordedAt: recordedAt || new Date().toISOString(),
            });

            if (response.success) {
                notify(
                    'success',
                    'Meeting Notes Sent',
                    'Your recording has been transcribed and sent for processing.'
                );
            } else {
                throw new Error(response.message || 'Failed to process meeting notes.');
            }
        } catch (error: any) {
            const message =
                error.response?.data?.message ||
                error.message ||
                'Failed to process meeting notes recording.';
            errorMessage.value = message;
            notify('error', 'Processing Failed', message);
        } finally {
            state.value = 'idle';
            elapsedSeconds.value = 0;
            recordedAt = null;
            stoppedReason = 'manual';
        }
    };

    const stopRecording = async (reason: StoppedReason = 'manual') => {
        if (state.value !== 'recording' || !mediaRecorder) {
            return;
        }

        stoppedReason = reason;
        clearTimer();

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
    };

    const startRecording = async () => {
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
    };

    const toggleRecording = async () => {
        if (state.value === 'recording') {
            await stopRecording('manual');
        } else if (state.value === 'idle') {
            await startRecording();
        }
    };

    const formattedElapsedTime = () => formatElapsedTime(elapsedSeconds.value);

    onUnmounted(() => {
        clearTimer();
        if (mediaRecorder && state.value === 'recording') {
            mediaRecorder.stop();
        }
        cleanupStream();
    });

    return {
        state,
        elapsedSeconds,
        errorMessage,
        toggleRecording,
        formattedElapsedTime,
        maxDurationMinutes: MAX_DURATION_SECONDS / 60,
    };
}
