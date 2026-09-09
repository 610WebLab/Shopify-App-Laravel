<?php

namespace App\Traits;

use App\Models\Shippingzone;
use Illuminate\Support\Facades\Log;

trait FetchShippingZoneTrate
{
    /**
     * Resolve the best matching active shipping zone for a destination.
     * Returns json-encoded zone (or null) for backward compatibility with callers.
     */
    public function getShippingZones($country, $state, $postCode, $shopId)
    {
        $country = $this->normalizeRegionCode($country);
        $state = $this->normalizeRegionCode($state);
        $postCode = $this->normalizePostalCode($postCode);
        $shopId = (int) $shopId;
        $stateCode = ($country !== '' && $state !== '') ? ($country . ':' . $state) : '';

        if ($country === '' && $state === '' && $postCode === '') {
            Log::info('[CarrierService] Zone lookup skipped - no destination data', [
                'shop_id' => $shopId,
            ]);

            return json_encode(null);
        }

        $zones = Shippingzone::where('user_id', $shopId)
            ->where('status', 1)
            ->orderBy('id')
            ->get();

        if ($zones->isEmpty()) {
            return json_encode(null);
        }

        $candidates = [];

        foreach ($zones as $zone) {
            $score = $this->scoreZoneMatch($zone, $country, $stateCode);
            if ($score <= 0) {
                continue;
            }

            if (!$this->zoneAllowsPostalCode($zone, $postCode)) {
                Log::info('[CarrierService] Zone matched region but postal code rejected', [
                    'shop_id' => $shopId,
                    'zone_id' => $zone->id,
                    'post_code' => $postCode,
                    'score' => $score,
                ]);
                continue;
            }

            $candidates[] = [
                'zone' => $zone,
                'score' => $score,
            ];
        }

        if (empty($candidates)) {
            Log::info('[CarrierService] No shipping zone matched destination', [
                'shop_id' => $shopId,
                'country' => $country,
                'state' => $state,
                'state_code' => $stateCode,
                'post_code' => $postCode,
            ]);

            return json_encode(null);
        }

        usort($candidates, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['zone']->id <=> $b['zone']->id;
            }

            return $b['score'] <=> $a['score'];
        });

        $best = $candidates[0];

        Log::info('[CarrierService] Zone matched', [
            'shop_id' => $shopId,
            'zone_id' => $best['zone']->id,
            'score' => $best['score'],
            'country' => $country,
            'state_code' => $stateCode,
            'post_code' => $postCode,
            'candidate_count' => count($candidates),
        ]);

        return json_encode($best['zone']);
    }

    /**
     * Higher score = more specific match.
     * 100 = state (e.g. US:CA), 50 = country (e.g. US), 1 = empty/worldwide fallback.
     */
    private function scoreZoneMatch($zone, string $country, string $stateCode): int
    {
        $regionValues = $this->extractZoneRegionValues($zone);
        $countries = $this->splitCsvList($zone->country ?? '');
        $states = $this->splitCsvList($zone->state ?? '');

        $hasStateMatch = false;
        $hasCountryMatch = false;

        if ($stateCode !== '') {
            if (in_array($stateCode, $states, true) || in_array($stateCode, $regionValues, true)) {
                $hasStateMatch = true;
            }
        }

        if ($country !== '') {
            if (in_array($country, $countries, true) || in_array($country, $regionValues, true)) {
                $hasCountryMatch = true;
            }
        }

        if ($hasStateMatch) {
            return 100;
        }

        if ($hasCountryMatch) {
            return 50;
        }

        if ($this->isEmptyRegionFallbackZone($zone, $countries, $states, $regionValues)) {
            return 1;
        }

        return 0;
    }

    private function isEmptyRegionFallbackZone($zone, array $countries, array $states, array $regionValues): bool
    {
        $countryEmpty = ($zone->country === null || trim((string) $zone->country) === '');
        $stateEmpty = ($zone->state === null || trim((string) $zone->state) === '');

        return $countryEmpty
            && $stateEmpty
            && empty($countries)
            && empty($states)
            && empty($regionValues);
    }

    private function extractZoneRegionValues($zone): array
    {
        if (empty($zone->zone_region)) {
            return [];
        }

        $regions = json_decode($zone->zone_region, true);
        if (!is_array($regions)) {
            return [];
        }

        $values = [];
        foreach ($regions as $region) {
            $value = is_array($region) ? ($region['value'] ?? '') : $region;
            $value = $this->normalizeRegionCode((string) $value);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    private function zoneAllowsPostalCode($zone, string $postCode): bool
    {
        $allowedZips = $this->parsePostalCodeList($zone->zip ?? '');
        if (empty($allowedZips)) {
            return true;
        }

        if ($postCode === '') {
            return false;
        }

        $postCodeBase = $this->postalCodeBase($postCode);

        foreach ($allowedZips as $allowed) {
            $allowedBase = $this->postalCodeBase($allowed);
            if (
                strcasecmp($postCode, $allowed) === 0
                || strcasecmp($postCodeBase, $allowed) === 0
                || strcasecmp($postCode, $allowedBase) === 0
                || strcasecmp($postCodeBase, $allowedBase) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    private function parsePostalCodeList(?string $zipField): array
    {
        if ($zipField === null || trim($zipField) === '') {
            return [];
        }

        $parts = preg_split('/[,;\n\r]+/', $zipField) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $value = $this->normalizePostalCode($part);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function splitCsvList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $parts = array_map(
            fn ($item) => $this->normalizeRegionCode($item),
            explode(',', $value)
        );

        return array_values(array_filter($parts, fn ($item) => $item !== ''));
    }

    private function normalizeRegionCode($value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizePostalCode($value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $value)) ?? '');
    }

    private function postalCodeBase(string $postCode): string
    {
        if (str_contains($postCode, '-')) {
            return trim(explode('-', $postCode, 2)[0]);
        }

        return $postCode;
    }
}
