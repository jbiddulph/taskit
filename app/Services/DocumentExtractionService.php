<?php

namespace App\Services;

use App\Models\DocumentExtractionProposal;
use App\Models\OperationalDocument;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentExtractionService
{
    public function extractFromDocument(
        OperationalDocument $document,
        User $user,
        ?int $projectId = null,
    ): ?DocumentExtractionProposal {
        $webhookUrl = config('services.n8n.document_extraction_webhook_url');

        if ($webhookUrl) {
            return $this->sendToN8n($document, $user, $webhookUrl, $projectId);
        }

        if (! config('services.openai.api_key')) {
            return null;
        }

        if (! $document->is_image && ! $document->is_pdf) {
            return null;
        }

        return $this->extractWithOpenAi($document, $user, $projectId);
    }

    public function createProposalFromExtraction(
        OperationalDocument $document,
        User $user,
        array $extractedData,
        ?string $summary = null,
        ?int $projectId = null,
    ): DocumentExtractionProposal {
        $summaryValue = $extractedData['summary'] ?? $summary;
        unset($extractedData['summary']);

        return DocumentExtractionProposal::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
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

    protected function sendToN8n(
        OperationalDocument $document,
        User $user,
        string $webhookUrl,
        ?int $projectId = null,
    ): ?DocumentExtractionProposal {
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

            return $this->createProposalFromExtraction(
                $document,
                $user,
                $data['extracted_data'],
                $data['summary'] ?? null,
                $projectId,
            );
        } catch (\Throwable $e) {
            Log::warning('N8N document extraction request failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractWithOpenAi(
        OperationalDocument $document,
        User $user,
        ?int $projectId = null,
    ): ?DocumentExtractionProposal {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $path = Storage::disk('private')->path($document->file_path);
        $prompt = $this->extractionPrompt();

        try {
            if ($document->is_pdf) {
                $text = $this->extractPdfText($path);
                if (trim($text) === '') {
                    Log::warning('PDF text extraction returned empty', ['document_id' => $document->id]);

                    return null;
                }

                $response = Http::withToken($apiKey)
                    ->timeout(90)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt."\n\n--- DOCUMENT TEXT ---\n".$text,
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
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'image_url',
                                        'image_url' => ['url' => "data:{$mime};base64,{$contents}"],
                                    ],
                                    ['type' => 'text', 'text' => $prompt],
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

                return null;
            }

            $json = $response->json('choices.0.message.content');
            $extracted = json_decode($json, true);

            if (! is_array($extracted)) {
                return null;
            }

            $summary = $extracted['summary'] ?? null;
            unset($extracted['summary']);

            return $this->createProposalFromExtraction($document, $user, $extracted, $summary, $projectId);
        } catch (\Throwable $e) {
            Log::warning('OpenAI document extraction error', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractionPrompt(): string
    {
        return <<<'PROMPT'
Extract certificate/compliance document fields from this UK property document. Return ONLY valid JSON:
{
  "document_type": "gas_safety|epc|electrical|pat_testing|fire_alarm|other",
  "label": "human readable name e.g. Gas Safety Certificate",
  "certificate_number": "string or null",
  "expiry_date": "YYYY-MM-DD or null",
  "issue_date": "YYYY-MM-DD or null",
  "engineer_name": "string or null",
  "address": "full property address or null",
  "summary": "one sentence summary",
  "suggested_tasks": [
    {
      "title": "actionable task title",
      "due_date": "YYYY-MM-DD or null",
      "priority": "Low|Medium|High",
      "notes": "optional context"
    }
  ]
}

Rules:
- Convert UK dates (DD/MM/YYYY) to ISO YYYY-MM-DD
- suggested_tasks: include renewal reminders, engineer follow-ups, or compliance actions implied by the document
- Use null when unknown — do not guess
PROMPT;
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
