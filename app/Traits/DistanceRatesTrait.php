<?php

namespace App\Traits;

use App\Models\RatesByDistance;
use App\Models\Shippingzone;
use App\Traits\FetchShippingZoneTrate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait DistanceRatesTrait
{
    use FetchShippingZoneTrate;

    /**
     * @param  array|string  $destinationOrAddress  Full destination array preferred; string address kept for BC.
     * @param  array|null  $originOrAddress  Shopify rate.origin fallback when zone has no store location.
     */
    public function DistanceRateShipping($country_code, $province_code, $post_code, $destinationOrAddress, $price, $weightInGram, $quantity, $lineItem, $shopId, $originOrAddress = null)
    {
        $destination = $this->normalizeDestinationPoint(
            $destinationOrAddress,
            $country_code,
            $province_code,
            $post_code
        );

        $fallbackOrigin = is_array($originOrAddress)
            ? $this->normalizeDestinationPoint($originOrAddress)
            : null;

        $zone = json_decode($this->getShippingZones(
            $destination['country'],
            $destination['province'],
            $destination['postal_code'],
            $shopId
        ));

        if (empty($zone)) {
            return json_encode([]);
        }

        return json_encode($this->calCulateDistanceRate(
            $zone->id,
            $price,
            $weightInGram,
            $quantity,
            $lineItem,
            $destination,
            $fallbackOrigin
        ));
    }

    public function calCulateDistanceRate($zoneID, $price, $weightInGram, $quantity, $lineItem, array $destination, ?array $fallbackOrigin = null)
    {
        $originResolution = $this->resolveOriginPoint($zoneID, $fallbackOrigin);
        $origin = $originResolution['point'] ?? null;
        $destinationPoint = $this->formatDistancePoint($destination);

        if (empty($origin) || empty($destinationPoint)) {
            Log::info('[DistanceRate] Missing origin or destination point', [
                'zone_id' => $zoneID,
                'origin' => $origin,
                'origin_source' => $originResolution['source'] ?? null,
                'origin_reason' => $originResolution['reason'] ?? null,
                'destination' => $destinationPoint,
                'destination_fields' => $destination,
                'hint' => 'Assign an active store location on the shipping zone, or ensure Shopify rate.origin is present.',
            ]);

            return [];
        }

        Log::info('[DistanceRate] Using origin/destination points', [
            'zone_id' => $zoneID,
            'origin' => $origin,
            'origin_source' => $originResolution['source'] ?? null,
            'destination' => $destinationPoint,
        ]);

        $matrix = $this->getDistanceMatrix($origin, $destinationPoint);

        if (empty($matrix['status']) || !isset($matrix['distance_km'])) {
            Log::warning('[DistanceRate] Distance Matrix failed - could not calculate distance between points', [
                'zone_id' => $zoneID,
                'point_a_origin' => $origin,
                'point_b_destination' => $destinationPoint,
                'destination_fields' => $destination,
                'matrix' => $matrix,
            ]);

            return [];
        }

        $distanceKm = (float) $matrix['distance_km'];

        Log::info('[DistanceRate] Total distance between two points', [
            'zone_id' => $zoneID,
            'point_a_origin' => $origin,
            'point_b_destination' => $destinationPoint,
            'distance_km' => $distanceKm,
            'distance_text' => $matrix['distance_text'] ?? null,
            'destination_fields' => $destination,
        ]);

        $distanceRates = RatesByDistance::where('zone_id', $zoneID)->where('status', 1)->get();
        $result = [];

        foreach ($distanceRates as $distance) {
            if (!$this->isWithinDistanceLimits($distance, $distanceKm)) {
                Log::info('[DistanceRate] Rate skipped - outside min/max distance', [
                    'zone_id' => $zoneID,
                    'rate_id' => $distance->id,
                    'rate_title' => $distance->title,
                    'distance_km' => $distanceKm,
                    'min_distance' => $distance->min_distance,
                    'max_distance' => $distance->max_distance,
                ]);
                continue;
            }

            if ($distance->rates == 'price_based_rate') {
                $priceBasedRate = $this->calculationPriceBasedRate($distance, $price, $distanceKm);
                if ($priceBasedRate) {
                    $result[] = $priceBasedRate;
                } else {
                    Log::info('[DistanceRate] Rate skipped - cart price outside min/max order price', [
                        'zone_id' => $zoneID,
                        'rate_id' => $distance->id,
                        'rate_title' => $distance->title,
                        'cart_price' => $price,
                        'min_order_price' => $distance->min_order_price,
                        'max_order_price' => $distance->max_order_price,
                        'distance_km' => $distanceKm,
                    ]);
                }
            } else {
                $weightBasedRate = $this->calculationWeightBasedRate($distance, $weightInGram, $distanceKm);
                if ($weightBasedRate) {
                    $result[] = $weightBasedRate;
                } else {
                    Log::info('[DistanceRate] Rate skipped - cart weight outside min/max order weight', [
                        'zone_id' => $zoneID,
                        'rate_id' => $distance->id,
                        'rate_title' => $distance->title,
                        'cart_weight_grams' => $weightInGram,
                        'min_order_weight' => $distance->min_order_weight,
                        'max_order_weight' => $distance->max_order_weight,
                        'weight_unit' => $distance->weight_unit,
                        'distance_km' => $distanceKm,
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Point A: zone store location first; Shopify rate.origin as fallback.
     */
    public function resolveOriginPoint($zoneID, ?array $fallbackOrigin = null): array
    {
        $zone = Shippingzone::with('storeLocation')->find($zoneID);

        if (!$zone) {
            return ['point' => null, 'source' => null, 'reason' => 'zone_not_found'];
        }

        if (empty($zone->location_id)) {
            $fallbackPoint = $fallbackOrigin ? $this->formatDistancePoint($fallbackOrigin) : null;
            if ($fallbackPoint) {
                return [
                    'point' => $fallbackPoint,
                    'source' => 'shopify_rate_origin',
                    'reason' => 'zone_has_no_store_location',
                ];
            }

            return [
                'point' => null,
                'source' => null,
                'reason' => 'zone_has_no_store_location_and_no_shopify_origin',
            ];
        }

        $location = $zone->storeLocation;
        if (!$location) {
            $fallbackPoint = $fallbackOrigin ? $this->formatDistancePoint($fallbackOrigin) : null;
            if ($fallbackPoint) {
                return [
                    'point' => $fallbackPoint,
                    'source' => 'shopify_rate_origin',
                    'reason' => 'store_location_record_missing',
                ];
            }

            return [
                'point' => null,
                'source' => null,
                'reason' => 'store_location_record_missing',
            ];
        }

        if (!$location->is_active) {
            $fallbackPoint = $fallbackOrigin ? $this->formatDistancePoint($fallbackOrigin) : null;
            if ($fallbackPoint) {
                return [
                    'point' => $fallbackPoint,
                    'source' => 'shopify_rate_origin',
                    'reason' => 'store_location_inactive',
                ];
            }

            return [
                'point' => null,
                'source' => null,
                'reason' => 'store_location_inactive',
            ];
        }

        $point = $this->formatDistancePoint([
            'address1' => $location->address1,
            'address2' => $location->address2,
            'city' => $location->city,
            'province' => $location->province_code ?: $location->province,
            'postal_code' => $location->zip,
            'country' => $location->country_code ?: $location->country,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ]);

        if ($point) {
            return [
                'point' => $point,
                'source' => 'zone_store_location',
                'reason' => null,
            ];
        }

        $fallbackPoint = $fallbackOrigin ? $this->formatDistancePoint($fallbackOrigin) : null;
        if ($fallbackPoint) {
            return [
                'point' => $fallbackPoint,
                'source' => 'shopify_rate_origin',
                'reason' => 'store_location_has_no_usable_address',
            ];
        }

        return [
            'point' => null,
            'source' => null,
            'reason' => 'store_location_has_no_usable_address',
        ];
    }

    /**
     * @deprecated Use resolveOriginPoint(); kept for BC.
     */
    public function resolveZoneStoreOriginPoint($zoneID): ?string
    {
        return $this->resolveOriginPoint($zoneID)['point'] ?? null;
    }

    /**
     * Point B: checkout / order destination.
     */
    private function normalizeDestinationPoint($destinationOrAddress, $country = '', $province = '', $postalCode = ''): array
    {
        if (is_array($destinationOrAddress)) {
            return [
                'address1' => $destinationOrAddress['address1'] ?? ($destinationOrAddress['street1'] ?? ''),
                'address2' => $destinationOrAddress['address2'] ?? ($destinationOrAddress['street2'] ?? ''),
                'city' => $destinationOrAddress['city'] ?? '',
                'province' => $destinationOrAddress['province']
                    ?? ($destinationOrAddress['province_code'] ?? ($destinationOrAddress['state'] ?? '')),
                'postal_code' => $destinationOrAddress['postal_code']
                    ?? ($destinationOrAddress['zip'] ?? ''),
                'country' => $destinationOrAddress['country']
                    ?? ($destinationOrAddress['country_code'] ?? ''),
                'latitude' => $destinationOrAddress['latitude'] ?? null,
                'longitude' => $destinationOrAddress['longitude'] ?? null,
            ];
        }

        return [
            'address1' => (string) $destinationOrAddress,
            'address2' => '',
            'city' => '',
            'province' => (string) $province,
            'postal_code' => (string) $postalCode,
            'country' => (string) $country,
            'latitude' => null,
            'longitude' => null,
        ];
    }

    private function formatDistancePoint(array $point): ?string
    {
        $lat = $point['latitude'] ?? null;
        $lng = $point['longitude'] ?? null;
        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
            return trim((string) $lat) . ',' . trim((string) $lng);
        }

        $parts = array_filter([
            trim((string) ($point['address1'] ?? '')),
            trim((string) ($point['address2'] ?? '')),
            trim((string) ($point['city'] ?? '')),
            trim((string) ($point['province'] ?? '')),
            trim((string) ($point['postal_code'] ?? '')),
            trim((string) ($point['country'] ?? '')),
        ], fn ($value) => $value !== '');

        if (empty($parts)) {
            return null;
        }

        return implode(', ', $parts);
    }

    private function isWithinDistanceLimits($distance, float $distanceKm): bool
    {
        $min = floatval($distance->min_distance);
        $max = floatval($distance->max_distance);
        $hasMin = !empty($distance->min_distance) && $min > 0;
        $hasMax = !empty($distance->max_distance) && $max > 0;

        if ($hasMin && $hasMax) {
            return $distanceKm >= $min && $distanceKm <= $max;
        }

        if (!$hasMin && $hasMax) {
            return $distanceKm <= $max;
        }

        if ($hasMin && !$hasMax) {
            return $distanceKm >= $min;
        }

        return true;
    }

    public function calculationPriceBasedRate($distance, $price, $distanceKm)
    {
        $cartPrice = floatval($price) / 100;

        if (!empty($distance->min_order_price) && !empty($distance->max_order_price)) {
            if (floatval($distance->min_order_price) > 0 && floatval($distance->max_order_price) > 0 && $cartPrice > 0) {
                if ($cartPrice >= floatval($distance->min_order_price) && $cartPrice <= floatval($distance->max_order_price)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } elseif (empty($distance->min_order_price) && !empty($distance->max_order_price)) {
            if (floatval($distance->max_order_price) > 0 && $cartPrice > 0) {
                if ($cartPrice <= floatval($distance->max_order_price)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } elseif (!empty($distance->min_order_price) && empty($distance->max_order_price)) {
            if (floatval($distance->min_order_price) > 0 && $cartPrice > 0) {
                if ($cartPrice >= floatval($distance->min_order_price)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } else {
            return $this->calculateDeliveryRate($distance, $distanceKm);
        }

        return null;
    }

    public function calculationWeightBasedRate($distance, $weight, $distanceKm)
    {
        $cartWeight = floatval($weight);
        $minOrderWeight = $this->convertToGrams($distance->min_order_weight, $distance->weight_unit);
        $maxOrderWeight = $this->convertToGrams($distance->max_order_weight, $distance->weight_unit);

        if (!empty($distance->min_order_weight) && !empty($distance->max_order_weight)) {
            if (floatval($distance->min_order_weight) > 0 && floatval($distance->max_order_weight) > 0 && $cartWeight > 0) {
                if ($cartWeight >= floatval($minOrderWeight) && $cartWeight <= floatval($maxOrderWeight)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } elseif (empty($distance->min_order_weight) && !empty($distance->max_order_weight)) {
            if (floatval($distance->max_order_weight) > 0 && $cartWeight > 0) {
                if (floatval($maxOrderWeight) >= $cartWeight) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } elseif (!empty($distance->min_order_weight) && empty($distance->max_order_weight)) {
            if (floatval($distance->min_order_weight) > 0 && $cartWeight > 0) {
                if ($cartWeight >= floatval($minOrderWeight)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } else {
            return $this->calculateDeliveryRate($distance, $distanceKm);
        }

        return null;
    }

    public function calculateDeliveryRate($distance, $km)
    {
        $basePrice = floatval($distance->base_delivery_price ?? 0);
        $pricePerKm = floatval($distance->price_per_kilometer ?? 0);
        $maxEnabled = ($distance->rate_price_limit ?? 'no') === 'yes';
        $maxDeliveryRate = floatval($distance->max_delivery_rate ?? 0);
        $applyMaxCap = $maxEnabled && $maxDeliveryRate > 0;
        $distanceKm = floatval($km);
        $rawRate = 0;
        $formula = '';
        $cappedByMax = false;

        if ($pricePerKm > 0) {
            $rawRate = $pricePerKm * $distanceKm;
            $formula = 'price_per_kilometer * distance_km';
        } elseif ($basePrice > 0) {
            $rawRate = $basePrice;
            $formula = 'base_delivery_price';
        } else {
            $rawRate = 0;
            $formula = 'zero (no price params)';
        }

        if ($applyMaxCap && $rawRate > $maxDeliveryRate) {
            $shipping = $maxDeliveryRate;
            $cappedByMax = true;
        } else {
            $shipping = $rawRate;
        }

        $shopifyTotalPriceCents = (int) round($shipping * 100);

        Log::info('[DistanceRate] total_price calculation', [
            'rate_id' => $distance->id,
            'rate_title' => $distance->title,
            'zone_id' => $distance->zone_id,
            'parameters' => [
                'distance_km' => $distanceKm,
                'base_delivery_price' => $basePrice,
                'price_per_kilometer' => $pricePerKm,
                'rate_price_limit' => $distance->rate_price_limit ?? 'no',
                'max_delivery_rate' => $maxDeliveryRate,
                'max_cap_applied' => $applyMaxCap,
                'min_distance' => $distance->min_distance,
                'max_distance' => $distance->max_distance,
                'rates_type' => $distance->rates,
                'min_order_price' => $distance->min_order_price,
                'max_order_price' => $distance->max_order_price,
                'min_order_weight' => $distance->min_order_weight,
                'max_order_weight' => $distance->max_order_weight,
                'weight_unit' => $distance->weight_unit,
            ],
            'formula' => $formula,
            'raw_rate_before_cap' => $rawRate,
            'capped_by_max_delivery_rate' => $cappedByMax,
            'shipPrice' => $shipping,
            'shopify_total_price_cents' => $shopifyTotalPriceCents,
            'shopify_formula' => 'total_price = shipPrice * 100',
        ]);

        return [[
            'status' => 1,
            'service_name' => $distance->title,
            'description' => $distance->description,
            'shipPrice' => $shipping,
        ]];
    }

    /**
     * Google Distance Matrix between Point A (origin) and Point B (destination).
     */
    public function getDistanceMatrix($origin, $destination)
    {
        $apiKey = config('app.google_map_api_key');
        if (empty($apiKey) || empty($origin) || empty($destination)) {
            return ['status' => false, 'message' => 'Missing API key, origin, or destination'];
        }

        $request = Http::asJson();
        if (!config('shopify-app.http_verify_ssl')) {
            $request = $request->withoutVerifying();
        }

        try {
            $response = $request->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'units' => 'metric',
                'origins' => $origin,
                'destinations' => $destination,
                'key' => $apiKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('[DistanceRate] Distance Matrix request failed', [
                'message' => $e->getMessage(),
                'origin' => $origin,
                'destination' => $destination,
            ]);

            return ['status' => false, 'message' => $e->getMessage()];
        }

        if (!$response->successful()) {
            return ['status' => false, 'message' => 'Distance Matrix HTTP error'];
        }

        $data = $response->json();
        if (($data['status'] ?? null) !== 'OK') {
            return [
                'status' => false,
                'message' => $data['error_message'] ?? ($data['status'] ?? 'Distance Matrix status not OK'),
            ];
        }

        $element = $data['rows'][0]['elements'][0] ?? null;
        if (!$element || ($element['status'] ?? null) !== 'OK' || !isset($element['distance']['value'])) {
            return [
                'status' => false,
                'message' => $element['status'] ?? 'No route between origin and destination',
            ];
        }

        $meters = (float) $element['distance']['value'];
        $distanceKm = round($meters / 1000, 3);

        return [
            'status' => true,
            'distance_km' => $distanceKm,
            'distance_text' => $element['distance']['text'] ?? ($distanceKm . ' km'),
            'origin' => $origin,
            'destination' => $destination,
        ];
    }

    public function convertToGrams($weight, $unit)
    {
        if (is_null($weight) || !is_numeric($weight) || floatval($weight) <= 0) {
            return 0;
        }

        $unit = strtolower((string) $unit);
        switch ($unit) {
            case 'kg':
                return floatval($weight) * 1000;
            case 'lb':
                return floatval($weight) * 453.592;
            case 'oz':
                return floatval($weight) * 28.3495;
            default:
                return 0;
        }
    }
}
