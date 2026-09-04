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
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'legionella', 'label' => 'Legionella Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Buildings / Public Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'electricians' => [
        ['type' => 'eicr', 'label' => 'Electrical Installation Condition Report (EICR)', 'frequency' => '5_years', 'lead_days' => 60, 'task_type' => 'Inspection'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'emergency_lighting', 'label' => 'Emergency Lighting Test', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'fire_alarm', 'label' => 'Fire Alarm Inspection', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Inspection'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'qualifications', 'label' => 'NICEIC / NAPIT Registration', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'rams', 'label' => 'RAMS Review', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'contract', 'label' => 'Maintenance Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Job'],
    ],

    'plumbers' => [
        ['type' => 'gas_safety', 'label' => 'Gas Safe Registration / CP12', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'boiler_service', 'label' => 'Boiler Servicing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Repair'],
        ['type' => 'unvented_cylinder', 'label' => 'Unvented Cylinder Certificate', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'water_regulations', 'label' => 'Water Regulations / G3 Competency', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'rams', 'label' => 'RAMS Review', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'contract', 'label' => 'Service Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Job'],
    ],

    'cleaning-companies' => [
        ['type' => 'coshh', 'label' => 'COSHH Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'dbs', 'label' => 'DBS Checks', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'Equipment PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'contract', 'label' => 'Client Cleaning Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Job'],
        ['type' => 'health_safety', 'label' => 'Health & Safety Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
    ],

    'roofers' => [
        ['type' => 'rams', 'label' => 'RAMS Review', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Survey'],
        ['type' => 'working_at_height', 'label' => 'Working at Height Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Survey'],
        ['type' => 'equipment_inspection', 'label' => 'Ladder / Scaffold Equipment Check', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Survey'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Warranty'],
        ['type' => 'cscs', 'label' => 'CSCS / Competency Cards', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Warranty'],
        ['type' => 'contract', 'label' => 'Warranty / Client Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Warranty'],
    ],

    'landscapers' => [
        ['type' => 'equipment_inspection', 'label' => 'Machinery & Equipment Inspection', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Maintenance'],
        ['type' => 'pat_testing', 'label' => 'Electrical Tool PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'coshh', 'label' => 'COSHH / Pesticide Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Job'],
        ['type' => 'rams', 'label' => 'RAMS Review', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Job'],
        ['type' => 'contract', 'label' => 'Maintenance Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Job'],
    ],

    'restaurants' => [
        ['type' => 'food_hygiene', 'label' => 'Food Hygiene Rating / Inspection', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'allergen', 'label' => 'Allergen Review', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Compliance'],
        ['type' => 'pest_control', 'label' => 'Pest Control Visit', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Maintenance'],
        ['type' => 'gas_safety', 'label' => 'Kitchen Gas Safety', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'retail' => [
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'health_safety', 'label' => 'Health & Safety Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'first_aid', 'label' => 'First Aid Kit / Trained Staff Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'emergency_lighting', 'label' => 'Emergency Lighting', 'frequency' => '6_months', 'lead_days' => 14, 'task_type' => 'Maintenance'],
    ],

    'marketing-agencies' => [
        ['type' => 'pi_insurance', 'label' => 'Professional Indemnity Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'gdpr', 'label' => 'GDPR / Data Protection Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'cyber_insurance', 'label' => 'Cyber Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Client Retainer Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Client'],
        ['type' => 'ico_registration', 'label' => 'ICO Registration', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'software-development' => [
        ['type' => 'iso27001', 'label' => 'ISO 27001 / Information Security Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'gdpr', 'label' => 'GDPR / Data Protection Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'cyber_insurance', 'label' => 'Cyber Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'disaster_recovery', 'label' => 'Disaster Recovery / Backup Test', 'frequency' => 'quarterly', 'lead_days' => 7, 'task_type' => 'Task'],
        ['type' => 'contract', 'label' => 'Client / Vendor Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Task'],
        ['type' => 'pi_insurance', 'label' => 'Professional Indemnity Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'accountants' => [
        ['type' => 'pi_insurance', 'label' => 'Professional Indemnity Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'aml', 'label' => 'AML / Client Due Diligence Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'gdpr', 'label' => 'GDPR / Data Protection Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'cpd', 'label' => 'CPD / Practising Certificate', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'ico_registration', 'label' => 'ICO Registration', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Engagement Letters / Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Client'],
    ],

    'recruitment' => [
        ['type' => 'gdpr', 'label' => 'GDPR / Candidate Data Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'right_to_work', 'label' => 'Right to Work Process Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'PI / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Client Terms / Contracts', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Client'],
        ['type' => 'ico_registration', 'label' => 'ICO Registration', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'dbs', 'label' => 'DBS Process Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'schools' => [
        ['type' => 'safeguarding', 'label' => 'Safeguarding Policy Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'dbs', 'label' => 'DBS / Staff Vetting Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'fire_drill', 'label' => 'Fire Drill', 'frequency' => 'quarterly', 'lead_days' => 14, 'task_type' => 'Compliance'],
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'first_aid', 'label' => 'First Aid Provision Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Admin'],
        ['type' => 'asbestos', 'label' => 'Asbestos Management Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'legionella', 'label' => 'Legionella Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'charities' => [
        ['type' => 'safeguarding', 'label' => 'Safeguarding Policy Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'dbs', 'label' => 'DBS / Volunteer Checks', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'insurance', 'label' => 'Public / Trustee Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'accounts', 'label' => 'Annual Accounts / Charity Commission Return', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Admin'],
        ['type' => 'gdpr', 'label' => 'GDPR / Donor Data Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
    ],

    'manufacturing' => [
        ['type' => 'ppe_checks', 'label' => 'PPE Checks', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Safety'],
        ['type' => 'coshh', 'label' => 'COSHH Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
        ['type' => 'machinery_inspection', 'label' => 'Machinery Inspection', 'frequency' => 'monthly', 'lead_days' => 7, 'task_type' => 'Quality'],
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Maintenance'],
        ['type' => 'iso9001', 'label' => 'ISO 9001 / Quality Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Quality'],
        ['type' => 'insurance', 'label' => 'Employers / Public Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Safety'],
    ],

    'general' => [
        ['type' => 'insurance', 'label' => 'Public / Employers Liability Insurance', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'fire_safety', 'label' => 'Fire Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'pat_testing', 'label' => 'PAT Testing', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Inspection'],
        ['type' => 'health_safety', 'label' => 'Health & Safety Risk Assessment', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'gdpr', 'label' => 'GDPR / Data Protection Review', 'frequency' => 'annual', 'lead_days' => 30, 'task_type' => 'Compliance'],
        ['type' => 'contract', 'label' => 'Contracts / SLAs', 'frequency' => 'annual', 'lead_days' => 60, 'task_type' => 'Task'],
    ],
];
