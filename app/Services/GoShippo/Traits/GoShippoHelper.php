<?php

namespace App\Services\GoShippo\Traits;

use App\Models\Carrier;
use Http;
use Exception;
use Log;
use App\Models\Dimension;
trait GoShippoHelper
{
    public function handleGoShippoPayload($credentials, $checkout): array
    {
        $result = [];

        try {
            // Ensure credentials is an array
            if (!is_array($credentials)) {
                return [];
            }

            foreach ($credentials as $cred) {
                if (!isset($cred['api_key']) || !isset($cred['id'])) {
                    continue;
                }
                $response = $this->createGoShhippoShipmentRate($cred['api_key'], $cred['id'], $checkout, $cred['user_id']);

                if (!empty($response)) {
                    $result[] = $response;
                } else {
                    Log::warning("Empty response received from createGoShippoCustomsInfo", ['credential' => $cred]);
                }
            }
            return !empty($result) ? $result : [];
        } catch (Exception $e) {
            Log::error("Exception in handleGoShippoPayload", ['error' => $e->getMessage()]);
            return [];
        }
    }
    public function createGoShhippoShipmentRate($apiKey, $serviceId, $checkout, $userId): array
    {
        try {

            $defaultDimension = [
                "weight" => "2",
                "length" => "2",
                "width" => "2",
                "height" => "2",
                "distance_unit" => "in",
                "mass_unit" => "lb"
            ];

            $smallestSortingValue = Dimension::where('user_id', $userId)->orderBy('sorting', 'ASC')->first();

            $enabledCarrierIds = Carrier::where('service_id', $serviceId)
                ->where('status', 'active')
                ->pluck('account_id')
                ->toArray();
    
            $ship = [
                "address_from" => [
                    "name" => @$checkout['rate']['origin']['name'] ?? @$checkout['rate']['origin']['company_name'],
                    "street1" => @$checkout['rate']['origin']['address1'] ?? "",
                    "city" => @$checkout['rate']['origin']['city'] ?? "",
                    "state" => @$checkout['rate']['origin']['province'] ?? "",
                    "zip" => @$checkout['rate']['origin']['postal_code'] ?? "",
                    "country" => @$checkout['rate']['origin']['country'] ?? "",
                    "phone" => @$checkout['rate']['origin']['phone'] ?? "",
                    "email" => @$checkout['rate']['origin']['email'] ?? ""
                ],
                "address_to" => [
                    "name" => @$checkout['rate']['destination']['name'] ?? @$checkout['rate']['destination']['company_name'],
                    "street1" => @$checkout['rate']['destination']['address1'] ?? "",
                    "city" => @$checkout['rate']['destination']['city'] ?? "",
                    "state" => @$checkout['rate']['destination']['province'] ?? "",
                    "zip" => @$checkout['rate']['destination']['postal_code'] ?? "",
                    "country" => @$checkout['rate']['destination']['country'] ?? "",
                    "phone" => @$checkout['rate']['destination']['phone'] ?? "",
                    "email" => @$checkout['rate']['destination']['email'] ?? ""
                ],
                "carrier_accounts" => $enabledCarrierIds,
                "parcels" => [
                    [
                      "length" => $smallestSortingValue->length ?? $defaultDimension["length"],
                        "width" => $smallestSortingValue->width ?? $defaultDimension["width"],
                        "height" => $smallestSortingValue->height ?? $defaultDimension["height"],
                        "weight" => $smallestSortingValue->weight ?? $defaultDimension["weight"],
                        "distance_unit" => "in",
                        "mass_unit" => "lb"
                    ]
                ],
                "async" => false
            ];
    
            $goShippo = config('app.carrier_service_api_url.shippo');
    
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'ShippoToken ' . $apiKey,
            ])->post($goShippo . "/shipments", $ship);
    
            $shipment = $response->json();
    
            if (!$response->successful()) {
                return [];
            }
    
            if (!isset($shipment['rates']) || empty($shipment['rates'])) {
                return [];
            }
            return !empty($shipment['rates']) ? $shipment['rates'] : [];
        } catch (Exception $e) {
            Log::error("Exception in createGoShippoShipmentRate", ['error' => $e->getMessage()]);
            return [];
        }
    }
}
