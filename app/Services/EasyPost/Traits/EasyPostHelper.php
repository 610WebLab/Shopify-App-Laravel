<?php

namespace App\Services\EasyPost\Traits;

use App\Models\Carrier;
use Http;
use Exception;
use Log;
use App\Models\Dimension;
trait EasyPostHelper
{

    public function handleEasyPostPayload($credentials, $checkout): array
    {
        $result = [];

        try {
            if (!is_array($credentials)) {
                return [];
            }
            foreach ($credentials as $cred) {
                if (!isset($cred['api_key']) || !isset($cred['id'])) {
                    continue;
                }
                $response = $this->createEasyPostCustomsInfo($cred['api_key'], $cred['id'], $checkout, $cred['user_id']);

                if (!empty($response)) {
                    $result[] = $response;
                } else {
                    Log::warning("Empty response received from createEasyPostCustomsInfo", ['credential' => $cred]);
                }
            }
            return !empty($result) ? $result : [];
        } catch (Exception $e) {
            Log::error("Exception in handleEasyPostPayload", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function createEasyPostCustomsInfo(string $apiKey, int $serviceId, array $checkout, $userId): array
    {
        try {
            $easypostUrl = config('app.carrier_service_api_url.easypost');
            $encodedApiKey = base64_encode($apiKey . ':');
    
            $filteredLineItems = $checkout['rate']['items'] ?? [];
            $originCountry = $checkout['rate']['origin']['country'] ?? 'US'; // Default to US
    
            $customs_items = array_map(function ($lineItem) use ($originCountry) {
                return [
                    "description" => $lineItem['name'] ?? 'N/A',
                    "quantity" => (int) ($lineItem['quantity'] ?? 1),
                    "weight" => (float) ($lineItem['grams'] ?? 0),
                    "value" => (float) ($lineItem['price'] ?? 0),
                    "origin_country" => $originCountry,
                ];
            }, $filteredLineItems);
    
            $jsonData = [
                'customs_info' => [
                    "customs_certify" => true,
                    "customs_signer" => $checkout['rate']['destination']['name'] ?? "",
                    "contents_type" => "merchandise",
                    "contents_explanation" => "",
                    "restriction_type" => "none",
                    "eel_pfc" => "NOEEI 30.37(a)",
                    "customs_items" => $customs_items,
                ]
            ];
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $encodedApiKey,
            ])->post("$easypostUrl/customs_infos", $jsonData);
            if ($response->successful()) {
                $result = $response->json();
                if (!empty($result['id'])) {
                    return $this->createEasyPostShipmentRate($apiKey, $result['id'], $checkout, $serviceId, $userId);
                }
            } else {
                Log::error('Failed to create customs info:', ['response' => $response->json()]);
                return [];
            }
        } catch (Exception $e) {
            Log::error("Error in createEasyPostCustomsInfo: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }
    public function createEasyPostShipmentRate($apiKey, $cstInfoID, $checkout, $serviceId, $userId)
    {
        try {
            $defaultDimension = [
                "length" => "10",
                "width" => "5",
                "height" => "2",
                "weight" => "16" 
            ];

            $smallestSortingValue = Dimension::where('user_id', $userId)->orderBy('sorting', 'ASC')->first();
            $easypostUrl = config('app.carrier_service_api_url.easypost');
            $encodedApiKey = base64_encode($apiKey . ':');
            $ship = [
                "shipment" => [
                    "to_address" => [
                        "name" => @$checkout['rate']['destination']['name'] ?? @$checkout['rate']['destination']['company_name'],
                        "street1" => @$checkout['rate']['destination']['address1'] ?? "",
                        "street2" => "",
                        "city" => @$checkout['rate']['destination']['city'] ?? "",
                        "state" => @$checkout['rate']['destination']['province'] ?? "",
                        "zip" => @$checkout['rate']['destination']['postal_code'] ?? "",
                        "country" => @$checkout['rate']['destination']['country'] ?? "",
                        "phone" => @$checkout['rate']['destination']['phone'] ?? "",
                        "email" => @$checkout['rate']['destination']['email'] ?? ""
                    ],
                    "from_address" => [
                        "name" => @$checkout['rate']['origin']['name'] ?? @$checkout['rate']['origin']['company_name'],
                        "street1" => @$checkout['rate']['origin']['address1'] ?? "",
                        "street2" => "",
                        "city" => @$checkout['rate']['origin']['city'] ?? "",
                        "state" => @$checkout['rate']['origin']['province'] ?? "",
                        "zip" => @$checkout['rate']['origin']['postal_code'] ?? "",
                        "country" => @$checkout['rate']['origin']['country'] ?? "",
                        "phone" => @$checkout['rate']['origin']['phone'] ?? "",
                        "email" => @$checkout['rate']['origin']['email'] ?? ""
                    ],
                    "parcel" => [

                       "length" => $smallestSortingValue->length ?? $defaultDimension["length"],
                        "width" => $smallestSortingValue->width ?? $defaultDimension["width"],
                        "height" => $smallestSortingValue->height ?? $defaultDimension["height"],
                        "weight" => $smallestSortingValue->weight ?? $defaultDimension["weight"],
                    ],
                    "customs_info" => [
                        "id" => $cstInfoID
                    ]
                ]
            ];
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $encodedApiKey,
            ])->post("$easypostUrl/shipments", $ship);
            $shipment = $response->json();
            if (!$response->successful()) {
                return [];
            }
            if (!isset($shipment['rates']) || empty($shipment['rates'])) {
                return [];
            }
            $enabledCarrierIds = Carrier::where('service_id', $serviceId)
                ->where('status', 'active')
                ->pluck('account_id')
                ->toArray();
            $filteredRates = collect($shipment['rates'])->filter(function ($rate) use ($enabledCarrierIds) {
                return in_array($rate['carrier_account_id'], $enabledCarrierIds);
            })->values()->toArray();
    
            return !empty($filteredRates) ? $filteredRates : [];
        } catch (Exception $e) {
            Log::error("Error in createEasyPostShipmentRate: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [];
        }
    
    }


    // public function createEasyPostShipmentRate($apiKey, $cstInfoID, $checkout, $serviceId)
    // {
    //     Log::info("Triggered createEasyPostShipmentRate", [
    //         'apiKey' => $apiKey,
    //         'cstInfoID' => $cstInfoID,
    //         'checkout' => $checkout
    //     ]);
    //     $easypostUrl = config('app.carrier_service_api_url.easypost');
    //     $encodedApiKey = base64_encode($apiKey . ':');
    //     // $ship = [
    //     //     "shipment" => [
    //     //         "to_address" => [
    //     //             "name" => @$checkout['rate']['origin']['name'] ?? @$checkout['rate']['origin']['company_name'],
    //     //             "street1" => @$checkout['rate']['origin']['address1'] ?? "",
    //     //             "street2" => "",
    //     //             "city" => @$checkout['rate']['origin']['city'] ?? "",
    //     //             "state" => @$checkout['rate']['origin']['province'] ?? "",
    //     //             "zip" => @$checkout['rate']['origin']['postal_code'] ?? "",
    //     //             "country" => @$checkout['rate']['origin']['country'] ?? "",
    //     //             "phone" => @$checkout['rate']['origin']['phone'] ?? "",
    //     //             "email" => @$checkout['rate']['origin']['email'] ?? ""
    //     //         ],
    //     //         "from_address" => [
    //     //             "name" => @$checkout['rate']['destination']['name'] ?? @$checkout['rate']['destination']['company_name'],
    //     //             "street1" => @$checkout['rate']['destination']['address1'] ?? "",
    //     //             "street2" => "",
    //     //             "city" => @$checkout['rate']['destination']['city'] ?? "",
    //     //             "state" => @$checkout['rate']['destination']['province'] ?? "",
    //     //             "zip" => @$checkout['rate']['destination']['postal_code'] ?? "",
    //     //             "country" => @$checkout['rate']['destination']['country'] ?? "",
    //     //             "phone" => @$checkout['rate']['destination']['phone'] ?? "",
    //     //             "email" => @$checkout['rate']['destination']['email'] ?? ""
    //     //         ],
    //     //         "parcel" => [
    //     //             "length" => "7",
    //     //             "width" => "6",
    //     //             "height" => "1.3",
    //     //             "weight" => "15"
    //     //         ],
    //     //         "customs_info" => [
    //     //             "id" => $cstInfoID
    //     //         ]
    //     //     ]
    //     // ];

    //     $ship = [
    //         "shipment" => [
    //             "to_address" => [
    //                 "name" => "Easy Clothes",
    //                 "street1" => "6121 Santa Monica Blvd",
    //                 "street2" => "",
    //                 "city" => "Los Angeles",
    //                 "state" => "CA",
    //                 "zip" => "90038",
    //                 "country" => "US",
    //                 "phone" => "+1 (213) 693 0950",
    //                 "email" => "mlechanteur@easy-clothes.com"
    //             ],
    //             "from_address" => [
    //                 "name" => "Test",
    //                 "street1" => "1901 West Main Street",
    //                 "street2" => "",
    //                 "city" => "Battle Ground",
    //                 "state" => "WA",
    //                 "zip" => "98604",
    //                 "country" => "US",
    //                 "phone" => "",
    //                 "email" => ""
    //             ],
    //             "parcel" => [
    //                 "length" => "7",
    //                 "width" => "6",
    //                 "height" => "1.3",
    //                 "weight" => "15"
    //             ],
    //             "customs_info" => [
    //                 "id" => $cstInfoID
    //             ]
    //         ]
    //     ];

    //     $response = Http::withHeaders([
    //         'Content-Type' => 'application/json',
    //         'Authorization' => 'Basic ' . $encodedApiKey,
    //     ])->post($easypostUrl . "/shipments", $ship);
    //     $shipment = $response->json();
    //     Log::info("EasyPost Create Shipment Rate", ['data' => $shipment]);
    //     if ($response->successful()) {
    //         $shipMent = $response->json();
    //         if (isset($shipMent['rates']) && count($shipMent['rates']) > 0) {
    //             $enabledCarrierIds = Carrier::where('service_id', $serviceId)->where('status', 'active')->pluck('account_id')->toArray();
    //             $filteredRates = collect($shipMent['rates'])->filter(function ($rate) use ($enabledCarrierIds) {
    //                 return in_array($rate['carrier_account_id'], $enabledCarrierIds);
    //             })->values()->toArray();
    //             Log::info("typeofShipMentRate", ['type' => gettype($filteredRates)]);
    //             return $filteredRates;
    //         } else {
    //             return [];
    //         }
    //     } else {
    //         Log::error($response->json());
    //         return ['status' => false];
    //     }
    // }

    public function getEasyPostShippingRates() {}

    public function createEasyPostShippingLabel() {}
}
