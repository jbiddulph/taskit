<?php

namespace App\Exceptions;

use App\Models\Company;
use Exception;
use Illuminate\Http\JsonResponse;

class DocumentAiAllowanceExceededException extends Exception
{
    public function __construct(
        public readonly Company $company,
        public readonly array $usageSummary,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $this->defaultMessage());
    }

    public function defaultMessage(): string
    {
        $limit = $this->usageSummary['limit'] ?? $this->company->getDocumentAiAllowance();
        $used = $this->usageSummary['used'] ?? 0;

        return "Document AI allowance reached ({$used}/{$limit} this month). Upgrade your plan for more AI document reads.";
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error' => 'document_ai_allowance_exceeded',
            'document_ai' => $this->usageSummary,
        ], 429);
    }
}
