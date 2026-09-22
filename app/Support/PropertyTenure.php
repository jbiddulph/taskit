<?php

namespace App\Support;

class PropertyTenure
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function choices(): array
    {
        return [
            ['value' => 'freehold', 'label' => 'Freehold'],
            ['value' => 'leasehold', 'label' => 'Leasehold'],
            ['value' => 'commonhold', 'label' => 'Commonhold'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    public static function label(?string $tenure): string
    {
        foreach (self::choices() as $choice) {
            if ($choice['value'] === $tenure) {
                return $choice['label'];
            }
        }

        return $tenure ? ucfirst($tenure) : '—';
    }

    public static function validationRule(): string
    {
        return 'nullable|in:'.implode(',', array_column(self::choices(), 'value'));
    }
}
