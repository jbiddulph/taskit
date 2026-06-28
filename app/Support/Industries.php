<?php

namespace App\Support;

class Industries
{
    public static function default(): string
    {
        return config('industries.default', 'general');
    }

    public static function list(): array
    {
        return config('industries.list', []);
    }

    public static function choices(): array
    {
        return collect(self::list())
            ->map(fn (string $label, string $slug) => ['value' => $slug, 'label' => $label])
            ->values()
            ->all();
    }

    public static function label(string $slug): string
    {
        return self::list()[$slug] ?? ucfirst(str_replace('-', ' ', $slug));
    }

    public static function isValid(string $slug): bool
    {
        return array_key_exists($slug, self::list());
    }

    public static function resolve(?string $slug): string
    {
        if ($slug && self::isValid($slug)) {
            return $slug;
        }

        return self::default();
    }

    public static function typesFor(?string $slug): array
    {
        $slug = self::resolve($slug);
        $types = config("industries.industry_types.{$slug}");

        if (! is_array($types) || $types === []) {
            return config('industries.industry_types.'.self::default(), ['Task', 'Other']);
        }

        return $types;
    }

    public static function allTypeValues(): array
    {
        return array_keys(config('industries.types', []));
    }

    public static function typeIconMap(): array
    {
        $map = [];

        foreach (config('industries.types', []) as $type => $meta) {
            $map[$type] = $meta['icon'] ?? 'Circle';
        }

        return $map;
    }

    public static function typeOptionsFor(?string $slug): array
    {
        $icons = self::typeIconMap();

        return collect(self::typesFor($slug))
            ->map(fn (string $type) => [
                'value' => $type,
                'icon' => $icons[$type] ?? 'Circle',
                'label' => $type,
            ])
            ->values()
            ->all();
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, config('industries.types', []));
    }

    public static function validationRule(): string
    {
        return 'nullable|string|max:50|not_in:Other';
    }

    public static function requiredValidationRule(): string
    {
        return 'required|string|max:50|not_in:Other';
    }
}
