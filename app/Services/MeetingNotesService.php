<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingNotesService
{
    public function sendToN8n(User $user, UploadedFile $audio, array $metadata): void
    {
        $webhookUrl = config('services.n8n.meeting_notes_webhook_url');

        if (empty($webhookUrl)) {
            throw new \RuntimeException('N8N meeting notes webhook URL is not configured.');
        }

        try {
            $response = Http::timeout(120)
                ->attach(
                    'audio',
                    file_get_contents($audio->getRealPath()),
                    $audio->getClientOriginalName() ?: 'meeting-notes.webm',
                    ['Content-Type' => $audio->getMimeType() ?: 'audio/webm']
                )
                ->post($webhookUrl, [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'company_id' => $user->company_id,
                    'duration_seconds' => $metadata['duration_seconds'] ?? null,
                    'stopped_reason' => $metadata['stopped_reason'] ?? 'manual',
                    'recorded_at' => $metadata['recorded_at'] ?? now()->toIso8601String(),
                ]);

            if (! $response->successful()) {
                Log::warning('N8N meeting notes webhook returned an error', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException('Failed to send meeting notes for processing.');
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Failed to send meeting notes to n8n webhook', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to send meeting notes for processing.');
        }
    }
}
