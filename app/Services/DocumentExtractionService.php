<?php

namespace App\Services;

use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\User;
use App\Support\CertificateFieldExtractor;
use App\Support\CertificateTypes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentExtractionService
{
    public function __construct(
        protected ComplianceNotificationService $notificationService,
    ) {}

    public function extractFromDocument(
        OperationalDocument $document,
        User $user,
        ?int $projectId = null,
    ): ?DocumentExtractionProposal {
        $extracted = null;

        if (config('services.openai.api_key') && ($document->is_image || $document->is_pdf)) {
            $extracted = $this->extractWithOpenAi($document);
        }

        $webhookUrl = config('services.n8n.document_extraction_webhook_url');
        if (! $extracted && $webhookUrl) {
            $extracted = $this->extractViaN8n($document, $user, $webhookUrl, $projectId);
        }

        if (! $extracted && $document->is_pdf) {
            $text = $this->extractPdfText(Storage::disk('private')->path($document->file_path));
            if (trim($text) !== '') {
                $extracted = CertificateFieldExtractor::fromText($text);
            }
        }

        if (! is_array($extracted) || ! CertificateFieldExtractor::hasUsefulFields($extracted)) {
            return null;
        }

        return $this->createProposalFromExtraction(
            $document,
            $user,
            $extracted,
            $extracted['summary'] ?? null,
            $projectId,
        );
    }

    public function createProposalFromExtraction(
        OperationalDocument $document,
        User $user,
        array $extractedData,
        ?string $summary = null,
        ?int $projectId = null,
    ): DocumentExtractionProposal {
        $extractedData = $this->normalizeExtractedData($extractedData);
        $summaryValue = $extractedData['summary'] ?? $summary;
        unset($extractedData['summary'], $extractedData['confidence'], $extractedData['source']);

        $this->applyExtractionToDocument($document, $extractedData);

        $proposal = DocumentExtractionProposal::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id ?? $document->company_id,
            'operational_document_id' => $document->id,
            'operational_object_id' => $document->operational_object_id,
            'status' => DocumentExtractionProposal::STATUS_PENDING,
            'extracted_data' => $extractedData,
            'summary' => $summaryValue,
            'metadata' => array_filter([
                'original_filename' => $document->original_filename,
                'document_type' => $document->document_type,
                'project_id' => $projectId,
            ]),
        ]);

        $this->notificationService->notifyCompanyOfExtraction($proposal);

        return $proposal;
    }

    public function createFromN8n(array $payload): DocumentExtractionProposal
    {
        $document = OperationalDocument::findOrFail($payload['operational_document_id']);
        $user = User::findOrFail($payload['user_id']);

        return $this->createProposalFromExtraction(
            $document,
            $user,
            $payload['extracted_data'] ?? [],
            $payload['summary'] ?? null,
            isset($payload['project_id']) ? (int) $payload['project_id'] : null,
        );
    }

    protected function extractViaN8n(
        OperationalDocument $document,
        User $user,
        string $webhookUrl,
        ?int $projectId = null,
    ): ?array {
        $path = Storage::disk('private')->path($document->file_path);

        try {
            $response = Http::timeout(120)
                ->attach(
                    'file',
                    file_get_contents($path),
                    $document->original_filename,
                    ['Content-Type' => $document->mime_type]
                )
                ->post($webhookUrl, array_filter([
                    'operational_document_id' => $document->id,
                    'operational_object_id' => $document->operational_object_id,
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'mime_type' => $document->mime_type,
                    'title' => $document->title,
                    'project_id' => $projectId,
                ]));

            if (! $response->successful()) {
                Log::warning('N8N document extraction webhook failed', [
                    'document_id' => $document->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['extracted_data'])) {
                return null;
            }

            $extracted = $data['extracted_data'];
            if (! empty($data['summary'])) {
                $extracted['summary'] = $data['summary'];
            }
            $extracted['source'] = 'n8n';

            return $extracted;
        } catch (\Throwable $e) {
            Log::warning('N8N document extraction request failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractWithOpenAi(OperationalDocument $document): ?array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $path = Storage::disk('private')->path($document->file_path);
        $prompt = $this->extractionPrompt();
        $fallback = [
            'document_type' => null,
            'label' => null,
            'certificate_number' => null,
            'expiry_date' => null,
            'issue_date' => null,
            'engineer_name' => null,
            'address' => null,
            'summary' => null,
            'suggested_tasks' => [],
            'confidence' => 0,
            'source' => 'rules',
        ];

        try {
            if ($document->is_pdf) {
                $text = $this->extractPdfText($path);
                $fallback = CertificateFieldExtractor::fromText($text);

                if (trim($text) === '') {
                    Log::warning('PDF text extraction returned empty', ['document_id' => $document->id]);

                    return CertificateFieldExtractor::hasUsefulFields($fallback) ? $fallback : null;
                }

                $response = Http::withToken($apiKey)
                    ->timeout(90)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'temperature' => 0,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $prompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => "Extract the certificate, service, or contract fields from this UK document text:\n\n".$text,
                            ],
                        ],
                        'response_format' => ['type' => 'json_object'],
                    ]);
            } elseif ($document->is_image) {
                $contents = base64_encode(file_get_contents($path));
                $mime = $document->mime_type;

                $response = Http::withToken($apiKey)
                    ->timeout(90)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'temperature' => 0,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $prompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'image_url',
                                        'image_url' => ['url' => "data:{$mime};base64,{$contents}"],
                                    ],
                                    ['type' => 'text', 'text' => 'Extract the certificate, service, or contract fields from this document image.'],
                                ],
                            ],
                        ],
                        'response_format' => ['type' => 'json_object'],
                    ]);
            } else {
                return null;
            }

            if (! $response->successful()) {
                Log::warning('OpenAI document extraction failed', [
                    'document_id' => $document->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return CertificateFieldExtractor::hasUsefulFields($fallback) ? $fallback : null;
            }

            $json = $response->json('choices.0.message.content');
            $extracted = json_decode($json, true);

            if (! is_array($extracted)) {
                return CertificateFieldExtractor::hasUsefulFields($fallback) ? $fallback : null;
            }

            $extracted['source'] = 'openai';
            $extracted['confidence'] = 0.86;

            return CertificateFieldExtractor::merge($extracted, $fallback);
        } catch (\Throwable $e) {
            Log::warning('OpenAI document extraction error', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return CertificateFieldExtractor::hasUsefulFields($fallback) ? $fallback : null;
        }
    }

    protected function extractionPrompt(): string
    {
        $types = implode('|', CertificateTypes::ids());

        return <<<PROMPT
Extract UK property / facilities compliance fields from this document. Return ONLY valid JSON:
{
  "document_type": "{$types}",
  "label": "human readable name e.g. Gas Safety Certificate, PAT Test, Boiler Service, Fire Safety Assessment, Maintenance Contract",
  "certificate_number": "string or null",
  "expiry_date": "YYYY-MM-DD or null",
  "issue_date": "YYYY-MM-DD or null",
  "engineer_name": "string or null",
  "address": "full property address or null",
  "summary": "one sentence summary including the type and expiry if present",
  "suggested_tasks": [
    {
      "title": "actionable task title",
      "due_date": "YYYY-MM-DD or null",
      "priority": "Low|Medium|High",
      "notes": "optional context"
    }
  ]
}

Document types:
- gas_safety: landlord gas safety / CP12
- boiler_service: annual boiler servicing
- pat_testing: portable appliance testing
- fire_safety: fire risk assessment / fire safety
- fire_alarm: fire alarm inspection
- eicr: electrical installation condition report
- contract: tenancy, maintenance, or other contracts with an end date
- insurance, legionella, asbestos, epc, emergency_lighting, inspection, other

Rules:
- Convert UK dates (DD/MM/YYYY) to ISO YYYY-MM-DD
- Prefer labelled expiry / valid until / next due / contract end dates
- suggested_tasks: include renewal reminders, engineer follow-ups, or compliance actions implied by the document
- Use null when unknown — do not guess
PROMPT;
    }

    protected function normalizeExtractedData(array $extractedData): array
    {
        if (! empty($extractedData['expiresOn']) && empty($extractedData['expiry_date'])) {
            $extractedData['expiry_date'] = $extractedData['expiresOn'];
        }
        if (! empty($extractedData['issuedOn']) && empty($extractedData['issue_date'])) {
            $extractedData['issue_date'] = $extractedData['issuedOn'];
        }
        if (! empty($extractedData['certificateNumber']) && empty($extractedData['certificate_number'])) {
            $extractedData['certificate_number'] = $extractedData['certificateNumber'];
        }
        if (! empty($extractedData['contractorName']) && empty($extractedData['engineer_name'])) {
            $extractedData['engineer_name'] = $extractedData['contractorName'];
        }
        if (! empty($extractedData['type']) && empty($extractedData['document_type'])) {
            $extractedData['document_type'] = $extractedData['type'];
        }

        if (! empty($extractedData['document_type'])) {
            $extractedData['document_type'] = CertificateTypes::normalize($extractedData['document_type']);
            if (empty($extractedData['label'])) {
                $extractedData['label'] = CertificateTypes::label($extractedData['document_type']);
            }
        }

        foreach (['expiry_date', 'issue_date'] as $dateKey) {
            if (! empty($extractedData[$dateKey])) {
                $parsed = CertificateFieldExtractor::parseUkDate((string) $extractedData[$dateKey]);
                if ($parsed) {
                    $extractedData[$dateKey] = $parsed;
                }
            }
        }

        return $extractedData;
    }

    protected function applyExtractionToDocument(OperationalDocument $document, array $extractedData): void
    {
        $updates = [
            'extracted_data' => $extractedData,
        ];

        if (! empty($extractedData['document_type'])) {
            $updates['document_type'] = $extractedData['document_type'];
        }

        if (! empty($extractedData['label'])) {
            $updates['title'] = $extractedData['label'];
        }

        if (! empty($extractedData['expiry_date']) && ! $document->expires_at) {
            $updates['expires_at'] = $extractedData['expiry_date'];
        }

        $document->update($updates);
        $document->refreshExpiryStatus();
    }

    protected function extractPdfText(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        $text = '';
        if (preg_match_all('/\(([^\(\)\\\\]+)\)/', $content, $matches)) {
            $text = implode(' ', $matches[1]);
        }

        if (strlen(trim($text)) < 20 && preg_match_all('/stream[\s\S]*?endstream/', $content, $streams)) {
            foreach ($streams[0] as $stream) {
                $decoded = @gzuncompress(substr($stream, 7, -9));
                if (is_string($decoded)) {
                    $text .= ' '.$decoded;
                }
            }
        }

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
