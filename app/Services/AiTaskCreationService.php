<?php

namespace App\Services;

use App\Models\OperationalObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiTaskCreationService
{
    /**
     * Parse a natural-language message into a structured task proposal.
     * Never writes to the database — callers must confirm before creating.
     */
    public function propose(User $user, string $message, string $context = 'task_creation'): array
    {
        $message = trim($message);

        if ($message === '') {
            return [
                'intent' => 'unknown',
                'message' => 'Please describe what needs doing.',
            ];
        }

        $structured = null;

        if (config('services.openai.api_key')) {
            $structured = $this->extractWithOpenAi($user, $message, $context);
        }

        if (! is_array($structured)) {
            $structured = $this->extractHeuristically($user, $message);
        }

        $structured = $this->enrichWithCompanyContext($user, $structured);

        return [
            'intent' => $structured['intent'] ?? 'create_task',
            'task' => $structured['task'] ?? null,
            'preview' => $this->buildPreview($structured['task'] ?? []),
            'confidence' => $structured['confidence'] ?? 'medium',
            'requires_confirmation' => true,
        ];
    }

    private function extractWithOpenAi(User $user, string $message, string $context): ?array
    {
        try {
            $today = now()->toDateString();
            $teamNames = $user->company_id
                ? User::query()->where('company_id', $user->company_id)->pluck('name')->take(30)->implode(', ')
                : $user->name;

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => <<<PROMPT
You extract structured ZapTask task proposals from natural language.
Today's date is {$today}.
Known team members: {$teamNames}.
Return JSON only:
{
  "intent": "create_task",
  "confidence": "high|medium|low",
  "task": {
    "title": "string",
    "assigned_to": "string|null",
    "asset_reference": "string|null",
    "due_date": "YYYY-MM-DD|null",
    "category": "string|null",
    "priority": "low|normal|high|urgent",
    "description": "string|null"
  }
}
Use relative date phrases relative to today. Do not invent assignees not in the team list.
PROMPT
                        ],
                        [
                            'role' => 'user',
                            'content' => "Context: {$context}\n\nMessage: {$message}",
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('AI task extraction OpenAI request failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $decoded = is_string($content) ? json_decode($content, true) : null;

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::warning('AI task extraction failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function extractHeuristically(User $user, string $message): array
    {
        $assignedTo = null;
        if (preg_match('/\b(?:remind|ask|tell|get)\s+([A-Z][a-z]+)\b/i', $message, $m)
            || preg_match('/\bassign(?:ed)?\s+to\s+([A-Z][a-z]+)\b/i', $message, $m)) {
            $assignedTo = ucfirst(strtolower($m[1]));
        }

        $assetReference = null;
        if (preg_match('/\b([A-Z]{2}\d{2}\s?[A-Z]{3})\b/', strtoupper($message), $m)) {
            $assetReference = preg_replace('/\s+/', ' ', trim($m[1]));
        }

        $dueDate = $this->parseNaturalDate($message);
        $priority = 'normal';
        if (preg_match('/\b(urgent|asap|critical)\b/i', $message)) {
            $priority = 'urgent';
        } elseif (preg_match('/\b(high priority|important)\b/i', $message)) {
            $priority = 'high';
        }

        $category = null;
        foreach (['insurance', 'mot', 'service', 'compliance', 'maintenance', 'inspection', 'renewal'] as $cat) {
            if (stripos($message, $cat) !== false) {
                $category = $cat;
                break;
            }
        }

        $title = $this->deriveTitle($message, $assignedTo);

        return [
            'intent' => 'create_task',
            'confidence' => 'medium',
            'task' => [
                'title' => $title,
                'assigned_to' => $assignedTo,
                'asset_reference' => $assetReference,
                'due_date' => $dueDate,
                'category' => $category,
                'priority' => $priority,
                'description' => $message,
            ],
        ];
    }

    private function deriveTitle(string $message, ?string $assignedTo): string
    {
        $title = $message;

        $title = preg_replace('/^(remind|ask|tell|get)\s+\w+\s+(to\s+)?/i', '', $title) ?? $title;
        $title = preg_replace('/\b(two weeks? before|a week before|(\d+)\s+days? before).*$/i', '', $title) ?? $title;
        $title = trim($title, " \t\n\r\0\x0B.,");

        if ($title === '' || strlen($title) > 120) {
            $title = Str::limit(ucfirst(trim($message)), 80, '…');
        } else {
            $title = Str::ucfirst($title);
        }

        return $title;
    }

    private function parseNaturalDate(string $message): ?string
    {
        $lower = strtolower($message);
        $today = Carbon::today();

        if (preg_match('/\btwo weeks? before\b.*\b(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)(?:\s+(\d{4}))?\b/i', $message, $m)) {
            $expiry = Carbon::parse(sprintf('%s %s %s', $m[1], $m[2], $m[3] ?? $today->year));
            if ($expiry->lt($today)) {
                $expiry->addYear();
            }

            return $expiry->copy()->subWeeks(2)->toDateString();
        }

        if (preg_match('/\b(\d+)\s+days? before\b.*\b(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)(?:\s+(\d{4}))?\b/i', $message, $m)) {
            $expiry = Carbon::parse(sprintf('%s %s %s', $m[2], $m[3], $m[4] ?? $today->year));
            if ($expiry->lt($today)) {
                $expiry->addYear();
            }

            return $expiry->copy()->subDays((int) $m[1])->toDateString();
        }

        if (preg_match('/\b(\d{1,2})\s+(january|february|march|april|may|june|july|august|september|october|november|december)(?:\s+(\d{4}))?\b/i', $message, $m)) {
            $date = Carbon::parse(sprintf('%s %s %s', $m[1], $m[2], $m[3] ?? $today->year));
            if ($date->lt($today)) {
                $date->addYear();
            }

            return $date->toDateString();
        }

        if (str_contains($lower, 'next friday')) {
            return $today->copy()->next(Carbon::FRIDAY)->toDateString();
        }
        if (str_contains($lower, 'tomorrow')) {
            return $today->copy()->addDay()->toDateString();
        }
        if (str_contains($lower, 'next week')) {
            return $today->copy()->addWeek()->toDateString();
        }

        return null;
    }

    private function enrichWithCompanyContext(User $user, array $structured): array
    {
        $task = $structured['task'] ?? [];

        if (! empty($task['assigned_to']) && $user->company_id) {
            $match = User::query()
                ->where('company_id', $user->company_id)
                ->where('name', 'ilike', $task['assigned_to'].'%')
                ->first();

            if ($match) {
                $task['assigned_to'] = $match->name;
                $task['assigned_to_user_id'] = $match->id;
            }
        }

        if (! empty($task['asset_reference']) && $user->company_id) {
            $ref = preg_replace('/\s+/', '', strtoupper($task['asset_reference']));
            $asset = OperationalObject::query()
                ->where('company_id', $user->company_id)
                ->whereRaw("REPLACE(UPPER(COALESCE(reference, '')), ' ', '') = ?", [$ref])
                ->first();

            if ($asset) {
                $task['asset_id'] = $asset->id;
                $task['asset_name'] = $asset->name;
            }
        }

        $structured['task'] = $task;

        return $structured;
    }

    private function buildPreview(array $task): array
    {
        return [
            'title' => $task['title'] ?? null,
            'assigned_to' => $task['assigned_to'] ?? null,
            'asset' => $task['asset_name'] ?? $task['asset_reference'] ?? null,
            'due_date' => $task['due_date'] ?? null,
            'category' => $task['category'] ?? null,
            'priority' => $task['priority'] ?? 'normal',
        ];
    }
}
