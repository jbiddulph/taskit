<?php

namespace App\Services;

use Carbon\Carbon;

class VoiceCommandParser
{
    public function parse(string $transcript): array
    {
        $text = trim($transcript);
        $lower = strtolower($text);

        if ($text === '') {
            return ['intent' => 'unknown', 'message' => 'No speech was detected.'];
        }

        if ($this->matchesDeleteIntent($lower)) {
            return array_merge(['intent' => 'delete'], $this->extractMatchAndFields($text));
        }

        if ($this->matchesCreateIntent($lower)) {
            return array_merge(['intent' => 'create'], $this->extractCreatePayload($text));
        }

        if ($this->matchesUpdateIntent($lower)) {
            return array_merge(['intent' => 'update'], $this->extractMatchAndFields($text));
        }

        if (preg_match('/^(create|add|new)\b/i', $text)) {
            return array_merge(['intent' => 'create'], $this->extractCreatePayload($text));
        }

        return array_merge(['intent' => 'create'], $this->extractCreatePayload($text));
    }

    private function matchesDeleteIntent(string $lower): bool
    {
        return (bool) preg_match('/\b(delete|remove|drop)\b/', $lower)
            && preg_match('/\b(task|todo)\b/', $lower);
    }

    private function matchesCreateIntent(string $lower): bool
    {
        return (bool) preg_match('/\b(create|add|make|new)\b/', $lower)
            && preg_match('/\b(task|todo|reminder)\b/', $lower);
    }

    private function matchesUpdateIntent(string $lower): bool
    {
        if (preg_match('/\b(update|change|edit|modify|move|set)\b/', $lower)) {
            return true;
        }

        return preg_match('/\b(priority|due date|location|column|status)\b/', $lower)
            && preg_match('/\b(task|todo)\b/', $lower);
    }

    private function extractMatchAndFields(string $text): array
    {
        $referenceId = $this->extractReferenceId($text);
        $title = $this->extractQuotedTitle($text) ?? $this->extractTitleAfterTask($text);

        return [
            'match' => array_filter([
                'reference_id' => $referenceId,
                'title' => $title,
            ]),
            'updates' => $this->extractUpdates($text),
        ];
    }

    private function extractCreatePayload(string $text): array
    {
        $title = $this->extractQuotedTitle($text);

        if (! $title) {
            if (preg_match('/\b(?:create|add|make|new)\s+(?:a\s+)?(?:task|todo|reminder)\s+(?:called|named|titled)?\s*(.+)$/i', $text, $m)) {
                $title = $this->cleanTitleCandidate($m[1]);
            } elseif (preg_match('/\b(?:task|todo)\s+(?:called|named|titled)?\s*(.+)$/i', $text, $m)) {
                $title = $this->cleanTitleCandidate($m[1]);
            } else {
                $title = $this->cleanTitleCandidate($text);
            }
        }

        return [
            'create' => array_filter([
                'title' => $title,
                ...$this->extractUpdates($text),
            ]),
        ];
    }

    private function extractReferenceId(string $text): ?string
    {
        if (preg_match('/\b([A-Za-z]{2,10})-(\d+)\b/', $text, $m)) {
            return strtoupper($m[1]).'-'.$m[2];
        }

        return null;
    }

    private function extractQuotedTitle(string $text): ?string
    {
        if (preg_match('/["\']([^"\']+)["\']/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractTitleAfterTask(string $text): ?string
    {
        $patterns = [
            '/\b(?:update|change|edit|modify|move|delete|remove)\s+(?:the\s+)?(?:task|todo)\s+(.+?)(?:\s+and\s+|\s+to\s+(?:the\s+)?(?:done|todo|progress|qa|testing)\b|$)/i',
            '/\b(?:task|todo)\s+(.+?)(?:\s+and\s+|\s+to\s+(?:the\s+)?(?:done|todo|progress|qa|testing)\b|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $candidate = $this->cleanTitleCandidate($m[1]);
                if ($candidate !== '' && ! preg_match('/^\d+$/', $candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function cleanTitleCandidate(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\b(please|the|a|an|called|named|titled)\b/i', ' ', $value) ?? $value;
        $value = preg_replace('/\b(and move it|and move|move it|to the|into the)\b.*$/i', '', $value) ?? $value;
        $value = preg_replace('/\b(priority|due date|location|status|column)\b.*$/i', '', $value) ?? $value;
        $value = preg_replace('/\b([A-Za-z]{2,10})-\d+\b/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B.,");
    }

    private function extractUpdates(string $text): array
    {
        $updates = [];
        $lower = strtolower($text);

        $status = $this->extractStatus($lower);
        if ($status) {
            $updates['status'] = $status;
        }

        $priority = $this->extractPriority($lower);
        if ($priority) {
            $updates['priority'] = $priority;
        }

        $dueDate = $this->extractDueDate($text);
        if ($dueDate) {
            $updates['due_date'] = $dueDate;
        }

        $locationQuery = $this->extractLocationQuery($text);
        if ($locationQuery) {
            $updates['location_query'] = $locationQuery;
        }

        return $updates;
    }

    private function extractStatus(string $lower): ?string
    {
        if (preg_match('/\b(in[\s-]?progress|progress column|working on|wip)\b/', $lower)) {
            return 'in-progress';
        }
        if (preg_match('/\b(qa|q\s*&\s*a|testing|qa[\s-]?testing|test column)\b/', $lower)) {
            return 'qa-testing';
        }
        if (preg_match('/\b(to[\s-]?do|todo column|backlog|not started)\b/', $lower)) {
            return 'todo';
        }
        if (preg_match('/\b(done|complete|completed|finished|done column)\b/', $lower)) {
            return 'done';
        }

        return null;
    }

    private function extractPriority(string $lower): ?string
    {
        if (preg_match('/\b(critical|urgent)\b/', $lower)) {
            return 'Critical';
        }
        if (preg_match('/\bhigh(?:\s+priority)?\b/', $lower)) {
            return 'High';
        }
        if (preg_match('/\blow(?:\s+priority)?\b/', $lower)) {
            return 'Low';
        }
        if (preg_match('/\bmedium(?:\s+priority)?\b/', $lower)) {
            return 'Medium';
        }

        return null;
    }

    private function extractDueDate(string $text): ?string
    {
        $lower = strtolower($text);
        $now = Carbon::now();

        if (preg_match('/\b(tomorrow)\b/', $lower)) {
            return $now->copy()->addDay()->toDateString();
        }
        if (preg_match('/\b(today)\b/', $lower)) {
            return $now->toDateString();
        }
        if (preg_match('/\bnext\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/', $lower, $m)) {
            return $now->copy()->next(constant('Carbon\Carbon::'.strtoupper($m[1])))->toDateString();
        }
        if (preg_match('/\b(this\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/', $lower, $m)) {
            $day = constant('Carbon\Carbon::'.strtoupper($m[2]));

            return $now->copy()->next($day)->toDateString();
        }
        if (preg_match('/\bnext\s+week\b/', $lower)) {
            return $now->copy()->addWeek()->toDateString();
        }
        if (preg_match('/\b(in\s+)?(\d{1,2})\s+days?\b/', $lower, $m)) {
            return $now->copy()->addDays((int) $m[2])->toDateString();
        }
        if (preg_match('/\bdue\s+(?:for\s+)?(?:on\s+)?(\d{4}-\d{2}-\d{2})\b/i', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractLocationQuery(string $text): ?string
    {
        $patterns = [
            '/\blocation\s+(?:of|for|at)\s+(?:this\s+task\s+)?(?:of\s+)?(.+?)(?:\.|$)/i',
            '/\badd\s+(?:a\s+)?location\s+(?:for\s+(?:this\s+)?task\s+)?(?:of\s+)?(.+?)(?:\.|$)/i',
            '/\bat\s+([A-Z][^.]+(?:Pier|Street|Road|Avenue|Lane|Park|Station|Centre|Center)[^.]*)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $location = trim($m[1]);
                $location = preg_replace('/\b(and make|and set|and change|priority|due)\b.*$/i', '', $location) ?? $location;

                return trim($location, " \t\n\r\0\x0B.,");
            }
        }

        return null;
    }
}
