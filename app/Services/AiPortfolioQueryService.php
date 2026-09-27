<?php

namespace App\Services;

use App\Models\ComplianceRequirement;
use App\Models\DocumentAiUsage;
use App\Models\OperationalDocument;
use App\Models\OperationalObject;
use App\Models\User;
use App\Support\CertificateTypes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Answers natural-language questions about a company's property compliance portfolio.
 * Grounded in sites, certificate requirements, and uploaded documents — never invents data.
 */
class AiPortfolioQueryService
{
    /**
     * @return array{
     *   intent: string,
     *   answer: string,
     *   matches: array<int, array<string, mixed>>,
     *   suggested_tasks: array<int, array<string, mixed>>,
     *   confidence: string,
     *   requires_confirmation: bool,
     *   sources: array<int, string>
     * }
     */
    public function ask(User $user, string $message): array
    {
        $message = trim($message);

        if ($message === '' || ! $user->company_id) {
            return [
                'intent' => 'portfolio_answer',
                'answer' => 'Ask something about your portfolio — for example, which gas certificates expire in the next 60 days.',
                'matches' => [],
                'suggested_tasks' => [],
                'confidence' => 'low',
                'requires_confirmation' => false,
                'sources' => [],
            ];
        }

        $company = $user->company;
        if ($company) {
            $company->consumeDocumentAi(
                DocumentAiUsage::KIND_PORTFOLIO_ASK,
                $user->id,
                null,
                ['message_preview' => Str::limit($message, 120)],
            );
        }

        $snapshot = $this->buildSnapshot((int) $user->company_id);
        $heuristic = $this->answerHeuristically($message, $snapshot);

        if ($heuristic !== null) {
            return $heuristic;
        }

        if (config('services.openai.api_key')) {
            $ai = $this->answerWithOpenAi($message, $snapshot);
            if ($ai !== null) {
                return $ai;
            }
        }

        return $this->fallbackAnswer($snapshot);
    }

    /**
     * Build attention chips for the Compliance dashboard.
     *
     * @return array<int, array{severity: string, count: int, label: string, type: string|null, days: int|null, status: string|null}>
     */
    public function attentionInsights(int $companyId): array
    {
        $requirements = $this->loadRequirements($companyId);
        $insights = [];

        foreach (['gas_safety' => 14, 'eicr' => 60, 'insurance' => 30, 'epc' => 60, 'boiler_service' => 30] as $type => $days) {
            $count = $requirements
                ->filter(fn (array $row) => $row['requirement_type'] === $type)
                ->filter(function (array $row) use ($days) {
                    if (! $row['next_due_date']) {
                        return false;
                    }
                    $due = Carbon::parse($row['next_due_date'])->startOfDay();
                    $today = now()->startOfDay();

                    return $due->lte($today->copy()->addDays($days));
                })
                ->count();

            if ($count > 0) {
                $short = CertificateTypes::short($type);
                $severity = $days <= 14 ? 'critical' : ($days <= 30 ? 'high' : 'medium');
                $insights[] = [
                    'severity' => $severity,
                    'count' => $count,
                    'label' => "{$short} ".($count === 1 ? 'needs' : 'need')." attention within {$days} days",
                    'type' => $type,
                    'days' => $days,
                    'status' => null,
                ];
            }
        }

        // Properties with no document for core types
        $sitesMissingCore = $this->sitesMissingCoreCertificates($companyId);
        if ($sitesMissingCore > 0) {
            $insights[] = [
                'severity' => 'critical',
                'count' => $sitesMissingCore,
                'label' => $sitesMissingCore === 1
                    ? 'property has missing core certificates'
                    : 'properties have missing core certificates',
                'type' => null,
                'days' => null,
                'status' => 'missing',
            ];
        }

        $overdue = $requirements->where('status', ComplianceRequirement::STATUS_OVERDUE)->count();
        if ($overdue > 0 && ! collect($insights)->contains(fn ($i) => str_contains($i['label'], 'overdue'))) {
            $insights[] = [
                'severity' => 'critical',
                'count' => $overdue,
                'label' => $overdue === 1 ? 'certificate is overdue' : 'certificates are overdue',
                'type' => null,
                'days' => null,
                'status' => ComplianceRequirement::STATUS_OVERDUE,
            ];
        }

        // Prefer insight chips; keep list short
        return array_slice($insights, 0, 6);
    }

    /**
     * @return array{sites: array<int, array<string, mixed>>, requirements: array<int, array<string, mixed>>, documents: array<int, array<string, mixed>>}
     */
    private function buildSnapshot(int $companyId): array
    {
        $sites = OperationalObject::forCompany($companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address_line_1', 'city', 'postal_code', 'type'])
            ->map(fn (OperationalObject $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'address' => collect([$site->address_line_1, $site->city, $site->postal_code])->filter()->implode(', '),
                'type' => $site->type,
            ])
            ->values()
            ->all();

        $requirements = $this->loadRequirements($companyId)->values()->all();

        $documents = OperationalDocument::forCompany($companyId)
            ->where('status', '!=', OperationalDocument::STATUS_ARCHIVED)
            ->with('operationalObject:id,name')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(fn (OperationalDocument $doc) => [
                'id' => $doc->id,
                'title' => $doc->title,
                'document_type' => $doc->document_type,
                'type_label' => $doc->document_type ? CertificateTypes::label($doc->document_type) : 'Document',
                'site_id' => $doc->operational_object_id,
                'site_name' => $doc->operationalObject?->name,
                'expires_at' => $doc->expires_at?->toDateString(),
                'issued_or_uploaded' => $doc->created_at?->toDateString(),
                'last_completed_hint' => data_get($doc->extracted_data, 'issue_date')
                    ?? data_get($doc->extracted_data, 'service_date')
                    ?? $doc->created_at?->toDateString(),
                'findings' => data_get($doc->extracted_data, 'findings'),
                'address' => data_get($doc->extracted_data, 'address'),
                'text_excerpt' => Str::limit((string) ($doc->extracted_text ?? ''), 1200, ''),
            ])
            ->values()
            ->all();

        return compact('sites', 'requirements', 'documents');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function loadRequirements(int $companyId): Collection
    {
        $requirements = ComplianceRequirement::forCompany($companyId)
            ->with(['operationalObject:id,name', 'documents:id,compliance_requirement_id'])
            ->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();
        }

        return $requirements
            ->filter(fn (ComplianceRequirement $req) => $req->isActiveOnSitePage()
                || in_array($req->requirement_type, ['gas_safety', 'eicr', 'epc', 'insurance', 'boiler_service'], true))
            ->map(fn (ComplianceRequirement $req) => [
                'id' => $req->id,
                'label' => $req->label,
                'requirement_type' => $req->requirement_type,
                'type_label' => CertificateTypes::label($req->requirement_type),
                'status' => $req->status,
                'next_due_date' => $req->next_due_date?->toDateString(),
                'last_completed_at' => $req->last_completed_at?->toDateString(),
                'provider' => $req->provider,
                'site_id' => $req->operational_object_id,
                'site_name' => $req->operationalObject?->name,
                'has_document' => $req->documents->isNotEmpty(),
                'has_open_task' => $req->hasOpenTask(),
            ]);
    }

    private function sitesMissingCoreCertificates(int $companyId): int
    {
        $coreTypes = ['gas_safety', 'eicr', 'epc', 'insurance'];
        $sites = OperationalObject::forCompany($companyId)->where('is_active', true)->pluck('id');
        if ($sites->isEmpty()) {
            return 0;
        }

        $missingCount = 0;
        foreach ($sites as $siteId) {
            $present = ComplianceRequirement::forCompany($companyId)
                ->where('operational_object_id', $siteId)
                ->whereIn('requirement_type', $coreTypes)
                ->where(function ($q) {
                    $q->whereNotNull('next_due_date')
                        ->orWhereHas('documents');
                })
                ->pluck('requirement_type')
                ->unique();

            if ($present->count() < count($coreTypes)) {
                $missingCount++;
            }
        }

        return $missingCount;
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>|null
     */
    private function answerHeuristically(string $message, array $snapshot): ?array
    {
        $lower = strtolower($message);
        $days = $this->extractDays($lower) ?? 60;
        $type = $this->detectCertificateType($lower);
        $siteHint = $this->detectSiteHint($message, $snapshot['sites']);

        // "When was the boiler last serviced at …"
        if ((str_contains($lower, 'boiler') || str_contains($lower, 'serviced') || str_contains($lower, 'service'))
            && (str_contains($lower, 'when') || str_contains($lower, 'last'))) {
            return $this->answerBoilerService($snapshot, $siteHint);
        }

        // Missing / no EICR / no gas
        if (preg_match('/\b(no|missing|without|lack)\b/', $lower) || str_contains($lower, 'properties with no')) {
            return $this->answerMissing($snapshot, $type);
        }

        // Expiring / due within N days
        if (preg_match('/\b(expir(?:y|ies|ing|e[ds]?)?|due|renew(?:al|ing)?|attention|needs?)\b/', $lower)) {
            return $this->answerExpiring($snapshot, $type, $days);
        }

        // Work carried out at property this year
        if ((str_contains($lower, 'work') || str_contains($lower, 'carried') || str_contains($lower, 'document') || str_contains($lower, 'everything'))
            && $siteHint) {
            return $this->answerSiteDocuments($snapshot, $siteHint);
        }

        // Document RAG: “Show me everything relating to the roof”
        if ($topic = $this->detectTopic($lower)) {
            return $this->answerDocumentTopic($snapshot, $topic, $siteHint);
        }

        // Portfolio overview
        if (preg_match('/\b(overview|summary|how many|attention|this week)\b/', $lower)) {
            return $this->answerOverview($snapshot);
        }

        return null;
    }

    private function detectTopic(string $lower): ?string
    {
        // Prefer explicit “relating to / about the X” before a broad “show me …” capture.
        if (preg_match('/\b(?:relating to|related to|regarding|concerning|about(?:\s+the)?)\s+(?:the\s+)?([a-z][a-z0-9\-]{2,40})\b/i', $lower, $m)) {
            return $this->cleanTopic($m[1]);
        }

        if (preg_match('/\b(?:everything|documents?|records?)\s+(?:on|about|for|relating to)\s+(?:the\s+)?([a-z][a-z0-9\-]{2,40})\b/i', $lower, $m)) {
            return $this->cleanTopic($m[1]);
        }

        foreach (['roof', 'boiler', 'damp', 'mould', 'mold', 'asbestos', 'gutter', 'window', 'kitchen', 'bathroom', 'electrical', 'heating', 'plumbing', 'fire', 'alarm', 'tyre', 'mot'] as $word) {
            if (str_contains($lower, $word) && (str_contains($lower, 'show') || str_contains($lower, 'find') || str_contains($lower, 'what') || str_contains($lower, 'any') || str_contains($lower, 'document') || str_contains($lower, 'relat'))) {
                return $word;
            }
        }

        return null;
    }

    private function cleanTopic(string $topic): ?string
    {
        $topic = trim(strtolower($topic), " \t.?!");
        $topic = preg_replace('/\b(please|thanks|property|portfolio|documents?|everything|relating|related)\b/i', '', $topic) ?? $topic;
        $topic = trim($topic);
        if (strlen($topic) < 3) {
            return null;
        }

        // Take first meaningful token if a phrase slipped through.
        if (str_contains($topic, ' ')) {
            $parts = preg_split('/\s+/', $topic) ?: [];
            $topic = $parts[0] ?? $topic;
        }

        return strlen($topic) >= 3 ? $topic : null;
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @param  array<string, mixed>|null  $siteHint
     * @return array<string, mixed>
     */
    private function answerDocumentTopic(array $snapshot, string $topic, ?array $siteHint): array
    {
        $needle = strtolower($topic);
        $docs = collect($snapshot['documents'])
            ->filter(function (array $doc) use ($needle, $siteHint) {
                if ($siteHint && (int) ($doc['site_id'] ?? 0) !== (int) $siteHint['id']) {
                    return false;
                }
                $hay = strtolower(implode(' ', array_filter([
                    $doc['title'] ?? '',
                    $doc['type_label'] ?? '',
                    $doc['findings'] ?? '',
                    $doc['address'] ?? '',
                    $doc['text_excerpt'] ?? '',
                    $doc['site_name'] ?? '',
                ])));

                return str_contains($hay, $needle);
            })
            ->values();

        if ($docs->isEmpty()) {
            $where = $siteHint ? " at {$siteHint['name']}" : '';

            return $this->packAnswer(
                "No documents found relating to “{$topic}”{$where}.",
                [],
                'medium',
                ['documents', 'extracted_text'],
            );
        }

        $lines = $docs->take(25)->map(function (array $doc) use ($needle) {
            $site = $doc['site_name'] ?? 'Unmatched inbox';
            $snippet = $this->topicSnippet((string) ($doc['text_excerpt'] ?: $doc['findings'] ?: $doc['title']), $needle);

            return "• {$site}: {$doc['type_label']} — {$doc['title']}"
                .($snippet ? "\n  “{$snippet}”" : '');
        })->implode("\n");

        $scope = $siteHint ? " at {$siteHint['name']}" : '';

        return $this->packAnswer(
            "{$docs->count()} document".($docs->count() === 1 ? '' : 's')." relating to “{$topic}”{$scope}:\n{$lines}",
            $docs->map(fn (array $doc) => [
                'id' => null,
                'site_id' => $doc['site_id'],
                'site_name' => $doc['site_name'] ?? 'Inbox',
                'requirement_type' => $doc['document_type'],
                'type_label' => $doc['type_label'],
                'status' => 'document',
                'next_due_date' => $doc['expires_at'],
                'label' => $doc['title'],
                'has_document' => true,
                'has_open_task' => false,
            ])->all(),
            'high',
            ['documents', 'extracted_text'],
        );
    }

    private function topicSnippet(string $text, string $needle): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return null;
        }
        $pos = stripos($text, $needle);
        if ($pos === false) {
            return Str::limit($text, 140, '…');
        }
        $start = max(0, $pos - 40);

        return Str::limit(substr($text, $start), 160, '…');
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>
     */
    private function answerExpiring(array $snapshot, ?string $type, int $days): array
    {
        $today = now()->startOfDay();
        $cutoff = $today->copy()->addDays($days);

        $matches = collect($snapshot['requirements'])
            ->filter(function (array $row) use ($type, $today, $cutoff) {
                if ($type && $row['requirement_type'] !== $type) {
                    return false;
                }
                if (! $row['next_due_date']) {
                    return false;
                }
                $due = Carbon::parse($row['next_due_date'])->startOfDay();

                return $due->lte($cutoff);
            })
            ->sortBy('next_due_date')
            ->values();

        $typeLabel = $type ? CertificateTypes::label($type) : 'certificates';
        if ($matches->isEmpty()) {
            $answer = $type
                ? "No {$typeLabel} expire within the next {$days} days."
                : "No certificates expire within the next {$days} days.";
        } else {
            $lines = $matches->take(20)->map(function (array $row) {
                $due = $row['next_due_date'] ? Carbon::parse($row['next_due_date'])->format('j M Y') : 'no date';
                $status = str_replace('_', ' ', $row['status']);

                return "• {$row['site_name']}: {$row['type_label']} — due {$due} ({$status})";
            })->implode("\n");
            $count = $matches->count();
            $answer = "{$count} ".($type ? Str::plural($typeLabel, $count) : 'certificate'.($count === 1 ? '' : 's'))
                ." expiring within {$days} days:\n{$lines}";
        }

        return $this->packAnswer($answer, $matches->all(), 'high', ['compliance_requirements']);
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>
     */
    private function answerMissing(array $snapshot, ?string $type): array
    {
        $type = $type ?? 'eicr';
        $typeLabel = CertificateTypes::label($type);

        $siteIdsWithType = collect($snapshot['requirements'])
            ->filter(fn (array $row) => $row['requirement_type'] === $type
                && ($row['next_due_date'] || $row['has_document'] || $row['last_completed_at']))
            ->pluck('site_id')
            ->unique()
            ->all();

        $matches = collect($snapshot['sites'])
            ->reject(fn (array $site) => in_array($site['id'], $siteIdsWithType, true))
            ->map(fn (array $site) => [
                'id' => null,
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'requirement_type' => $type,
                'type_label' => $typeLabel,
                'status' => 'missing',
                'next_due_date' => null,
                'label' => "Missing {$typeLabel}",
                'has_document' => false,
                'has_open_task' => false,
            ])
            ->values();

        if ($matches->isEmpty()) {
            $answer = "Every property has an {$typeLabel} on record.";
        } else {
            $list = $matches->take(30)->map(fn (array $row) => "• {$row['site_name']}")->implode("\n");
            $answer = "{$matches->count()} ".Str::plural('property', $matches->count())
                ." with no {$typeLabel}:\n{$list}";
        }

        return $this->packAnswer($answer, $matches->all(), 'high', ['sites', 'compliance_requirements']);
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @param  array<string, mixed>|null  $siteHint
     * @return array<string, mixed>
     */
    private function answerBoilerService(array $snapshot, ?array $siteHint): array
    {
        $docs = collect($snapshot['documents'])
            ->filter(fn (array $doc) => in_array($doc['document_type'], ['boiler_service', 'gas_safety'], true)
                || str_contains(strtolower((string) $doc['title']), 'boiler'));

        $reqs = collect($snapshot['requirements'])
            ->filter(fn (array $row) => $row['requirement_type'] === 'boiler_service');

        if ($siteHint) {
            $docs = $docs->filter(fn (array $doc) => (int) $doc['site_id'] === (int) $siteHint['id']);
            $reqs = $reqs->filter(fn (array $row) => (int) $row['site_id'] === (int) $siteHint['id']);
        }

        $latestDoc = $docs->sortByDesc('last_completed_hint')->first();
        $latestReq = $reqs->sortByDesc('last_completed_at')->first();

        if (! $latestDoc && ! $latestReq) {
            $where = $siteHint ? " at {$siteHint['name']}" : '';

            return $this->packAnswer(
                "No boiler service records found{$where}.",
                [],
                'medium',
                ['documents', 'compliance_requirements'],
            );
        }

        $siteName = $latestDoc['site_name'] ?? $latestReq['site_name'] ?? $siteHint['name'] ?? 'the property';
        $when = $latestReq['last_completed_at']
            ?? $latestDoc['last_completed_hint']
            ?? null;
        $whenDisplay = $when ? Carbon::parse($when)->format('j M Y') : 'an unknown date';
        $nextDue = $latestReq['next_due_date'] ?? $latestDoc['expires_at'] ?? null;
        $nextBit = $nextDue ? ' Next due '.Carbon::parse($nextDue)->format('j M Y').'.' : '';

        $matches = array_values(array_filter([$latestReq, $latestDoc ? [
            'id' => $latestReq['id'] ?? null,
            'site_id' => $latestDoc['site_id'],
            'site_name' => $latestDoc['site_name'],
            'requirement_type' => 'boiler_service',
            'type_label' => 'Boiler Servicing',
            'status' => $latestReq['status'] ?? 'compliant',
            'next_due_date' => $nextDue,
            'last_completed_at' => $when,
            'label' => 'Boiler Servicing',
            'has_document' => true,
            'has_open_task' => $latestReq['has_open_task'] ?? false,
        ] : null]));

        return $this->packAnswer(
            "The boiler at {$siteName} was last serviced on {$whenDisplay}.{$nextBit}",
            $matches,
            'high',
            ['documents', 'compliance_requirements'],
        );
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @param  array<string, mixed>  $siteHint
     * @return array<string, mixed>
     */
    private function answerSiteDocuments(array $snapshot, array $siteHint): array
    {
        $year = (int) now()->year;
        $docs = collect($snapshot['documents'])
            ->filter(fn (array $doc) => (int) $doc['site_id'] === (int) $siteHint['id'])
            ->filter(function (array $doc) use ($year) {
                $date = $doc['issued_or_uploaded'] ?? $doc['last_completed_hint'] ?? null;
                if (! $date) {
                    return true;
                }

                return Carbon::parse($date)->year === $year;
            })
            ->values();

        if ($docs->isEmpty()) {
            return $this->packAnswer(
                "No uploaded documents found for {$siteHint['name']} in {$year}.",
                [],
                'medium',
                ['documents'],
            );
        }

        $lines = $docs->take(25)->map(function (array $doc) {
            $when = $doc['issued_or_uploaded'] ? Carbon::parse($doc['issued_or_uploaded'])->format('j M Y') : 'undated';

            return "• {$doc['type_label']}: {$doc['title']} ({$when})";
        })->implode("\n");

        return $this->packAnswer(
            "Documents for {$siteHint['name']} in {$year}:\n{$lines}",
            $docs->map(fn (array $doc) => [
                'id' => null,
                'site_id' => $doc['site_id'],
                'site_name' => $doc['site_name'],
                'requirement_type' => $doc['document_type'],
                'type_label' => $doc['type_label'],
                'status' => 'document',
                'next_due_date' => $doc['expires_at'],
                'label' => $doc['title'],
                'has_document' => true,
                'has_open_task' => false,
            ])->all(),
            'high',
            ['documents'],
        );
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>
     */
    private function answerOverview(array $snapshot): array
    {
        $reqs = collect($snapshot['requirements']);
        $overdue = $reqs->where('status', ComplianceRequirement::STATUS_OVERDUE)->count();
        $dueSoon = $reqs->where('status', ComplianceRequirement::STATUS_DUE_SOON)->count();
        $compliant = $reqs->where('status', ComplianceRequirement::STATUS_COMPLIANT)->count();
        $siteCount = count($snapshot['sites']);
        $docCount = count($snapshot['documents']);

        $attention = $reqs
            ->filter(fn (array $row) => in_array($row['status'], [
                ComplianceRequirement::STATUS_OVERDUE,
                ComplianceRequirement::STATUS_DUE_SOON,
            ], true))
            ->sortBy('next_due_date')
            ->values();

        $answer = "Portfolio: {$siteCount} properties, {$docCount} documents.\n"
            ."🔴 {$overdue} overdue · 🟠 {$dueSoon} due soon · 🟢 {$compliant} compliant.";

        if ($attention->isNotEmpty()) {
            $lines = $attention->take(12)->map(function (array $row) {
                $due = $row['next_due_date'] ? Carbon::parse($row['next_due_date'])->format('j M Y') : '—';

                return "• {$row['site_name']}: {$row['type_label']} ({$row['status']}, due {$due})";
            })->implode("\n");
            $answer .= "\n\nNeeds attention:\n{$lines}";
        }

        return $this->packAnswer($answer, $attention->all(), 'high', ['sites', 'compliance_requirements', 'documents']);
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>
     */
    private function fallbackAnswer(array $snapshot): array
    {
        return $this->answerOverview($snapshot);
    }

    /**
     * @param  array{sites: array, requirements: array, documents: array}  $snapshot
     * @return array<string, mixed>|null
     */
    private function answerWithOpenAi(string $message, array $snapshot): ?array
    {
        try {
            $compact = [
                'today' => now()->toDateString(),
                'sites' => array_slice($snapshot['sites'], 0, 80),
                'requirements' => array_slice($snapshot['requirements'], 0, 150),
                'documents' => array_slice($snapshot['documents'], 0, 80),
            ];

            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => <<<'PROMPT'
You are Property Compliance AI for landlords and estate agents.
Answer ONLY using the provided portfolio JSON (sites, certificate requirements, documents).
If the data does not contain the answer, say so clearly — never invent properties, dates, or certificates.
Return JSON:
{
  "answer": "plain text, use short bullet lines when listing properties",
  "match_requirement_ids": [1, 2],
  "match_site_ids": [3],
  "confidence": "high|medium|low"
}
PROMPT
                        ],
                        [
                            'role' => 'user',
                            'content' => "Portfolio data:\n".json_encode($compact)."\n\nQuestion: {$message}",
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Portfolio AI OpenAI request failed', ['status' => $response->status()]);

                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $decoded = is_string($content) ? json_decode($content, true) : null;
            if (! is_array($decoded) || empty($decoded['answer'])) {
                return null;
            }

            $reqIds = collect($decoded['match_requirement_ids'] ?? [])->map(fn ($id) => (int) $id)->all();
            $siteIds = collect($decoded['match_site_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

            $matches = collect($snapshot['requirements'])
                ->filter(fn (array $row) => in_array((int) $row['id'], $reqIds, true)
                    || in_array((int) $row['site_id'], $siteIds, true))
                ->values()
                ->all();

            return $this->packAnswer(
                (string) $decoded['answer'],
                $matches,
                (string) ($decoded['confidence'] ?? 'medium'),
                ['sites', 'compliance_requirements', 'documents', 'openai'],
            );
        } catch (\Throwable $e) {
            Log::warning('Portfolio AI failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @param  array<int, string>  $sources
     * @return array<string, mixed>
     */
    private function packAnswer(string $answer, array $matches, string $confidence, array $sources): array
    {
        $suggested = collect($matches)
            ->filter(fn (array $row) => ! empty($row['id'])
                && in_array($row['status'] ?? '', [
                    ComplianceRequirement::STATUS_OVERDUE,
                    ComplianceRequirement::STATUS_DUE_SOON,
                    ComplianceRequirement::STATUS_MISSING,
                ], true)
                && empty($row['has_open_task']))
            ->take(10)
            ->map(fn (array $row) => [
                'title' => ($row['label'] ?? $row['type_label'] ?? 'Compliance').' — '.($row['site_name'] ?? 'property'),
                'requirement_id' => $row['id'],
                'asset_id' => $row['site_id'] ?? null,
                'due_date' => $row['next_due_date'] ?? null,
                'category' => 'compliance',
                'priority' => ($row['status'] ?? '') === ComplianceRequirement::STATUS_OVERDUE ? 'high' : 'normal',
                'description' => 'Created from Property Compliance AI portfolio query.',
            ])
            ->values()
            ->all();

        return [
            'intent' => 'portfolio_answer',
            'answer' => $answer,
            'matches' => array_values($matches),
            'suggested_tasks' => $suggested,
            'confidence' => $confidence,
            'requires_confirmation' => count($suggested) > 0,
            'sources' => $sources,
        ];
    }

    private function extractDays(string $lower): ?int
    {
        if (preg_match('/\b(\d+)\s*days?\b/', $lower, $m)) {
            return max(1, min(365, (int) $m[1]));
        }
        if (str_contains($lower, 'two weeks') || str_contains($lower, '14 days')) {
            return 14;
        }
        if (str_contains($lower, 'this week') || str_contains($lower, 'next week')) {
            return 7;
        }
        if (str_contains($lower, 'this month') || str_contains($lower, 'next month')) {
            return 30;
        }
        if (str_contains($lower, '60')) {
            return 60;
        }

        return null;
    }

    private function detectCertificateType(string $lower): ?string
    {
        $map = [
            'gas' => 'gas_safety',
            'cp12' => 'gas_safety',
            'eicr' => 'eicr',
            'electrical' => 'eicr',
            'epc' => 'epc',
            'energy' => 'epc',
            'insurance' => 'insurance',
            'boiler' => 'boiler_service',
            'fire' => 'fire_safety',
            'pat' => 'pat_testing',
        ];

        foreach ($map as $needle => $type) {
            if (str_contains($lower, $needle)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sites
     * @return array<string, mixed>|null
     */
    private function detectSiteHint(string $message, array $sites): ?array
    {
        $lower = strtolower($message);
        $best = null;
        $bestLen = 0;

        foreach ($sites as $site) {
            $name = strtolower((string) $site['name']);
            if ($name !== '' && str_contains($lower, $name) && strlen($name) > $bestLen) {
                $best = $site;
                $bestLen = strlen($name);
            }

            $address = strtolower((string) ($site['address'] ?? ''));
            if ($address !== '' && strlen($address) > 6 && str_contains($lower, $address) && strlen($address) > $bestLen) {
                $best = $site;
                $bestLen = strlen($address);
            }
        }

        // Partial: "22 richmond" against "22 Richmond Road"
        if (! $best) {
            foreach ($sites as $site) {
                $tokens = preg_split('/\s+/', strtolower((string) $site['name'])) ?: [];
                $hits = 0;
                foreach ($tokens as $token) {
                    if (strlen($token) >= 3 && str_contains($lower, $token)) {
                        $hits++;
                    }
                }
                if ($hits >= 2) {
                    return $site;
                }
            }
        }

        return $best;
    }
}
