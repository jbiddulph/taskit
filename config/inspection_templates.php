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
