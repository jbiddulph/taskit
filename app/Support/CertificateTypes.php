<?php

namespace App\Support;

class CertificateTypes
{
    /**
     * Canonical certificate / contract types used across extraction, templates, and UI.
     *
     * @return array<string, array{label: string, short: string, frequency: string, lead_days: int, task_type: string}>
     */
    public static function list(): array
    {
        return [
            'gas_safety' => [
                'label' => 'Gas Safety (CP12)',
                'short' => 'Gas',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'boiler_service' => [
                'label' => 'Boiler Servicing',
                'short' => 'Boiler',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Maintenance',
            ],
            'eicr' => [
                'label' => 'EICR',
                'short' => 'EICR',
                'frequency' => '5_years',
                'lead_days' => 60,
                'task_type' => 'Compliance',
            ],
            'pat_testing' => [
                'label' => 'PAT Testing',
                'short' => 'PAT',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Inspection',
            ],
            'fire_safety' => [
                'label' => 'Fire Safety / FRA',
                'short' => 'Fire Safety',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Safety',
            ],
            'fire_alarm' => [
                'label' => 'Fire Alarm Inspection',
                'short' => 'Fire Alarm',
                'frequency' => '6_months',
                'lead_days' => 14,
                'task_type' => 'Inspection',
            ],
            'emergency_lighting' => [
                'label' => 'Emergency Lighting',
                'short' => 'Lighting',
                'frequency' => '6_months',
                'lead_days' => 14,
                'task_type' => 'Inspection',
            ],
            'insurance' => [
                'label' => 'Insurance',
                'short' => 'Insurance',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'contract' => [
                'label' => 'Contract',
                'short' => 'Contract',
                'frequency' => 'annual',
                'lead_days' => 60,
                'task_type' => 'Lease',
            ],
            'legionella' => [
                'label' => 'Legionella Assessment',
                'short' => 'Legionella',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'asbestos' => [
                'label' => 'Asbestos Survey',
                'short' => 'Asbestos',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'epc' => [
                'label' => 'EPC',
                'short' => 'EPC',
                'frequency' => '10_years',
                'lead_days' => 90,
                'task_type' => 'Compliance',
            ],
            'inspection' => [
                'label' => 'Inspection',
                'short' => 'Inspection',
                'frequency' => 'annual',
                'lead_days' => 14,
                'task_type' => 'Inspection',
            ],
            'other' => [
                'label' => 'Other document',
                'short' => 'Other',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
        ];
    }

    public static function ids(): array
    {
        return array_keys(self::list());
    }

    public static function label(string $type): string
    {
        return self::list()[$type]['label'] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function meta(string $type): array
    {
        return self::list()[$type] ?? self::list()['other'];
    }

    /**
     * Map model / OpenAI aliases onto canonical type ids.
     */
    public static function normalize(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        $aliases = [
            'pat' => 'pat_testing',
            'portable_appliance' => 'pat_testing',
            'electrical' => 'eicr',
            'electrical_certificate' => 'eicr',
            'eicr' => 'eicr',
            'gas' => 'gas_safety',
            'cp12' => 'gas_safety',
            'boiler' => 'boiler_service',
            'boiler_servicing' => 'boiler_service',
            'fire' => 'fire_safety',
            'fire_risk' => 'fire_safety',
            'fra' => 'fire_safety',
            'tenancy' => 'contract',
            'tenancy_renewal' => 'contract',
            'maintenance_contract' => 'contract',
            'lease' => 'contract',
            'policy' => 'insurance',
        ];

        $canonical = $aliases[$type] ?? $type;

        return array_key_exists($canonical, self::list()) ? $canonical : 'other';
    }

    public static function frequencyFor(string $type): string
    {
        return self::meta($type)['frequency'];
    }

    public static function leadDaysFor(string $type): int
    {
        return self::meta($type)['lead_days'];
    }

    public static function taskTypeFor(string $type): string
    {
        return self::meta($type)['task_type'];
    }
}
