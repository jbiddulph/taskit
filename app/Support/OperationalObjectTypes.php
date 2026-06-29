<?php

namespace App\Support;

class OperationalObjectTypes
{
    public static function list(): array
    {
        return [
            'property' => 'Property',
            'building' => 'Building',
            'unit' => 'Unit / Flat / Room',
            'site' => 'Site',
            'vehicle' => 'Vehicle',
            'equipment' => 'Equipment',
            'resident' => 'Resident',
            'customer' => 'Customer',
        ];
    }

    public static function label(string $type): string
    {
        return self::list()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::list());
    }

    public static function choices(): array
    {
        return collect(self::list())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    public static function validationRule(): string
    {
        $types = implode(',', array_keys(self::list()));

        return "required|string|in:{$types}";
    }
}
