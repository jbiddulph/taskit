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
                    'available_projects' => $metadata['available_projects'] ?? null,
                ]);

            if (! $response->successful()) {
                Log::warning('N8N meeting notes webhook returned an error', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'webhook_url' => $webhookUrl,
                    'audio_bytes' => $audio->getSize(),
                ]);

                throw new \RuntimeException($this->webhookErrorMessage($response->status(), $webhookUrl));
            }

            Log::info('Meeting notes sent to N8N', [
                'user_id' => $user->id,
                'audio_bytes' => $audio->getSize(),
                'duration_seconds' => $metadata['duration_seconds'] ?? null,
                'n8n_status' => $response->status(),
                'n8n_body' => $response->body(),
            ]);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Failed to send meeting notes to n8n webhook', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'webhook_url' => $webhookUrl,
            ]);

            throw new \RuntimeException('Failed to reach the N8N meeting notes webhook. Check N8N_MEETING_NOTES_WEBHOOK_URL and that n8n is reachable.');
        }
    }

    private function webhookErrorMessage(int $status, string $webhookUrl): string
    {
        if ($status === 404) {
            if (str_contains($webhookUrl, '/webhook-test/')) {
                return 'N8N webhook not found. You are using a test URL (/webhook-test/) — activate the workflow in N8N and set N8N_MEETING_NOTES_WEBHOOK_URL to the Production URL (/webhook/meeting-notes).';
            }

            return 'N8N meeting notes webhook not found (404). Import the workflow in N8N, activate it, and set N8N_MEETING_NOTES_WEBHOOK_URL to the Production URL from the Webhook node.';
        }

        return "Failed to send meeting notes for processing (N8N returned HTTP {$status}).";
    }
}
