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
    public function extractFromDocument(OperationalDocument $document, User $user): ?DocumentExtractionProposal
    {
        $webhookUrl = config('services.n8n.document_extraction_webhook_url');

        if ($webhookUrl) {
            return $this->sendToN8n($document, $user, $webhookUrl);
        }

        if (! config('services.openai.api_key')) {
            return null;
        }

        if (! $document->is_image && ! $document->is_pdf) {
            return null;
        }

        return $this->extractWithOpenAi($document, $user);
    }

    public function createProposalFromExtraction(
        OperationalDocument $document,
        User $user,
        array $extractedData,
        ?string $summary = null,
    ): DocumentExtractionProposal {
        return DocumentExtractionProposal::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'operational_document_id' => $document->id,
            'operational_object_id' => $document->operational_object_id,
            'status' => DocumentExtractionProposal::STATUS_PENDING,
            'extracted_data' => $extractedData,
            'summary' => $summary,
            'metadata' => [
                'original_filename' => $document->original_filename,
                'document_type' => $document->document_type,
            ],
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
        );
    }

    protected function sendToN8n(OperationalDocument $document, User $user, string $webhookUrl): ?DocumentExtractionProposal
    {
        $path = Storage::disk('private')->path($document->file_path);

        try {
            $response = Http::timeout(120)
                ->attach(
                    'file',
                    file_get_contents($path),
                    $document->original_filename,
                    ['Content-Type' => $document->mime_type]
                )
                ->post($webhookUrl, [
                    'operational_document_id' => $document->id,
                    'operational_object_id' => $document->operational_object_id,
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'mime_type' => $document->mime_type,
                    'title' => $document->title,
                ]);

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
            );
        } catch (\Throwable $e) {
            Log::warning('N8N document extraction request failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function extractWithOpenAi(OperationalDocument $document, User $user): ?DocumentExtractionProposal
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $path = Storage::disk('private')->path($document->file_path);
        $contents = base64_encode(file_get_contents($path));

        $mime = $document->mime_type;
        $prompt = <<<'PROMPT'
Extract certificate/compliance document fields from this file. Return ONLY valid JSON with these keys (use null if unknown):
{
  "document_type": "gas_safety|epc|electrical|pat_testing|fire_alarm|other",
  "label": "human readable name e.g. Gas Safety Certificate",
  "certificate_number": "string or null",
  "expiry_date": "YYYY-MM-DD or null",
  "issue_date": "YYYY-MM-DD or null",
  "engineer_name": "string or null",
  "address": "string or null",
  "summary": "one sentence summary"
}
PROMPT;

        try {
            if (! $document->is_image) {
                return null;
            }

            $content = [
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => "data:{$mime};base64,{$contents}",
                    ],
                ],
                ['type' => 'text', 'text' => $prompt],
            ];

            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $content],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

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

            return $this->createProposalFromExtraction($document, $user, $extracted, $summary);
        } catch (\Throwable $e) {
            Log::warning('OpenAI document extraction error', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
