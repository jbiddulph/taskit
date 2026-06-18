<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingNotesService
{
    public function transcribe(UploadedFile $audio): string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(300)
            ->attach(
                'file',
                file_get_contents($audio->getRealPath()),
                $audio->getClientOriginalName() ?: 'recording.webm',
                ['Content-Type' => $audio->getMimeType() ?: 'audio/webm']
            )
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => config('services.openai.whisper_model', 'whisper-1'),
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI Whisper transcription failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Failed to transcribe audio recording.');
        }

        $transcript = trim($response->json('text', ''));

        if ($transcript === '') {
            throw new \RuntimeException('No speech was detected in the recording.');
        }

        return $transcript;
    }

    public function sendToN8n(User $user, string $transcript, array $metadata): void
    {
        $webhookUrl = config('services.n8n.meeting_notes_webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('N8N meeting notes webhook URL is not configured; skipping webhook delivery.');

            return;
        }

        $payload = [
            'transcript' => $transcript,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'company_id' => $user->company_id,
            'duration_seconds' => $metadata['duration_seconds'] ?? null,
            'stopped_reason' => $metadata['stopped_reason'] ?? 'manual',
            'recorded_at' => $metadata['recorded_at'] ?? now()->toIso8601String(),
        ];

        try {
            Http::timeout(30)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify n8n webhook for meeting notes', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
