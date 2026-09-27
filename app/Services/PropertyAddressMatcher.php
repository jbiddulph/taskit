<?php

namespace App\Services;

use App\Models\OperationalObject;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Match an extracted certificate address (or filename hint) to a company property/site.
 */
class PropertyAddressMatcher
{
    public const STATUS_MATCHED = 'matched';

    public const STATUS_SUGGESTED = 'suggested';

    public const STATUS_UNMATCHED = 'unmatched';

    public const STATUS_MANUAL = 'manual';

    /**
     * @return array{status: string, confidence: int, site: OperationalObject|null, score: float, reason: string|null}
     */
    public function match(int $companyId, ?string $address, ?string $filenameHint = null): array
    {
        $sites = OperationalObject::forCompany($companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'address_line_1', 'address_line_2', 'city', 'postal_code', 'company_id']);

        if ($sites->isEmpty()) {
            return $this->result(self::STATUS_UNMATCHED, 0, null, 0, 'No properties in portfolio.');
        }

        $address = trim((string) $address);
        $filenameHint = trim((string) $filenameHint);

        $best = null;
        $bestScore = 0.0;
        $bestReason = null;

        foreach ($sites as $site) {
            [$score, $reason] = $this->scoreSite($site, $address, $filenameHint);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $site;
                $bestReason = $reason;
            }
        }

        if (! $best || $bestScore < 0.35) {
            return $this->result(self::STATUS_UNMATCHED, (int) round($bestScore * 100), null, $bestScore, 'No confident property match.');
        }

        if ($bestScore >= 0.72) {
            return $this->result(self::STATUS_MATCHED, (int) round($bestScore * 100), $best, $bestScore, $bestReason);
        }

        return $this->result(self::STATUS_SUGGESTED, (int) round($bestScore * 100), $best, $bestScore, $bestReason);
    }

    /**
     * @return array{0: float, 1: string|null}
     */
    private function scoreSite(OperationalObject $site, string $address, string $filenameHint): array
    {
        $score = 0.0;
        $reasons = [];

        $siteAddress = $this->normalize(collect([
            $site->address_line_1,
            $site->address_line_2,
            $site->city,
            $site->postal_code,
        ])->filter()->implode(' '));
        $siteName = $this->normalize((string) $site->name);
        $needle = $this->normalize($address);
        $fileNeedle = $this->normalize($filenameHint);

        $sitePostcode = $this->normalizePostcode((string) $site->postal_code);
        $addressPostcode = $this->extractPostcode($address);

        if ($sitePostcode && $addressPostcode && $sitePostcode === $addressPostcode) {
            $score += 0.45;
            $reasons[] = 'postcode';
        }

        if ($needle !== '' && $siteAddress !== '') {
            if ($needle === $siteAddress || str_contains($siteAddress, $needle) || str_contains($needle, $siteAddress)) {
                $score += 0.5;
                $reasons[] = 'full address';
            } else {
                $overlap = $this->tokenOverlap($needle, $siteAddress);
                if ($overlap >= 0.6) {
                    $score += 0.35 * $overlap;
                    $reasons[] = 'address tokens';
                }
            }
        }

        if ($needle !== '' && $siteName !== '') {
            if (str_contains($needle, $siteName) || str_contains($siteName, $needle)) {
                $score += 0.35;
                $reasons[] = 'property name';
            } else {
                $overlap = $this->tokenOverlap($needle, $siteName);
                if ($overlap >= 0.5) {
                    $score += 0.25 * $overlap;
                    $reasons[] = 'name tokens';
                }
            }
        }

        // House number + street fragment (e.g. "22 richmond")
        if ($needle !== '' && preg_match('/\b(\d+[a-z]?)\b/i', $address, $num)) {
            $number = strtolower($num[1]);
            $hay = $siteAddress.' '.$siteName;
            if (str_contains($hay, $number)) {
                $score += 0.15;
                $reasons[] = 'house number';
            }
        }

        if ($fileNeedle !== '') {
            if ($siteName !== '' && (str_contains($fileNeedle, $siteName) || $this->tokenOverlap($fileNeedle, $siteName) >= 0.6)) {
                $score += 0.25;
                $reasons[] = 'filename';
            }
            if ($sitePostcode && str_contains(str_replace(' ', '', $fileNeedle), str_replace(' ', '', $sitePostcode))) {
                $score += 0.2;
                $reasons[] = 'filename postcode';
            }
        }

        return [min(1.0, $score), $reasons ? implode(', ', array_unique($reasons)) : null];
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = str_replace(['.', ',', ';', '#', '/', '\\', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function normalizePostcode(string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', $value) ?? $value);

        return $value !== '' ? $value : null;
    }

    private function extractPostcode(string $value): ?string
    {
        if (preg_match('/\b([A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2})\b/i', $value, $m)) {
            return $this->normalizePostcode($m[1]);
        }

        return null;
    }

    private function tokenOverlap(string $a, string $b): float
    {
        $aTokens = $this->tokens($a);
        $bTokens = $this->tokens($b);
        if ($aTokens->isEmpty() || $bTokens->isEmpty()) {
            return 0.0;
        }

        $overlap = $aTokens->intersect($bTokens)->count();

        return $overlap / max($aTokens->count(), 1);
    }

    /**
     * @return Collection<int, string>
     */
    private function tokens(string $value): Collection
    {
        $stop = collect(['the', 'and', 'flat', 'apartment', 'apt', 'unit', 'floor', 'uk', 'england']);

        return collect(preg_split('/\s+/', $value) ?: [])
            ->filter(fn ($t) => strlen((string) $t) >= 2 && ! $stop->contains($t))
            ->values();
    }

    /**
     * @return array{status: string, confidence: int, site: OperationalObject|null, score: float, reason: string|null}
     */
    private function result(string $status, int $confidence, ?OperationalObject $site, float $score, ?string $reason): array
    {
        return [
            'status' => $status,
            'confidence' => max(0, min(100, $confidence)),
            'site' => $site,
            'score' => $score,
            'reason' => $reason,
        ];
    }
}
