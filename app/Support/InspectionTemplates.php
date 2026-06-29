<?php

namespace App\Support;

class InspectionTemplates
{
    public static function forIndustry(?string $industry): array
    {
        $industry = Industries::resolve($industry);
        $templates = config("inspection_templates.{$industry}", []);

        if ($templates === []) {
            return config('inspection_templates.general', []);
        }

        return array_merge(
            config('inspection_templates.general', []),
            $templates,
        );
    }

    public static function get(?string $industry, string $key): ?array
    {
        $templates = self::forIndustry($industry);

        return $templates[$key] ?? null;
    }

    public static function choices(?string $industry): array
    {
        return collect(self::forIndustry($industry))
            ->map(fn (array $template, string $key) => [
                'key' => $key,
                'label' => $template['label'] ?? $key,
                'compliance_type' => $template['compliance_type'] ?? null,
                'item_count' => count($template['items'] ?? []),
            ])
            ->values()
            ->all();
    }

    public static function isValid(?string $industry, string $key): bool
    {
        return self::get($industry, $key) !== null;
    }
}
