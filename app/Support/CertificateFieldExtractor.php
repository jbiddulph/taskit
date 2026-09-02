<?php

namespace App\Support;

class CertificateFieldExtractor
{
    /**
     * Rule-based extraction used when OpenAI is unavailable or as a merge fallback.
     *
     * @return array{
     *     document_type: string|null,
     *     label: string|null,
     *     certificate_number: string|null,
     *     expiry_date: string|null,
     *     issue_date: string|null,
     *     engineer_name: string|null,
     *     address: string|null,
     *     summary: string|null,
     *     suggested_tasks: array<int, array<string, mixed>>,
     *     confidence: float,
     *     source: string
     * }
     */
    public static function fromText(string $text): array
    {
        $type = self::detectType($text);
        $expiresOn = self::firstDate($text, [
            '/(?:valid until|expiry date|expires(?:\s+on)?|next (?:inspection|test|service) due|due date|renewal date|contract (?:ends|expiry))[:\s]+([0-9]{1,2}[\/.\-][0-9]{1,2}[\/.\-][0-9]{2,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{2,4}|[0-9]{4}-[0-9]{2}-[0-9]{2})/i',
            '/(?:certificate expires)[:\s]+([0-9]{1,2}[\/.\-][0-9]{1,2}[\/.\-][0-9]{2,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{2,4})/i',
        ]);
        $issuedOn = self::firstDate($text, [
            '/(?:date of (?:inspection|check|issue|service)|issue date|inspected on|serviced on|dated)[:\s]+([0-9]{1,2}[\/.\-][0-9]{1,2}[\/.\-][0-9]{2,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{2,4}|[0-9]{4}-[0-9]{2}-[0-9]{2})/i',
        ]);

        $numberMatch = [];
        preg_match(
            '/\b(?:certificate|cert|job|policy|contract)\s*(?:no\.?|number|#)[:\s]+([A-Z0-9][A-Z0-9\-\/]{3,})/i',
            $text,
            $numberMatch
        );

        $contractorMatch = [];
        preg_match(
            '/(?:engineer|inspector|issued by|contractor|company|serviced by)[:\s]+([A-Za-z0-9][A-Za-z0-9&.,\' \-]{2,60})/i',
            $text,
            $contractorMatch
        );

        $confidence = 0.35;
        if ($type) {
            $confidence += 0.25;
        }
        if ($expiresOn) {
            $confidence += 0.3;
        }
        if ($issuedOn) {
            $confidence += 0.05;
        }
        if (! empty($numberMatch[1])) {
            $confidence += 0.05;
        }

        $label = $type ? CertificateTypes::label($type) : null;
        $summary = $label
            ? ($expiresOn ? "{$label} — expires {$expiresOn}." : "{$label} document detected.")
            : ($expiresOn ? "Document expires {$expiresOn}." : null);

        return [
            'document_type' => $type,
            'label' => $label,
            'certificate_number' => $numberMatch[1] ?? null,
            'expiry_date' => $expiresOn,
            'issue_date' => $issuedOn,
            'engineer_name' => isset($contractorMatch[1]) ? trim($contractorMatch[1], " \t\n\r\0\x0B.,") : null,
            'address' => null,
            'summary' => $summary,
            'suggested_tasks' => [],
            'confidence' => min($confidence, 0.95),
            'source' => 'rules',
        ];
    }

    public static function hasUsefulFields(array $extracted): bool
    {
        return filled($extracted['document_type'] ?? null)
            || filled($extracted['expiry_date'] ?? null)
            || filled($extracted['issue_date'] ?? null)
            || filled($extracted['certificate_number'] ?? null)
            || filled($extracted['label'] ?? null);
    }

    public static function merge(?array $primary, array $fallback): array
    {
        if (! $primary) {
            return $fallback;
        }

        $merged = $fallback;
        foreach (['document_type', 'label', 'certificate_number', 'expiry_date', 'issue_date', 'engineer_name', 'address', 'summary'] as $key) {
            if (filled($primary[$key] ?? null)) {
                $merged[$key] = $primary[$key];
            }
        }

        if (! empty($primary['suggested_tasks']) && is_array($primary['suggested_tasks'])) {
            $merged['suggested_tasks'] = $primary['suggested_tasks'];
        }

        $merged['confidence'] = max(
            (float) ($primary['confidence'] ?? 0),
            (float) ($fallback['confidence'] ?? 0)
        );
        $merged['source'] = $primary['source'] ?? 'openai';

        if (! empty($primary['document_type'])) {
            $merged['document_type'] = CertificateTypes::normalize($primary['document_type']);
            if (empty($merged['label'])) {
                $merged['label'] = CertificateTypes::label($merged['document_type']);
            }
        }

        return $merged;
    }

    protected static function detectType(string $text): ?string
    {
        $hints = [
            'gas_safety' => ['/gas safety/i', '/\bcp12\b/i', '/landlord.?s? gas/i', '/gas safe/i'],
            'boiler_service' => ['/boiler (?:service|servicing|annual service)/i', '/gas boiler/i'],
            'eicr' => ['/\beicr\b/i', '/electrical installation condition/i', '/bs\s*7671/i'],
            'pat_testing' => ['/\bpat\b/i', '/portable appliance/i'],
            'fire_safety' => ['/fire (?:risk )?assessment/i', '/\bfra\b/i', '/fire safety/i'],
            'fire_alarm' => ['/fire alarm/i', '/bs\s*5839/i', '/fire detection/i'],
            'emergency_lighting' => ['/emergency lighting/i', '/bs\s*5266/i'],
            'insurance' => ['/insurance/i', '/policy schedule/i', '/cover note/i'],
            'contract' => ['/tenancy agreement/i', '/maintenance contract/i', '/service contract/i', '/contract (?:term|expiry|ends)/i'],
            'legionella' => ['/legionella/i'],
            'asbestos' => ['/asbestos/i'],
            'epc' => ['/\bepc\b/i', '/energy performance/i'],
            'inspection' => ['/inspection (report|certificate)/i', '/\bmot\b/i', '/service inspection/i'],
        ];

        foreach ($hints as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $patterns
     */
    protected static function firstDate(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match) && ! empty($match[1])) {
                $parsed = self::parseUkDate($match[1]);
                if ($parsed) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    public static function parseUkDate(string $raw): ?string
    {
        $value = trim(preg_replace('/(\d+)(st|nd|rd|th)/i', '$1', $raw) ?? $raw);

        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2,4})$/', $value, $numeric)) {
            return self::toIsoDate($numeric[1], $numeric[2], $numeric[3]);
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $iso)) {
            return self::toIsoDate($iso[3], $iso[2], $iso[1]);
        }

        $months = [
            'jan' => '01', 'january' => '01', 'feb' => '02', 'february' => '02',
            'mar' => '03', 'march' => '03', 'apr' => '04', 'april' => '04',
            'may' => '05', 'jun' => '06', 'june' => '06', 'jul' => '07', 'july' => '07',
            'aug' => '08', 'august' => '08', 'sep' => '09', 'sept' => '09', 'september' => '09',
            'oct' => '10', 'october' => '10', 'nov' => '11', 'november' => '11',
            'dec' => '12', 'december' => '12',
        ];

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{2,4})$/', $value, $named)) {
            $month = $months[strtolower($named[2])] ?? null;
            if (! $month) {
                return null;
            }

            return self::toIsoDate($named[1], $month, $named[3]);
        }

        if (preg_match('/^([A-Za-z]{3,9})\s+(\d{1,2}),?\s+(\d{2,4})$/', $value, $namedReverse)) {
            $month = $months[strtolower($namedReverse[1])] ?? null;
            if (! $month) {
                return null;
            }

            return self::toIsoDate($namedReverse[2], $month, $namedReverse[3]);
        }

        return null;
    }

    protected static function toIsoDate(string $day, string $month, string $year): ?string
    {
        $y = strlen($year) === 2
            ? ((int) $year > 50 ? '19'.$year : '20'.$year)
            : $year;
        $d = (int) $day;
        $m = (int) $month;
        $yi = (int) $y;

        if (! $d || ! $m || ! $yi || $d > 31 || $m > 12) {
            return null;
        }

        if (! checkdate($m, $d, $yi)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $yi, $m, $d);
    }
}
