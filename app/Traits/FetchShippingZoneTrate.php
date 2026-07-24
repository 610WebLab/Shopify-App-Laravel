<?php
namespace App\Traits;
use App\Models\Shippingzone;
use Illuminate\Support\Facades\Log;

trait FetchShippingZoneTrate {
    public function getShippingZones($country, $state, $postCode, $shopId)
    {
        if (!$country && !$state && !$postCode) {
            Log::info('[CarrierService] Zone lookup skipped - no destination data', [
                'shop_id' => $shopId,
            ]);
            return json_encode(null);
        }

        $shopId = (int) $shopId;
        $stateCode = $country . ':' . $state;
        $matchMethod = null;
        $zone = null;

        $zone = Shippingzone::whereRaw(
            '(FIND_IN_SET(?, country) OR FIND_IN_SET(?, state)) AND user_id = ? AND status = 1',
            [$country, $stateCode, $shopId]
        )->first();

        if (!empty($zone)) {
            $matchMethod = 'country_state_columns';
        }

        if (empty($zone)) {
            $zones = Shippingzone::where('user_id', $shopId)->where('status', 1)->get();
            foreach ($zones as $candidate) {
                if ($this->zoneRegionMatches($candidate, $country, $stateCode)) {
                    $zone = $candidate;
                    $matchMethod = 'zone_region_json';
                    break;
                }
            }
        }

        if (empty($zone)) {
            $zone = Shippingzone::where('country', '')
                ->where('state', '')
                ->where('user_id', $shopId)
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('zone_region')
                        ->orWhere('zone_region', '')
                        ->orWhere('zone_region', '[]');
                })
                ->first();

            if (!empty($zone)) {
                $matchMethod = 'fallback_empty_region';
            }
        }

        if (!empty($zone) && !empty($zone->zip)) {
            $allowedZips = array_filter(array_map('trim', explode(',', $zone->zip)));
            $normalizedPostCode = trim((string) $postCode);

            if (!empty($allowedZips) && ($normalizedPostCode === '' || !in_array($normalizedPostCode, $allowedZips, true))) {
                Log::info('[CarrierService] Zone matched but postal code rejected', [
                    'shop_id' => $shopId,
                    'zone_id' => $zone->id,
                    'post_code' => $normalizedPostCode,
                    'allowed_zips' => $allowedZips,
                    'match_method' => $matchMethod,
                ]);
                return json_encode(null);
            }
        }

        Log::info('[CarrierService] Zone lookup result', [
            'shop_id' => $shopId,
            'country' => $country,
            'state' => $state,
            'state_code' => $stateCode,
            'post_code' => $postCode,
            'matched_zone_id' => $zone->id ?? null,
            'match_method' => $matchMethod,
        ]);

        return json_encode($zone);
    }

    private function zoneRegionMatches($zone, $country, $stateCode)
    {
        if (empty($zone->zone_region)) {
            return false;
        }

        $regions = json_decode($zone->zone_region, true);
        if (!is_array($regions)) {
            return false;
        }

        foreach ($regions as $region) {
            $value = is_array($region) ? ($region['value'] ?? '') : $region;
            if ($value === $country || $value === $stateCode) {
                return true;
            }
        }

        return false;
    }
}
