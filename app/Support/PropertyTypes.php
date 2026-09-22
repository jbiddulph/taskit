<?php

namespace App\Support;

class PropertyTypes
{
    /**
     * Dwelling / unit classification (distinct from OperationalObject::type).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function choices(): array
    {
        return [
            ['value' => 'flat', 'label' => 'Flat / Apartment'],
            ['value' => 'house', 'label' => 'House'],
            ['value' => 'bungalow', 'label' => 'Bungalow'],
            ['value' => 'maisonette', 'label' => 'Maisonette'],
            ['value' => 'studio', 'label' => 'Studio'],
            ['value' => 'hmo', 'label' => 'HMO'],
            ['value' => 'commercial', 'label' => 'Commercial'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    public static function label(?string $type): string
    {
        foreach (self::choices() as $choice) {
            if ($choice['value'] === $type) {
                return $choice['label'];
            }
        }

        return $type ? ucfirst($type) : '—';
    }

    public static function validationRule(): string
    {
        return 'nullable|in:'.implode(',', array_column(self::choices(), 'value'));
    }
}
