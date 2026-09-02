<?php

namespace App\Services;

use App\Mail\ComplianceExpiryMail;
use App\Mail\ComplianceExtractionMail;
use App\Models\Company;
use App\Models\DocumentExtractionProposal;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ComplianceNotificationService
{
    /**
     * Notify every user linked to the company (via company code) that OpenAI read a document.
     */
    public function notifyCompanyOfExtraction(DocumentExtractionProposal $proposal): int
    {
        $proposal->loadMissing(['operationalObject', 'operationalDocument']);

        $company = Company::find($proposal->company_id);
        if (! $company) {
            return 0;
        }

        $site = $proposal->operationalObject;
        $data = $proposal->extracted_data ?? [];
        $label = $data['label'] ?? ($data['document_type'] ?? 'Compliance document');
        $expiry = $data['expiry_date'] ?? null;
        $message = $proposal->summary
            ?: ($expiry ? "{$label} for {$site?->name} — expiry {$expiry}." : "{$label} for {$site?->name} is ready to review.");

        $created = 0;
        foreach ($this->companyUsers($company->id) as $user) {
            $created += $this->notifyIfNotExists(
                user: $user,
                type: 'document_extraction',
                title: 'Compliance document read',
                message: $message,
                data: [
                    'proposal_id' => $proposal->id,
                    'operational_object_id' => $proposal->operational_object_id,
                    'operational_document_id' => $proposal->operational_document_id,
                    'scope' => 'extraction',
                ],
                onceOnly: true,
            );

            $this->sendMailSafely(
                $user,
                new ComplianceExtractionMail($company, $proposal, $site, $user->name),
            );
        }

        return $created;
    }

    public function notifyCompanyOfExpiry(
        Company $company,
        string $label,
        string $siteName,
        ?Carbon $expiryDate,
        string $scope,
        array $data,
    ): int {
        $remaining = $expiryDate
            ? (int) now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay(), false)
            : null;

        $whenText = $this->whenText($remaining);
        $title = $remaining !== null && $remaining < 0
            ? 'Compliance expired'
            : 'Compliance reminder';
        $message = "{$label} for {$siteName} {$whenText}.";
        $subject = ($remaining !== null && $remaining < 0 ? 'Expired' : 'Reminder').": {$label} — {$siteName}";

        $created = 0;
        foreach ($this->companyUsers($company->id) as $user) {
            $created += $this->notifyIfNotExists(
                user: $user,
                type: $remaining !== null && $remaining < 0 ? 'warning' : 'info',
                title: $title,
                message: $message,
                data: array_merge($data, ['scope' => $scope]),
                onceOnly: $remaining !== null && $remaining < 0,
            );

            $this->sendMailSafely(
                $user,
                new ComplianceExpiryMail(
                    $company,
                    $user->name,
                    $label,
                    $siteName,
                    $whenText,
                    $expiryDate?->toDateString(),
                    $subject,
                ),
            );
        }

        return $created;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function companyUsers(int $companyId)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->orderBy('created_at')
            ->get();
    }

    protected function whenText(?int $remaining): string
    {
        if ($remaining === null) {
            return 'has no expiry date on file';
        }

        if ($remaining < 0) {
            $days = abs($remaining);

            return 'expired '.$days.' '.($days === 1 ? 'day' : 'days').' ago';
        }

        if ($remaining === 0) {
            return 'expires today';
        }

        return 'expires in '.$remaining.' '.($remaining === 1 ? 'day' : 'days');
    }

    protected function notifyIfNotExists(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data,
        bool $onceOnly = false,
    ): int {
        $query = Notification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereRaw("(data->>'scope') = ?", [(string) ($data['scope'] ?? '')]);

        if (isset($data['proposal_id'])) {
            $query->whereRaw("(data->>'proposal_id') = ?", [(string) $data['proposal_id']]);
        } elseif (isset($data['requirement_id'])) {
            $query->whereRaw("(data->>'requirement_id') = ?", [(string) $data['requirement_id']]);
        } elseif (isset($data['document_id'])) {
            $query->whereRaw("(data->>'document_id') = ?", [(string) $data['document_id']]);
        }

        if (! $onceOnly) {
            $query->whereDate('created_at', now()->toDateString());
        }

        if ($query->exists()) {
            return 0;
        }

        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        return 1;
    }

    protected function sendMailSafely(User $user, $mailable): void
    {
        if (! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('Compliance notification email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
