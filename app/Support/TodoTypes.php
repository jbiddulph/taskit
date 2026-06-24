<?php

namespace App\Support;

class TodoTypes
{
    public const ALL = [
        'Bug',
        'Feature',
        'Task',
        'Story',
        'Epic',
        'Property',
        'Lead',
        'Viewing',
        'Offer',
        'Maintenance',
        'Compliance',
        'Other',
    ];

    public static function validationRule(): string
    {
        return 'nullable|in:'.implode(',', self::ALL);
    }

    public static function requiredValidationRule(): string
    {
        return 'required|in:'.implode(',', self::ALL);
    }
}
