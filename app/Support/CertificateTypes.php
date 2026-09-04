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
            'qualifications' => [
                'label' => 'Trade Registration / Qualifications',
                'short' => 'Registration',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'unvented_cylinder' => [
                'label' => 'Unvented Cylinder Certificate',
                'short' => 'Unvented',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Inspection',
            ],
            'water_regulations' => [
                'label' => 'Water Regulations / G3',
                'short' => 'Water regs',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'coshh' => [
                'label' => 'COSHH Assessment',
                'short' => 'COSHH',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'dbs' => [
                'label' => 'DBS Check',
                'short' => 'DBS',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'health_safety' => [
                'label' => 'Health & Safety Risk Assessment',
                'short' => 'H&S',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'working_at_height' => [
                'label' => 'Working at Height Assessment',
                'short' => 'Height',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Safety',
            ],
            'equipment_inspection' => [
                'label' => 'Equipment Inspection',
                'short' => 'Equipment',
                'frequency' => 'monthly',
                'lead_days' => 7,
                'task_type' => 'Inspection',
            ],
            'cscs' => [
                'label' => 'CSCS / Competency Cards',
                'short' => 'CSCS',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'food_hygiene' => [
                'label' => 'Food Hygiene',
                'short' => 'Hygiene',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'allergen' => [
                'label' => 'Allergen Review',
                'short' => 'Allergen',
                'frequency' => '6_months',
                'lead_days' => 14,
                'task_type' => 'Compliance',
            ],
            'pest_control' => [
                'label' => 'Pest Control',
                'short' => 'Pest',
                'frequency' => 'quarterly',
                'lead_days' => 7,
                'task_type' => 'Maintenance',
            ],
            'first_aid' => [
                'label' => 'First Aid Review',
                'short' => 'First aid',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'pi_insurance' => [
                'label' => 'Professional Indemnity Insurance',
                'short' => 'PI',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'gdpr' => [
                'label' => 'GDPR / Data Protection',
                'short' => 'GDPR',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'cyber_insurance' => [
                'label' => 'Cyber Insurance',
                'short' => 'Cyber',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'ico_registration' => [
                'label' => 'ICO Registration',
                'short' => 'ICO',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'iso27001' => [
                'label' => 'ISO 27001 / Information Security',
                'short' => 'ISO 27001',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'disaster_recovery' => [
                'label' => 'Disaster Recovery / Backup Test',
                'short' => 'DR',
                'frequency' => 'quarterly',
                'lead_days' => 7,
                'task_type' => 'Task',
            ],
            'aml' => [
                'label' => 'AML Review',
                'short' => 'AML',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'cpd' => [
                'label' => 'CPD / Practising Certificate',
                'short' => 'CPD',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'right_to_work' => [
                'label' => 'Right to Work Review',
                'short' => 'RTW',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'safeguarding' => [
                'label' => 'Safeguarding Policy Review',
                'short' => 'Safeguarding',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Compliance',
            ],
            'accounts' => [
                'label' => 'Annual Accounts / Return',
                'short' => 'Accounts',
                'frequency' => 'annual',
                'lead_days' => 60,
                'task_type' => 'Admin',
            ],
            'machinery_inspection' => [
                'label' => 'Machinery Inspection',
                'short' => 'Machinery',
                'frequency' => 'monthly',
                'lead_days' => 7,
                'task_type' => 'Quality',
            ],
            'iso9001' => [
                'label' => 'ISO 9001 / Quality Review',
                'short' => 'ISO 9001',
                'frequency' => 'annual',
                'lead_days' => 30,
                'task_type' => 'Quality',
            ],
            'rams' => [
                'label' => 'RAMS Review',
                'short' => 'RAMS',
                'frequency' => 'quarterly',
                'lead_days' => 7,
                'task_type' => 'Safety',
            ],
            'ppe_checks' => [
                'label' => 'PPE Checks',
                'short' => 'PPE',
                'frequency' => 'monthly',
                'lead_days' => 7,
                'task_type' => 'Safety',
            ],
            'fire_drill' => [
                'label' => 'Fire Drill',
                'short' => 'Fire drill',
                'frequency' => 'quarterly',
                'lead_days' => 14,
                'task_type' => 'Safety',
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
            'portable_appliance_test' => 'pat_testing',
            'electrical' => 'eicr',
            'electrical_certificate' => 'eicr',
            'electrical_installation' => 'eicr',
            'eicr' => 'eicr',
            'gas' => 'gas_safety',
            'cp12' => 'gas_safety',
            'gas_safe' => 'gas_safety',
            'landlord_gas' => 'gas_safety',
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
            'public_liability' => 'insurance',
            'employers_liability' => 'insurance',
            'professional_indemnity' => 'pi_insurance',
            'pi' => 'pi_insurance',
            'cyber' => 'cyber_insurance',
            'data_protection' => 'gdpr',
            'ico' => 'ico_registration',
            'energy_performance' => 'epc',
            'dbs_check' => 'dbs',
            'disclosure' => 'dbs',
            'iso_27001' => 'iso27001',
            'iso_9001' => 'iso9001',
            'food_safety' => 'food_hygiene',
            'hygiene' => 'food_hygiene',
            'g3' => 'unvented_cylinder',
            'niceic' => 'qualifications',
            'napit' => 'qualifications',
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

    /**
     * High-level groupings used when AI classifies an uploaded certificate.
     *
     * @return array<string, array{label: string, types: list<string>}>
     */
    public static function categories(): array
    {
        return [
            'property_safety' => [
                'label' => 'Property & fire safety',
                'types' => ['gas_safety', 'boiler_service', 'fire_safety', 'fire_alarm', 'emergency_lighting', 'fire_drill', 'legionella', 'asbestos', 'epc'],
            ],
            'electrical' => [
                'label' => 'Electrical',
                'types' => ['eicr', 'pat_testing'],
            ],
            'inspections' => [
                'label' => 'Inspections & equipment',
                'types' => ['inspection', 'equipment_inspection', 'machinery_inspection', 'working_at_height', 'unvented_cylinder', 'water_regulations', 'pest_control'],
            ],
            'insurance_legal' => [
                'label' => 'Insurance & contracts',
                'types' => ['insurance', 'pi_insurance', 'cyber_insurance', 'contract'],
            ],
            'people' => [
                'label' => 'People & safeguarding',
                'types' => ['dbs', 'safeguarding', 'right_to_work', 'first_aid', 'cpd', 'qualifications', 'cscs'],
            ],
            'food' => [
                'label' => 'Food & hospitality',
                'types' => ['food_hygiene', 'allergen'],
            ],
            'health_safety' => [
                'label' => 'Health & safety',
                'types' => ['health_safety', 'coshh', 'rams', 'ppe_checks'],
            ],
            'data_quality' => [
                'label' => 'Data, quality & governance',
                'types' => ['gdpr', 'ico_registration', 'iso27001', 'iso9001', 'aml', 'disaster_recovery', 'accounts'],
            ],
            'other' => [
                'label' => 'Other',
                'types' => ['other'],
            ],
        ];
    }

    public static function categoryFor(string $type): string
    {
        foreach (self::categories() as $id => $category) {
            if (in_array($type, $category['types'], true)) {
                return $id;
            }
        }

        return 'other';
    }

    public static function categoryLabel(string $type): string
    {
        $categoryId = self::categoryFor($type);

        return self::categories()[$categoryId]['label'] ?? 'Other';
    }

    /**
     * Compact catalogue for the OpenAI extraction prompt.
     */
    public static function promptCatalogue(): string
    {
        $lines = [];

        foreach (self::categories() as $category) {
            $lines[] = $category['label'].':';
            foreach ($category['types'] as $type) {
                $lines[] = '- '.$type.': '.self::label($type);
            }
        }

        return implode("\n", $lines);
    }
}
