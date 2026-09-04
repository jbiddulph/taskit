<?php

namespace App\Support;

class ComplianceTemplates
{
    public static function forIndustry(?string $industry): array
    {
        $industry = Industries::resolve($industry);
        $templates = config("compliance_templates.{$industry}", []);

        if ($templates === []) {
            return config('compliance_templates.general', []);
        }

        return $templates;
    }

    public static function hasTemplates(?string $industry): bool
    {
        return count(self::forIndustry($industry)) > 0;
    }

    public static function taskTypeFor(string $industry, string $requirementType): string
    {
        $templates = self::forIndustry($industry);

        foreach ($templates as $template) {
            if (($template['type'] ?? null) === $requirementType) {
                return $template['task_type'] ?? 'Compliance';
            }
        }

        return CertificateTypes::taskTypeFor($requirementType);
    }

    public static function frequencyDays(string $frequency): int
    {
        return match ($frequency) {
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 91,
            '6_months' => 182,
            'annual' => 365,
            '2_years' => 730,
            '5_years' => 1825,
            '10_years' => 3650,
            default => 365,
        };
    }
}
