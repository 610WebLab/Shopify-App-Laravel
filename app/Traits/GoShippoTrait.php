<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\OtherCarrierService;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\Dimension;
use Http;
use Log;
trait GoShippoTrait {
    public function processGoShippoCarrier($shop, $service_type, $request)
    {
        $apiKey = $request->input('api_key');
        if (empty($apiKey)) {
            throw new \Exception('API key is required for EasyPost.');
        }
        $getAccount = $this->getGoShippoActiveAccount($apiKey);
        if ($getAccount['status']) {
            $carrier = OtherCarrierService::create([
                'user_id' => $shop->id,
                'carrier_type' => $service_type,
                'api_key' => $request->api_key,
                'status' => $request->status
            ]);
            $carrierData = [];
            foreach ($getAccount['data']['results'] as $account) {
                $data['user_id'] = $shop->id;
                $data['service_id'] = $carrier->id;
                $data['name'] = $account['carrier_name'];
                $data['account_id'] = $account['object_id'];
                $data['status'] = 'inactive';
                $data['created_at'] = now();
                $data['updated_at'] = now();
                $carrierData[] = $data;
            }
               $result = Carrier::insert($carrierData);
            if ($result) {
                return response()->json(['status' => true, 'message' => 'Carrier service created successfully', 'data' => $carrier]);
            } else {
                return response()->json(['status' => false, 'message' => 'Failed to create carrier service']);
            }
        }
    }
    public function updateGoShippoCarrier($shop, $service_type, $request, $id)
    {
        try {
            $carrier = OtherCarrierService::find($id);
            if (!$carrier) {
                return response()->json(['status' => false, 'message' => 'Carrier service not found']);
            }
            $apiKey = $request->input('api_key');
            $status = $request->input('status');

            if (empty($apiKey)) {
                return response()->json(['status' => false, 'message' => 'API key is required for EasyPost.']);
            }

            $getAccount = $this->getGoShippoActiveAccount($apiKey);
            if (!$getAccount['status']) {
                return response()->json(['status' => false, 'message' => 'Failed to retrieve EasyPost account details.']);
            }
            $carrier->carrier_type = $service_type;
            $carrier->api_key = $apiKey;
            $carrier->status = $status;
            $carrier->save();

            foreach ($getAccount['data']['results'] as $account) {
                $existingCarrier = Carrier::where('account_id', $account['account_id'])
                    ->where('service_id', $carrier->id)
                    ->first();

                Carrier::updateOrCreate(
                    [
                        'account_id' => $account['object_id'],
                        'service_id' => $carrier->id
                    ],
                    [
                        'user_id' => $shop->id,
                        'name' => $account['carrier_name'],
                        'status' => $existingCarrier ? $existingCarrier->status : 'inactive'
                    ]
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Carrier service updated successfully',
                'data' => $carrier
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    private function getGoShippoActiveAccount($apiKey)
    {
        $goShippo = config('app.carrier_service_api_url.shippo');
        // $encodedApiKey = base64_encode($apiKey . ':');
        // dd($easypostUrl, $encodedApiKey, config('app.carrier_service_base_url.easypost'));
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'ShippoToken ' . $apiKey,
        ])->get($goShippo . "/carrier_accounts?results=20");
        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()
            ];
        } else {
            return [
                'status' => false,
                'message' => $response->json()
            ];
        };
    }



    public function createGoShhippoShipmentRate($carrier_service,$shopifyOrder,$user,$carrier_id, $dimension_id)
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

            $smallestSortingValue = Dimension::where('user_id', $user->id)->where('id', $dimension_id)->first();
            $enabledCarrierIds = Carrier::where('id', $carrier_id)
                ->where('status', 'active')
                ->pluck('account_id')
                ->toArray();
                $shopInfo = $this->getShopInfo2($user);

            $ship = [
            //     "address_to"=> [
            //         "name"=> "Mr Hippo",
            //         "street1"=> "965 Mission St #572",
            //         "city"=> "San Francisco",
            //         "state"=> "CA",
            //         "zip"=> "94103",
            //         "country"=> "US",
            //         "phone"=> "4151234567",
            //         "email"=> "mrhippo@shippo.com"
            //   ],
            //     "address_from"=> [
            //         "name"=> "Mrs Hippo",
            //         "street1"=> "1092 Indian Summer Ct",
            //         "city"=> "San Jose",
            //         "state"=> "CA",
            //         "zip"=> "95122",
            //         "country"=> "US",
            //         "phone"=> "4159876543",
            //         "email"=> "mrshippo@shippo.com"
            //     ],
                        
                "address_from" => [
                    "name" => @$shopInfo['name'] ??"",
                    "street1" => @$shopInfo['address1'] ?? "",
                    "street2" => "",
                    "city" => @$shopInfo['city'] ?? "",
                    "state" => @$shopInfo['province_code'] ?? "",
                    "zip" => @$shopInfo['zip'] ?? "",
                    "country" => @$shopInfo['country_code'] ?? "",
                    "phone" => @$shopInfo['phone'] ?? "",
                    "email" => @$shopInfo['email'] ?? ""
                ],
                "address_to" => [
                    "name" => @$shopifyOrder['shipping_address']['name'] ?? @$shopifyOrder['shipping_address']['company'],
                    "street1" => @$shopifyOrder['shipping_address']['address1'] ?? "",
                    "street2" => "",
                    "city" => @$shopifyOrder['shipping_address']['city'] ?? "",
                    "state" => @$shopifyOrder['shipping_address']['province_code'] ?? "",
                    "zip" => @$shopifyOrder['shipping_address']['zip'] ?? "",
                    "country" => @$shopifyOrder['shipping_address']['country_code'] ?? "",
                    "phone" => @$shopifyOrder['shipping_address']['phone'] ?? "",
                    "email" => @$shopifyOrder['shipping_address']['email'] ?? ""
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
                'Authorization' => 'ShippoToken ' . $carrier_service->api_key,
            ])->post($goShippo . "/shipments", $ship);
    
            $shipment = $response->json();
    
            if (!$response->successful()) {
                return [];
            }
    
            if (!isset($shipment['rates']) || empty($shipment['rates'])) {
                return [];
            }
            $modifiedRates = array_map(function ($rate) {
                return [
                    'rate'          => $rate['amount'] ?? null,          // Rename amount to rate
                    'id'            => $rate['object_id'] ?? null,       // Rename object_id to id
                    'shipment_id'   => $rate['shipment'] ?? null,          // Rename shipment to shipment_id
                    'service'       => $rate['provider'] ?? null,          // Rename provider to service
                    'delivery_days' => $rate['estimated_days'] ?? null,    // Rename estimated_days to delivery_days
                ];
            }, $shipment['rates']);
    
            return !empty($modifiedRates) ? $modifiedRates : [];
        } catch (Exception $e) {
            Log::error("Exception in createGoShippoShipmentRate", ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function createShippoLabel($service, $request){
        $apiKey = $service->api_key;
    
        // Step 3: Prepare API Payload
        $payload = [
            'rate' => $request->rate_id,
             "async"=> false,
            "label_file_type"=>"PDF"
        ];
        $easypostUrl = config('app.carrier_service_api_url.shippo');

        // Step 4: Make API Request to EasyPost
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'ShippoToken ' . $apiKey,
            ])
            ->post("$easypostUrl/transactions/", $payload);
    
        // Step 5: Handle API Response
        if ($response->successful()) {
            $responseData = $response->json();
            $labelUrl = $responseData['label_url']?? null;
            // Check if we have an order ID to update
            if (!empty($request->order_id)) {
                $order = Order::where('id', $request->order_id)->first();
    
                if ($order) {
                    $order->label_url = $labelUrl;
                    $order->shipping_service = $request->service_id;
                    $order->carrier_id = $request->carrier_id;
                    $order->group_title = $request->group_title;
                    $order->carrier_label = $request->carrier_label;

                    $order->save();
                }
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Shipping label created and order updated successfully.',
                'data' => $responseData,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipping label.',
                'error' => $response->json(),
            ], 400);
        }
    }

    public function getShopInfo2($shop){
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop->password,
        ])->get('https://'.$shop->name.'/admin/api/2025-01/shop.json');

      return $response->json();
    }
}