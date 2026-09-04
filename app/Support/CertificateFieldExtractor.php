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
        $text = self::normalizeText($text);
        $dateToken = '([0-9]{1,2}[\/.\-][0-9]{1,2}[\/.\-][0-9]{2,4}|[0-9]{1,2}\s+[A-Za-z]{3,9}\s+[0-9]{2,4}|[0-9]{4}-[0-9]{2}-[0-9]{2})';

        $type = self::detectType($text);
        $expiresOn = self::firstDate($text, [
            '/(?:valid until|expiry date|expires(?:\s+on)?|certificate expires)[:\s]+'.$dateToken.'/i',
            '/(?:next (?:inspection|test|service|review|check) due|next review recommended|next review|due date|next due)[:\s]+'.$dateToken.'/i',
            '/(?:renewal date|renew by|contract (?:ends|expiry)|cover (?:ends|expiry))[:\s]+'.$dateToken.'/i',
        ]);
        $issuedOn = self::firstDate($text, [
            '/(?:date of (?:inspection|check|issue|service)|(?:inspection|service|test|issue) date|issued on|inspected on|serviced on|dated)[:\s]+'.$dateToken.'/i',
        ]);
        $renewalOn = self::firstDate($text, [
            '/(?:renewal date|renew by|next renewal|next (?:inspection|test|service|review|check) due|next review recommended|next review)[:\s]+'.$dateToken.'/i',
        ]);

        $numberMatch = [];
        preg_match(
            '/\b(?:certificate|cert|job|policy|contract|registration|reference|report)\s*(?:no\.?|number|#)[:\s]+([A-Z0-9][A-Z0-9\-\/]{3,})/i',
            $text,
            $numberMatch
        );

        $engineer = self::extractEngineer($text);
        $issuer = self::extractIssuer($text);
        $address = self::extractAddress($text);
        $result = self::detectResult($text);
        $findings = self::extractFindings($text);

        $confidence = 0.35;
        if ($type) {
            $confidence += 0.25;
        }
        if ($expiresOn) {
            $confidence += 0.3;
        } elseif ($renewalOn) {
            $confidence += 0.3;
        }
        if ($issuedOn) {
            $confidence += 0.05;
        }
        if (! empty($numberMatch[1])) {
            $confidence += 0.05;
        }

        $label = $type ? CertificateTypes::label($type) : null;
        $category = $type ? CertificateTypes::categoryFor($type) : null;
        $effectiveExpiry = $expiresOn ?: $renewalOn;
        $summary = $label
            ? ($effectiveExpiry ? "{$label} — expires {$effectiveExpiry}." : "{$label} document detected.")
            : ($effectiveExpiry ? "Document expires {$effectiveExpiry}." : null);

        return [
            'document_type' => $type,
            'category' => $category,
            'category_label' => $type ? CertificateTypes::categoryLabel($type) : null,
            'label' => $label,
            'certificate_number' => $numberMatch[1] ?? null,
            'expiry_date' => $effectiveExpiry,
            'renewal_date' => $renewalOn,
            'issue_date' => $issuedOn,
            'engineer_name' => $engineer,
            'issuer' => $issuer,
            'address' => $address,
            'result' => $result,
            'findings' => $findings,
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
            || filled($extracted['renewal_date'] ?? null)
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
        foreach (['document_type', 'category', 'category_label', 'label', 'certificate_number', 'expiry_date', 'renewal_date', 'issue_date', 'engineer_name', 'issuer', 'address', 'result', 'findings', 'summary'] as $key) {
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
            $merged['category'] = CertificateTypes::categoryFor($merged['document_type']);
            $merged['category_label'] = CertificateTypes::categoryLabel($merged['document_type']);
            if (empty($merged['label'])) {
                $merged['label'] = CertificateTypes::label($merged['document_type']);
            }
        }

        if (empty($merged['expiry_date']) && ! empty($merged['renewal_date'])) {
            $merged['expiry_date'] = $merged['renewal_date'];
        }

        return $merged;
    }

    public static function normalizeText(string $text): string
    {
        $normalized = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($normalized);
    }

    protected static function detectType(string $text): ?string
    {
        $hints = [
            'gas_safety' => ['/gas safety/i', '/\bcp12\b/i', '/landlord.?s? gas/i', '/gas safe/i'],
            'boiler_service' => ['/boiler (?:service|servicing|annual service)/i', '/gas boiler/i'],
            'eicr' => ['/\beicr\b/i', '/electrical installation condition/i', '/bs\s*7671/i'],
            'pat_testing' => ['/\bpat\b/i', '/portable appliance/i'],
            'fire_alarm' => ['/fire alarm/i', '/bs\s*5839/i', '/fire detection/i'],
            'fire_door' => ['/fire door/i'],
            'smoke_alarm' => ['/smoke alarm/i', '/smoke detector/i'],
            'fire_safety' => ['/fire (?:risk )?assessment/i', '/\bfra\b/i', '/fire safety/i'],
            'emergency_lighting' => ['/emergency lighting/i', '/bs\s*5266/i'],
            'pi_insurance' => ['/professional indemnity/i', '/\bpi insurance\b/i'],
            'cyber_insurance' => ['/cyber insurance/i', '/cyber liability/i'],
            'insurance' => ['/insurance/i', '/policy schedule/i', '/cover note/i', '/public liability/i', '/employers.? liability/i'],
            'contract' => ['/tenancy agreement/i', '/maintenance contract/i', '/service contract/i', '/contract (?:term|expiry|ends)/i', '/engagement letter/i'],
            'legionella' => ['/legionella/i'],
            'asbestos' => ['/asbestos/i'],
            'epc' => ['/\bepc\b/i', '/energy performance/i'],
            'dbs' => ['/\bdbs\b/i', '/disclosure and barring/i', '/criminal record check/i'],
            'gdpr' => ['/\bgdpr\b/i', '/data protection/i', '/uk gdpr/i'],
            'ico_registration' => ['/\bico\b/i', '/information commissioner/i'],
            'iso27001' => ['/iso\s*27001/i', '/information security management/i'],
            'iso9001' => ['/iso\s*9001/i'],
            'food_hygiene' => ['/food hygiene/i', '/food safety/i', '/fhia/i'],
            'allergen' => ['/allergen/i'],
            'safeguarding' => ['/safeguarding/i'],
            'right_to_work' => ['/right to work/i'],
            'right_to_rent' => ['/right to rent/i'],
            'deposit_protection' => ['/deposit protection/i', '/\btds\b/i', '/tenancy deposit/i'],
            'scaffold_inspection' => ['/scaffold(?:ing)? inspection/i'],
            'air_conditioning' => ['/air conditioning/i', '/\bac service\b/i'],
            'coshh' => ['/\bcoshh\b/i'],
            'rams' => ['/\brams\b/i', '/risk assessment and method statement/i'],
            'unvented_cylinder' => ['/unvented cylinder/i', '/\bg3\b/i'],
            'water_regulations' => ['/water regulations/i', '/water regs/i'],
            'cscs' => ['/\bcscs\b/i'],
            'aml' => ['/\baml\b/i', '/anti.?money laundering/i'],
            'cpd' => ['/\bcpd\b/i', '/practising certificate/i'],
            'ppe_checks' => ['/\bppe\b/i', '/personal protective equipment/i'],
            'health_safety' => ['/health (?:and|&) safety/i', '/\bh&s\b/i'],
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

    protected static function detectResult(string $text): ?string
    {
        if (preg_match('/overall (?:condition|assessment)[:\s\-]+(unsatisfactory|satisfactory|passed|pass|failed|fail)/i', $text, $match)) {
            return self::normalizeResult($match[1]);
        }

        if (preg_match('/system status on completion[:\s]+(normal|operational|fault)/i', $text, $match)) {
            return str_contains(strtolower($match[1]), 'fault') ? 'fail' : 'pass';
        }

        if (preg_match('/\bfailed[:\s]+(\d+)/i', $text, $match) && (int) $match[1] > 0) {
            return 'fail';
        }

        if (preg_match('/\b(unsatisfactory|failed|fail)\b/i', $text)) {
            return 'fail';
        }
        if (preg_match('/\b(satisfactory|passed|pass|safe to use)\b/i', $text)) {
            return 'pass';
        }

        return null;
    }

    protected static function normalizeResult(string $raw): string
    {
        $value = strtolower(trim($raw));

        return in_array($value, ['unsatisfactory', 'failed', 'fail'], true) ? 'fail' : 'pass';
    }

    protected static function extractEngineer(string $text): ?string
    {
        if (preg_match(
            '/(?i:engineer(?:\s+name)?|inspector|tester|serviced by|assessor)[:\s]+(?!(?i:engineer|inspector|declaration|signature|ref\.?|details|name|id|company)\b)([A-Z][a-zA-Z\'\-]+(?:\s+[A-Z][a-zA-Z\'\-]+){1,2})(?=\s+(?:Company|Ltd|Limited|Telephone|Engineer|Inspector|Date|Client|Appliance|Registration|Gas Safe)|\s*[-.,;]|$)/',
            $text,
            $match
        )) {
            return trim($match[1], " \t\n\r\0\x0B.,");
        }

        return null;
    }

    protected static function extractIssuer(string $text): ?string
    {
        if (preg_match(
            '/(?i:issued by|issuer|awarding body|insurance company|insurer|company)[:\s]+(.+?)(?=\s+(?i:engineer ref|telephone|email|registration|scheme no|gas safe|date|inspector details|client|appliance|engineer declaration)|[.;]|$)/',
            $text,
            $match
        )) {
            return trim($match[1], " \t\n\r\0\x0B.,");
        }

        if (preg_match(
            '/(?i:tester|engineer(?:\s+name)?|inspector)[:\s]+[A-Z][a-zA-Z\'\-]+(?:\s+[A-Z][a-zA-Z\'\-]+){1,2}\s*[-–]\s*([A-Z][A-Za-z0-9 &.\'\-]{3,80}?)(?=\s+(?:Appliance|Client|Telephone|Date|Inspection)|$)/',
            $text,
            $match
        )) {
            return trim($match[1], " \t\n\r\0\x0B.,");
        }

        return null;
    }

    protected static function extractAddress(string $text): ?string
    {
        $postcode = '[A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2}';
        $patterns = [
            '/(?:installation address|property address|site address)[:\s]+(.{8,180}?\b'.$postcode.'\b)/i',
            '/\bpremises[:\s]+(?!and\b)(.{8,180}?\b'.$postcode.'\b)/i',
            '/\bsite(?!\s+(?:ref\.?|details|contact))\s+(.{8,180}?\b'.$postcode.'\b)/i',
            '/(?:installation address|property address|site address|address)[:\s]+(.+?)(?:\.\s+(?:engineer|inspector|assessor|contractor|address|expiry|expires|issued)|;|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match) && ! empty($match[1])) {
                $address = trim($match[1], " \t\n\r\0\x0B.,");
                if ($address !== '' && ! preg_match('/^able\b/i', $address)) {
                    return $address;
                }
            }
        }

        return null;
    }

    protected static function extractFindings(string $text): ?string
    {
        $patterns = [
            '/defects?\s+identified[:\s]+(.{3,240}?)(?=\s+(?:recommendations?|classification|action taken|system status|engineer declaration|general checks)|$)/i',
            '/\bdefect[:\s]+(.{12,240}?)(?=\s+(?:action|summary|engineer declaration)|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match) && ! empty($match[1])) {
                $findings = trim($match[1], " \t\n\r\0\x0B.,");
                if ($findings !== '') {
                    return $findings;
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
