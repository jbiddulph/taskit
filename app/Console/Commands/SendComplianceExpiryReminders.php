<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ComplianceRequirement;
use App\Models\OperationalDocument;
use App\Services\ComplianceNotificationService;
use App\Support\CertificateTypes;
use Illuminate\Console\Command;

class SendComplianceExpiryReminders extends Command
{
    protected $signature = 'compliance:send-expiry-reminders';

    protected $description = 'Notify company users about certificates, services, and contracts that are due or expired';

    public function handle(ComplianceNotificationService $notifications): int
    {
        $created = 0;

        Company::query()->chunkById(50, function ($companies) use ($notifications, &$created) {
            foreach ($companies as $company) {
                $created += $this->remindForCompany($company, $notifications);
            }
        });

        $this->info("Compliance expiry notifications created: {$created}");

        return self::SUCCESS;
    }

    protected function remindForCompany(Company $company, ComplianceNotificationService $notifications): int
    {
        $created = 0;
        $warnDays = config('compliance.warn_days', [90, 30, 14, 7]);

        $requirements = ComplianceRequirement::forCompany($company->id)
            ->with('operationalObject')
            ->whereNotNull('next_due_date')
            ->get();

        foreach ($requirements as $requirement) {
            $requirement->refreshStatus();
            $window = $this->matchingWindow($requirement->next_due_date, $warnDays);
            if ($window === null) {
                continue;
            }

            $siteName = $requirement->operationalObject?->name ?? 'site';
            $created += $notifications->notifyCompanyOfExpiry(
                $company,
                $requirement->label ?: CertificateTypes::label($requirement->requirement_type),
                $siteName,
                $requirement->next_due_date,
                $window,
                [
                    'requirement_id' => $requirement->id,
                    'operational_object_id' => $requirement->operational_object_id,
                ],
            );
        }

        $documents = OperationalDocument::forCompany($company->id)
            ->with('operationalObject')
            ->whereNotNull('expires_at')
            ->where('status', '!=', OperationalDocument::STATUS_ARCHIVED)
            ->get();

        foreach ($documents as $document) {
            $document->refreshExpiryStatus();
            $window = $this->matchingWindow($document->expires_at, $warnDays);
            if ($window === null) {
                continue;
            }

            if ($document->compliance_requirement_id) {
                continue;
            }

            $siteName = $document->operationalObject?->name ?? 'site';
            $created += $notifications->notifyCompanyOfExpiry(
                $company,
                $document->title ?: CertificateTypes::label($document->document_type ?: 'other'),
                $siteName,
                $document->expires_at,
                $window,
                [
                    'document_id' => $document->id,
                    'operational_object_id' => $document->operational_object_id,
                ],
            );
        }

        return $created;
    }

    protected function matchingWindow($expiryDate, array $warnDays): ?string
    {
        if (! $expiryDate) {
            return null;
        }

        $remaining = (int) now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay(), false);

        if ($remaining < 0) {
            return 'expired';
        }

        if ($remaining === 0) {
            return 'due_today';
        }

        if (in_array($remaining, array_map('intval', $warnDays), true)) {
            return 'due_'.$remaining;
        }

        return null;
    }
}
