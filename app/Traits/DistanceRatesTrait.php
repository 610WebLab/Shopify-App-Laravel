<?php

namespace App\Traits;

use App\Models\RatesByDistance;
use App\Traits\FetchShippingZoneTrate;
use Log;
use Http;

trait DistanceRatesTrait
{
    use FetchShippingZoneTrate;

    public function DistanceRateShipping($country_code, $province_code, $post_code, $address, $price, $weightInGram, $quantity, $lineItem, $shopId)
    {
        $zone = json_decode($this->getShippingZones($country_code, $province_code, $post_code, $shopId));
        if (!empty($zone)) {

            return json_encode($this->calCulateDistanceRate($zone->id, $price, $weightInGram, $quantity, $lineItem, $address));
        } else {

            return response()->json([]);
        }
    }
    public function calCulateDistanceRate($zoneID, $price, $weightInGram, $quantity, $lineItem, $address)
    {
        $distanceRates = RatesByDistance::where('zone_id', $zoneID)->where('status', 1)->get();
        $result = [];
        foreach ($distanceRates as $distance) {
            if ($distance->rates == "price_based_rate") {
                $priceBasedRate = $this->calculationDistancePriceBasedRate($distance, $price, $quantity, $lineItem, $address);
                if ($priceBasedRate) {
                    array_push($result, $priceBasedRate);
                }
            } else {
                $weightBasedRate = $this->calculationDistanceWeightBasedRate($distance, $weightInGram, $quantity, $lineItem, $address);
                if ($weightBasedRate) {
                    array_push($result, $weightBasedRate);
                }
            }
        }
        return $result;
    }
    /** Fuctionality based on Price Based Rate Start **/
    public function calculationDistancePriceBasedRate($distance, $price, $quantity, $lineItem, $address)
    {
        $origin = null;
        if (!empty($distance->latitude) && !empty($distance->longitude)) {
            $origin = "{$distance->latitude},{$distance->longitude}";
        } else {
            $origin = $distance->street . ", " . $distance->city . ", " . $distance->country_region . ", " . $distance->postal_code;
        }
        $location = $this->getDistanceMatrix($origin, $address);
        if ($location['status'] && isset($location['distance'])) {
            $distanceKm = str_replace(" km", "", $location['distance']);
            if (!empty($distance->min_distance) && !empty($distance->max_distance)) {
                if (floatval($distance->min_distance) > 0 && floatval($distance->max_distance) > 0) {
                    if (floatval($distanceKm) >= floatval($distance->min_distance) && floatval($distanceKm) <= floatval($distance->max_distance)) {
                        return $this->calculationPriceBasedRate($distance, $price, $distanceKm);
                    }
                }
            } else if (empty($distance->min_distance) && !empty($distance->max_distance)) {
                if (floatval($distance->max_distance) > 0) {
                    if (floatval($distanceKm) <= floatval($distance->max_distance)) {
                        return $this->calculationPriceBasedRate($distance, $price, $distanceKm);
                    }
                }
            } else if (!empty($distance->min_distance) && empty($distance->max_distance)) {
                if (floatval($distance->min_distance) > 0) {
                    if (floatval($distanceKm) >= floatval($distance->min_distance)) {
                        return $this->calculationPriceBasedRate($distance, $price, $distanceKm);
                    }
                }
            } else {
                return $this->calculationPriceBasedRate($distance, $price, $distanceKm);
            }
        }
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
        } else if (empty($distance->min_order_price) && !empty($distance->max_order_price)) {
            if (floatval($distance->max_order_price) > 0 && $cartPrice > 0) {
                if ($cartPrice <= floatval($distance->max_order_price)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } else if (!empty($distance->min_order_price) && empty($distance->max_order_price)) {
            if (floatval($distance->min_order_price) > 0 && $cartPrice > 0) {
                if ($cartPrice >= floatval($distance->min_order_price)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            }
        } else {
            return $this->calculateDeliveryRate($distance, $distanceKm);
        }
    }
    /** Fuctionality based on Price Based Rate end **/
    /** Fuctionality based on Weight Based Rate Start **/
    public function calculationDistanceWeightBasedRate($distance, $weight, $quantity, $lineItem, $address)
    {
        $origin = null;
        if (!empty($distance->latitude) && !empty($distance->longitude)) {
            $origin = "{$distance->latitude},{$distance->longitude}";
        } else {
            $origin = $distance->street . ", " . $distance->city . ", " . $distance->country_region . ", " . $distance->postal_code;
        }
        $location = $this->getDistanceMatrix($origin, $address);
        if ($location['status'] && isset($location['distance'])) {
            $distanceKm = str_replace(" km", "", $location['distance']);
            // Log::info("Distence in KM weight", ['distance' => $distanceKm]);
            if (!empty($distance->min_distance) && !empty($distance->max_distance)) {
                if (floatval($distance->min_distance) > 0 && floatval($distance->max_distance) > 0) {
                    if (floatval($distanceKm) >= floatval($distance->min_distance) && floatval($distanceKm) <= floatval($distance->max_distance)) {
                        return $this->calculationWeightBasedRate($distance, $weight, $distanceKm);
                    }
                } else {
                    return;
                }
            } else if (empty($distance->min_distance) && !empty($distance->max_distance)) {
                if (floatval($distance->max_distance) > 0) {
                    if (floatval($distanceKm) <= floatval($distance->max_distance)) {
                        return $this->calculationWeightBasedRate($distance, $weight, $distanceKm);
                    }
                } else {
                    return;
                }
            } else if (!empty($distance->min_distance) && empty($distance->max_distance)) {
                if (floatval($distance->min_distance) > 0) {
                    if (floatval($distanceKm) >= floatval($distance->min_distance)) {
                        return $this->calculationWeightBasedRate($distance, $weight, $distanceKm);
                    }
                } else {
                    return;
                }
            } else {
                return $this->calculationWeightBasedRate($distance, $weight, $distanceKm);
            }
        }
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
            } else {
                return;
            }
        } else if (empty($distance->min_order_weight) && !empty($distance->max_order_weight)) {
            if (floatval($distance->max_order_weight) > 0 && $cartWeight > 0) {
                if (floatval($maxOrderWeight) >= $cartWeight) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            } else {
                return;
            }
        } else if (!empty($distance->min_order_weight) && empty($distance->max_order_weight)) {
            if (floatval($distance->min_order_weight) > 0 && $cartWeight > 0) {
                if ($cartWeight >= floatval($minOrderWeight)) {
                    return $this->calculateDeliveryRate($distance, $distanceKm);
                }
            } else {
                return;
            }
        } else {
            return $this->calculateDeliveryRate($distance, $distanceKm);
        }
    }
    /** Fuctionality based on Weight Based Rate end **/

    /** Common function for both Price and Weight Based Rate start**/
    public function calculateDeliveryRate($distance, $km)
    {
        $shipping = 0;
        $result = [];
        if (!empty($distance->max_delivery_rate) && (float)$distance->max_delivery_rate > 0) {
            if (!empty($distance->base_delivery_price) && !empty($distance->price_per_kilometer) || empty($distance->base_delivery_price) && !empty($distance->price_per_kilometer)) {
                $rate = floatval($distance->price_per_kilometer) * floatval($km);
                $shipping = floatval($distance->max_delivery_rate) >= floatval($rate) ? floatval($rate) : floatval($distance->max_delivery_rate);
            } else if (!empty($distance->base_delivery_price) && empty($distance->price_per_kilometer)) {
                $rate = floatval($distance->base_delivery_price);
                $shipping = floatval($distance->max_delivery_rate) >= floatval($rate) ? floatval($rate) : floatval($distance->max_delivery_rate);
            } else {
                $shipping = floatval($distance->max_delivery_rate);
            }
            array_push($result, array('status' => 1, 'service_name' => $distance->title, 'description' => $distance->description,  'shipPrice' => $shipping));
        } else {
            if (!empty($distance->base_delivery_price) && !empty($distance->price_per_kilometer) || empty($distance->base_delivery_price) && !empty($distance->price_per_kilometer)) {
                $shipping = floatval($distance->price_per_kilometer) * floatval($km);
            } else if (!empty($distance->base_delivery_price) && empty($distance->price_per_kilometer)) {
                $shipping = floatval($distance->base_delivery_price);
            } else {
                $shipping = 0;
            }
            array_push($result, array('status' => 1, 'service_name' => $distance->title, 'description' => $distance->description,  'shipPrice' => $shipping));
        }
        return $result;
    }
    /** Common function for both Price and Weight Based Rate end**/

    public function getDistanceMatrix($origin, $destination)
    {

        $apiKey = env('GOOGLE_MAP_API_KEY');
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'units'       => 'metric',
            'origins'     => $origin,
            'destinations' => $destination,
            'key'         => $apiKey,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'OK') {
                if (isset($data['rows'][0]['elements'][0])) {
                    $element = $data['rows'][0]['elements'][0];
                    if (isset($element['status']) && $element['status'] === 'OK') {
                        if (isset($element['distance']['text'])) {
                            $distanceText = $element['distance']['text'];
                            return ['status' => true, "distance" => $distanceText];
                        } else {
                            return ['status' => false];
                        }
                    } else {
                        return ['status' => false];
                    }
                } else {
                    return ['status' => false];
                }
            } else {
                return ['status' => false];
            }
        } else {
            return ['status' => false];
        }
    }

    public function convertToGrams($weight, $unit)
    {
        if (is_null($weight) || !is_numeric($weight) || floatval($weight) <= 0) {
            return 0;
        }

        // Convert unit to lowercase
        $unit = strtolower($unit);
        switch (strtolower($unit)) {
            case 'kg':
                return floatval($weight) * 1000; // 1 kg = 1000 grams
            case 'lb':
                return floatval($weight) * 453.592; // 1 lb = 453.592 grams
            case 'oz':
                return floatval($weight) * 28.3495; // 1 oz = 28.3495 grams
            default:
                return response()->json(['error' => 'Invalid weight unit'], 400);
        }
    }
}
