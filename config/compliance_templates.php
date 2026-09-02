<?php

return [
    /*
    | Industry compliance templates — seeded onto operational objects when requested.
    | frequency: annual | 6_months | monthly | quarterly | 2_years | 10_years
    */
    'property-management' => [
        ['type' => 'gas_safety', 'label' => 'Gas Safety Certificate', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'boiler_service', 'label' => 'Boiler Servicing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'fire_safety', 'label' => 'Fire Safety Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
        ['type' => 'fire_alarm', 'label' => 'Fire Alarm Inspection', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'emergency_lighting', 'label' => 'Emergency Lighting', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'legionella', 'label' => 'Legionella Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'epc', 'label' => 'EPC', 'frequency' => '10_years', 'lead_days' => 90, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Buildings Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Lease'],
    ],

    'estate-agents' => [
        ['type' => 'epc', 'label' => 'EPC', 'frequency' => '10_years', 'lead_days' => 90, 'task_type' => 'Compliance'],
        ['type' => 'gas_safety', 'label' => 'Gas Safety', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'boiler_service', 'label' => 'Boiler Servicing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'eicr', 'label' => 'Electrical Certificate (EICR)', 'frequency' => '5_years', 'lead_days' => 60, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'fire_safety', 'label' => 'Fire Safety', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
        ['type' => 'smoke_alarm', 'label' => 'Smoke Alarm Check', 'frequency' => 'annual', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'inventory', 'label' => 'Inventory', 'frequency' => 'annual', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'contract', 'label' => 'Tenancy / Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Lease'],
        ['type' => 'right_to_rent', 'label' => 'Right to Rent', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'deposit_protection', 'label' => 'Deposit Protection', 'frequency' => 'annual', 'lead_days' => 14, 'task_type' => 'Compliance'],
    ],

    'construction' => [
        ['type' => 'rams', 'label' => 'RAMS Review', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'ppe_checks', 'label' => 'PPE Checks', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'toolbox_talk', 'label' => 'Toolbox Talk', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'scaffold_inspection', 'label' => 'Scaffold Inspection', 'frequency' => 'weekly', 'lead_days' => 3, 'task_type' => 'Inspection'],
        ['type' => 'site_induction', 'label' => 'Site Induction', 'frequency' => 'annual', 'lead_days' => 14, 'task_type' => 'Safety'],
        ['type' => 'equipment_inspection', 'label' => 'Equipment Inspection', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Inspection'],
    ],

    'facilities-management' => [
        ['type' => 'gas_safety', 'label' => 'Gas Safety Certificate', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'boiler_service', 'label' => 'Boiler Servicing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'fire_safety', 'label' => 'Fire Safety Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
        ['type' => 'fire_alarm', 'label' => 'Fire Alarm Inspection', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'emergency_lighting', 'label' => 'Emergency Lighting', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'legionella', 'label' => 'Legionella Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Service Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Lease'],
    ],

    'care-agencies' => [
        ['type' => 'monthly_review', 'label' => 'Monthly Review', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Assessment'],
        ['type' => 'room_inspection', 'label' => 'Room Inspection', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Inspection'],
        ['type' => 'equipment_service', 'label' => 'Equipment Servicing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'fire_drill', 'label' => 'Fire Drill', 'frequency' => 'quarterly', 'lead_days' => 14, 'task_type' => 'Safety'],
    ],

    'hotels' => [
        ['type' => 'deep_clean', 'label' => 'Deep Clean', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Deep Clean'],
        ['type' => 'mattress_rotation', 'label' => 'Mattress Rotation', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Housekeeping'],
        ['type' => 'fire_door', 'label' => 'Fire Door Inspection', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'air_conditioning', 'label' => 'Air Conditioning Service', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
    ],
];
