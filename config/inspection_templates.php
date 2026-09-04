<?php

return [
    /*
    | Checklist item types: pass_fail_na | text | photos
    */
    'property-management' => [
        'fire_alarm' => [
            'label' => 'Fire Alarm Inspection',
            'compliance_type' => 'fire_alarm',
            'items' => [
                ['key' => 'panel_tested', 'label' => 'Control panel tested and operational', 'type' => 'pass_fail_na'],
                ['key' => 'call_points', 'label' => 'Manual call points checked', 'type' => 'pass_fail_na'],
                ['key' => 'sounders', 'label' => 'Sounders audible throughout building', 'type' => 'pass_fail_na'],
                ['key' => 'log_book', 'label' => 'Log book updated', 'type' => 'pass_fail_na'],
                ['key' => 'defects', 'label' => 'Defects or remedial actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Inspection photos', 'type' => 'photos'],
            ],
        ],
        'room_inspection' => [
            'label' => 'Property / Room Inspection',
            'compliance_type' => null,
            'items' => [
                ['key' => 'general_condition', 'label' => 'General condition acceptable', 'type' => 'pass_fail_na'],
                ['key' => 'damp_mould', 'label' => 'No signs of damp or mould', 'type' => 'pass_fail_na'],
                ['key' => 'windows_doors', 'label' => 'Windows and doors secure', 'type' => 'pass_fail_na'],
                ['key' => 'smoke_co', 'label' => 'Smoke / CO alarms present and tested', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Inspector notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'construction' => [
        'site_safety' => [
            'label' => 'Site Safety Walk',
            'compliance_type' => 'rams',
            'items' => [
                ['key' => 'ppe', 'label' => 'PPE worn correctly on site', 'type' => 'pass_fail_na'],
                ['key' => 'housekeeping', 'label' => 'Site housekeeping acceptable', 'type' => 'pass_fail_na'],
                ['key' => 'scaffold', 'label' => 'Scaffold / access equipment inspected', 'type' => 'pass_fail_na'],
                ['key' => 'hazards', 'label' => 'Hazards identified and controlled', 'type' => 'pass_fail_na'],
                ['key' => 'actions', 'label' => 'Actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Site photos', 'type' => 'photos'],
            ],
        ],
        'scaffold_inspection' => [
            'label' => 'Scaffold Inspection',
            'compliance_type' => 'scaffold_inspection',
            'items' => [
                ['key' => 'tags', 'label' => 'Scaffold tags valid and visible', 'type' => 'pass_fail_na'],
                ['key' => 'base', 'label' => 'Base plates and sole boards in place', 'type' => 'pass_fail_na'],
                ['key' => 'guardrails', 'label' => 'Guardrails and toe boards fitted', 'type' => 'pass_fail_na'],
                ['key' => 'access', 'label' => 'Safe access provided', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'care-agencies' => [
        'room_inspection' => [
            'label' => 'Resident Room Inspection',
            'compliance_type' => 'room_inspection',
            'items' => [
                ['key' => 'cleanliness', 'label' => 'Room clean and tidy', 'type' => 'pass_fail_na'],
                ['key' => 'equipment', 'label' => 'Equipment in good working order', 'type' => 'pass_fail_na'],
                ['key' => 'call_bell', 'label' => 'Call bell working', 'type' => 'pass_fail_na'],
                ['key' => 'safety', 'label' => 'No immediate safety concerns', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Care notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'hotels' => [
        'room_inspection' => [
            'label' => 'Guest Room Inspection',
            'compliance_type' => null,
            'items' => [
                ['key' => 'cleanliness', 'label' => 'Room meets cleanliness standard', 'type' => 'pass_fail_na'],
                ['key' => 'maintenance', 'label' => 'No maintenance issues observed', 'type' => 'pass_fail_na'],
                ['key' => 'fire_door', 'label' => 'Fire door closes correctly', 'type' => 'pass_fail_na'],
                ['key' => 'minibar', 'label' => 'Minibar / amenities checked', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Housekeeping notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'estate-agents' => [
        'inventory' => [
            'label' => 'Inventory / Check-in Inspection',
            'compliance_type' => 'inventory',
            'items' => [
                ['key' => 'keys', 'label' => 'Keys and access devices accounted for', 'type' => 'pass_fail_na'],
                ['key' => 'condition', 'label' => 'Property condition matches inventory', 'type' => 'pass_fail_na'],
                ['key' => 'meters', 'label' => 'Meter readings recorded', 'type' => 'pass_fail_na'],
                ['key' => 'alarms', 'label' => 'Smoke / CO alarms present and tested', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Inventory notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'electricians' => [
        'eicr_inspection' => [
            'label' => 'Electrical Installation Inspection',
            'compliance_type' => 'eicr',
            'items' => [
                ['key' => 'consumer_unit', 'label' => 'Consumer unit accessible and labelled', 'type' => 'pass_fail_na'],
                ['key' => 'rcd', 'label' => 'RCD / RCBO protection in place', 'type' => 'pass_fail_na'],
                ['key' => 'bonding', 'label' => 'Earthing and bonding satisfactory', 'type' => 'pass_fail_na'],
                ['key' => 'accessories', 'label' => 'Sockets and switches in good condition', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Observations / C1–C3 comments', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'plumbers' => [
        'gas_safety_inspection' => [
            'label' => 'Gas Safety Inspection',
            'compliance_type' => 'gas_safety',
            'items' => [
                ['key' => 'appliance', 'label' => 'Appliances operating safely', 'type' => 'pass_fail_na'],
                ['key' => 'flue', 'label' => 'Flue and ventilation satisfactory', 'type' => 'pass_fail_na'],
                ['key' => 'pipework', 'label' => 'Gas pipework visually sound', 'type' => 'pass_fail_na'],
                ['key' => 'co_alarm', 'label' => 'CO alarm present and working', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Engineer notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'cleaning-companies' => [
        'site_clean_inspection' => [
            'label' => 'Site Clean Inspection',
            'compliance_type' => null,
            'items' => [
                ['key' => 'floors', 'label' => 'Floors cleaned to specification', 'type' => 'pass_fail_na'],
                ['key' => 'washrooms', 'label' => 'Washrooms restocked and sanitary', 'type' => 'pass_fail_na'],
                ['key' => 'waste', 'label' => 'Waste removed and bins clean', 'type' => 'pass_fail_na'],
                ['key' => 'chemicals', 'label' => 'Chemicals stored and labelled (COSHH)', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Supervisor notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'roofers' => [
        'roof_survey' => [
            'label' => 'Roof Survey',
            'compliance_type' => 'working_at_height',
            'items' => [
                ['key' => 'coverings', 'label' => 'Coverings sound with no missing tiles', 'type' => 'pass_fail_na'],
                ['key' => 'flashings', 'label' => 'Flashings and valleys intact', 'type' => 'pass_fail_na'],
                ['key' => 'gutters', 'label' => 'Gutters and downpipes clear', 'type' => 'pass_fail_na'],
                ['key' => 'access', 'label' => 'Safe access and edge protection in place', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Survey notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'landscapers' => [
        'grounds_inspection' => [
            'label' => 'Grounds / Site Inspection',
            'compliance_type' => 'equipment_inspection',
            'items' => [
                ['key' => 'machinery', 'label' => 'Machinery guards and condition acceptable', 'type' => 'pass_fail_na'],
                ['key' => 'paths', 'label' => 'Paths and hardstanding safe', 'type' => 'pass_fail_na'],
                ['key' => 'chemicals', 'label' => 'Chemicals stored correctly', 'type' => 'pass_fail_na'],
                ['key' => 'waste', 'label' => 'Green waste managed', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'facilities-management' => [
        'building_walk' => [
            'label' => 'Building Walk-round',
            'compliance_type' => 'fire_safety',
            'items' => [
                ['key' => 'exits', 'label' => 'Fire exits clear and unlocked', 'type' => 'pass_fail_na'],
                ['key' => 'extinguishers', 'label' => 'Extinguishers in date and accessible', 'type' => 'pass_fail_na'],
                ['key' => 'plant', 'label' => 'Plant rooms tidy and no leaks', 'type' => 'pass_fail_na'],
                ['key' => 'lighting', 'label' => 'Emergency lighting operational', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'restaurants' => [
        'kitchen_hygiene' => [
            'label' => 'Kitchen Hygiene Inspection',
            'compliance_type' => 'food_hygiene',
            'items' => [
                ['key' => 'temps', 'label' => 'Fridge / freezer temperatures recorded', 'type' => 'pass_fail_na'],
                ['key' => 'cleanliness', 'label' => 'Prep surfaces and equipment clean', 'type' => 'pass_fail_na'],
                ['key' => 'pest', 'label' => 'No signs of pest activity', 'type' => 'pass_fail_na'],
                ['key' => 'allergen', 'label' => 'Allergen information available', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Hygiene notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'retail' => [
        'store_safety' => [
            'label' => 'Store Safety Walk',
            'compliance_type' => 'health_safety',
            'items' => [
                ['key' => 'aisles', 'label' => 'Aisles and exits clear', 'type' => 'pass_fail_na'],
                ['key' => 'stock', 'label' => 'Stock stored safely (no overloading)', 'type' => 'pass_fail_na'],
                ['key' => 'first_aid', 'label' => 'First aid kit stocked', 'type' => 'pass_fail_na'],
                ['key' => 'fire', 'label' => 'Fire equipment accessible', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'marketing-agencies' => [
        'office_compliance' => [
            'label' => 'Office Compliance Check',
            'compliance_type' => 'gdpr',
            'items' => [
                ['key' => 'access', 'label' => 'Visitor / access control in place', 'type' => 'pass_fail_na'],
                ['key' => 'data', 'label' => 'Client data stored securely', 'type' => 'pass_fail_na'],
                ['key' => 'desks', 'label' => 'Clear-desk / confidential waste followed', 'type' => 'pass_fail_na'],
                ['key' => 'exits', 'label' => 'Fire exits clear', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'software-development' => [
        'security_review' => [
            'label' => 'Information Security Review',
            'compliance_type' => 'iso27001',
            'items' => [
                ['key' => 'access', 'label' => 'User access reviews completed', 'type' => 'pass_fail_na'],
                ['key' => 'backups', 'label' => 'Backups tested and recoverable', 'type' => 'pass_fail_na'],
                ['key' => 'devices', 'label' => 'Devices encrypted and patched', 'type' => 'pass_fail_na'],
                ['key' => 'incidents', 'label' => 'Incident log reviewed', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Findings', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Evidence photos / screenshots', 'type' => 'photos'],
            ],
        ],
    ],

    'accountants' => [
        'practice_compliance' => [
            'label' => 'Practice Compliance Check',
            'compliance_type' => 'aml',
            'items' => [
                ['key' => 'aml', 'label' => 'AML files complete for sampled clients', 'type' => 'pass_fail_na'],
                ['key' => 'engagement', 'label' => 'Engagement letters in date', 'type' => 'pass_fail_na'],
                ['key' => 'data', 'label' => 'Client data stored securely', 'type' => 'pass_fail_na'],
                ['key' => 'cpd', 'label' => 'CPD records up to date', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Evidence', 'type' => 'photos'],
            ],
        ],
    ],

    'recruitment' => [
        'process_audit' => [
            'label' => 'Recruitment Process Audit',
            'compliance_type' => 'gdpr',
            'items' => [
                ['key' => 'consent', 'label' => 'Candidate consent / privacy notices in place', 'type' => 'pass_fail_na'],
                ['key' => 'rtw', 'label' => 'Right to work checks documented', 'type' => 'pass_fail_na'],
                ['key' => 'retention', 'label' => 'Data retention policy followed', 'type' => 'pass_fail_na'],
                ['key' => 'contracts', 'label' => 'Client terms on file', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Evidence', 'type' => 'photos'],
            ],
        ],
    ],

    'schools' => [
        'safeguarding_walk' => [
            'label' => 'Safeguarding / Site Walk',
            'compliance_type' => 'safeguarding',
            'items' => [
                ['key' => 'visitors', 'label' => 'Visitor signing-in and ID checks in use', 'type' => 'pass_fail_na'],
                ['key' => 'exits', 'label' => 'Fire exits and assembly points clear', 'type' => 'pass_fail_na'],
                ['key' => 'first_aid', 'label' => 'First aid kits accessible', 'type' => 'pass_fail_na'],
                ['key' => 'site', 'label' => 'Play / outdoor areas safe', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'charities' => [
        'venue_check' => [
            'label' => 'Venue / Office Check',
            'compliance_type' => 'safeguarding',
            'items' => [
                ['key' => 'safeguarding', 'label' => 'Safeguarding poster / contacts displayed', 'type' => 'pass_fail_na'],
                ['key' => 'exits', 'label' => 'Fire exits clear', 'type' => 'pass_fail_na'],
                ['key' => 'first_aid', 'label' => 'First aid kit stocked', 'type' => 'pass_fail_na'],
                ['key' => 'volunteers', 'label' => 'Volunteer induction records available', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Notes', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'manufacturing' => [
        'production_safety' => [
            'label' => 'Production Safety Walk',
            'compliance_type' => 'ppe_checks',
            'items' => [
                ['key' => 'ppe', 'label' => 'PPE worn in designated areas', 'type' => 'pass_fail_na'],
                ['key' => 'guards', 'label' => 'Machine guards in place', 'type' => 'pass_fail_na'],
                ['key' => 'housekeeping', 'label' => 'Housekeeping / walkways clear', 'type' => 'pass_fail_na'],
                ['key' => 'coshh', 'label' => 'Hazardous substances labelled and stored', 'type' => 'pass_fail_na'],
                ['key' => 'notes', 'label' => 'Actions required', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],

    'general' => [
        'general_inspection' => [
            'label' => 'General Inspection',
            'compliance_type' => null,
            'items' => [
                ['key' => 'overall', 'label' => 'Overall condition acceptable', 'type' => 'pass_fail_na'],
                ['key' => 'issues', 'label' => 'Issues found', 'type' => 'text'],
                ['key' => 'actions', 'label' => 'Recommended actions', 'type' => 'text'],
                ['key' => 'photos', 'label' => 'Photos', 'type' => 'photos'],
            ],
        ],
    ],
];
