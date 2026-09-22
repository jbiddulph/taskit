<?php

namespace App\Support;

class PropertyOccupancy
{
    public const OCCUPIED = 'occupied';

    public const VACANT = 'vacant';

    public const MAINTENANCE = 'maintenance';

    public const INACTIVE = 'inactive';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function choices(): array
    {
        return [
            ['value' => self::OCCUPIED, 'label' => 'Occupied'],
            ['value' => self::VACANT, 'label' => 'Vacant'],
            ['value' => self::MAINTENANCE, 'label' => 'Maintenance'],
            ['value' => self::INACTIVE, 'label' => 'Inactive'],
        ];
    }

    public static function label(?string $status): string
    {
        foreach (self::choices() as $choice) {
            if ($choice['value'] === $status) {
                return $choice['label'];
            }
        }

        return $status ? ucfirst($status) : 'Unknown';
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', array_column(self::choices(), 'value'));
    }
}
