<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\OtherCarrierService;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\Dimension;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Http;
use Log;

trait EasyPostTrait
{
    /**
     * Process and save an EasyPost carrier.
     *
     * @param  \App\Models\User  $shop
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Carrier
     *
     * @throws \Exception
     */
    public function processEasyPostCarrier($shop, $service_type, $request)
    {
        $apiKey = $request->input('api_key');
        $status = $request->input('status');
        if (empty($apiKey)) {
            throw new \Exception('API key is required for EasyPost.');
        }
        $getAccount = $this->getEasyPostActiveAccount($apiKey);
        if ($getAccount['status']) {
            $carrier = OtherCarrierService::create([
                'user_id' => $shop->id,
                'carrier_type' => $service_type,
                'api_key' => $request->api_key,
                'status' => $request->status
            ]);
            $carrierData = [];
            foreach ($getAccount['data'] as $account) {
                $data['user_id'] = $shop->id;
                $data['service_id'] = $carrier->id;
                $data['name'] = $account['readable'];
                $data['account_id'] = $account['id'];
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
    public function updateEasyPostCarrier($shop, $service_type, $request, $id)
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

            $getAccount = $this->getEasyPostActiveAccount($apiKey);
            if (!$getAccount['status']) {
                return response()->json(['status' => false, 'message' => 'Failed to retrieve EasyPost account details.']);
            }
            $carrier->carrier_type = $service_type;
            $carrier->api_key = $apiKey;
            $carrier->status = $status;
            $carrier->save();

            foreach ($getAccount['data'] as $account) {
                $existingCarrier = Carrier::where('account_id', $account['id'])
                    ->where('service_id', $carrier->id)
                    ->first();

                Carrier::updateOrCreate(
                    [
                        'account_id' => $account['id'],
                        'service_id' => $carrier->id
                    ],
                    [
                        'user_id' => $shop->id,
                        'name' => $account['readable'],
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


    private function getEasyPostActiveAccount($apiKey)
    {
        $easypostUrl = env('EASY_POST_BASE_URL');
        $encodedApiKey = base64_encode($apiKey . ':');
        // dd($easypostUrl, $encodedApiKey, config('app.carrier_service_base_url.easypost'));
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $encodedApiKey,
        ])->get($easypostUrl . "/carrier_accounts");

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


    public function createEasyPostCustomsInfo($carrier_service, $shopifyOrder, $user, $carrier_id, $dimension_id)
    {
        try {
            $easypostUrl = config('app.carrier_service_api_url.easypost');

            $encodedApiKey = base64_encode($carrier_service->api_key . ':');

            $filteredLineItems = $shopifyOrder['line_items'] ?? [];
            $shopInfo = $this->getShopInfo($user);
            $originCountry = $shopInfo['shop']['country'] ?? 'US'; // Default to US

            if (!$shopInfo['shop']) {
                return response()->json(['status' => false, "message" => "Shop not found"]);
            }

            $customs_items = array_map(function ($lineItem) use ($originCountry, $shopInfo) {
                return [
                    "description" => $lineItem['name'] ?? 'N/A',
                    "quantity" => (int) ($lineItem['quantity'] ?? 1),
                    "weight" => (float) ($lineItem['grams'] ?? 0),
                    "value" => (float) ($lineItem['price'] ?? 0),
                    "origin_country" => $originCountry,
                ];
            }, $filteredLineItems);
            $customs_signer = !empty($shopifyOrder['shipping_address']['name'])
                ? $shopifyOrder['shipping_address']['name']
                : (!empty($shopifyOrder['shipping_address']['company'])
                    ? $shopifyOrder['shipping_address']['company']
                    : "");

            $jsonData = [
                'customs_info' => [
                    "customs_certify" => true,
                    "customs_signer" => $customs_signer,
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
                    return $this->createEasyPostShipmentRate($carrier_service->api_key, $result['id'], $shopifyOrder, $carrier_service->id, $shopInfo['shop'], $carrier_id, $user, $dimension_id);
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

    public function createEasyPostShipmentRate($apiKey, $cstInfoID, $shopifyOrder, $serviceId, $shopInfo, $carrier_id, $user, $dimension_id)
    {
        try {
            $defaultDimension = [
                "length" => "10",
                "width" => "5",
                "height" => "2",
                "weight" => "16" 
            ];

            $smallestSortingValue = Dimension::where('user_id', $user->id)->where('id', $dimension_id)->first();
            $easypostUrl = config('app.carrier_service_api_url.easypost');
            $encodedApiKey = base64_encode($apiKey . ':');
            $ship = [
                // "shipment" => [
                //     "to_address" => [
                //         "name" => "John Doe",
                //         "street1" => "123 Test St",
                //         "street2" => "Suite 100",
                //         "city" => "Los Angeles",
                //         "state" => "CA",
                //         "zip" => "90001",
                //         "country" => "US",
                //         "phone" => "555-555-5555",
                //         "email" => "john@example.com"
                //     ],
                //     "from_address" => [
                //         "name" => "Jane Smith",
                //         "street1" => "456 Sample Ave",
                //         "street2" => "Apt 200",
                //         "city" => "New York",
                //         "state" => "NY",
                //         "zip" => "10001",
                //         "country" => "US",
                //         "phone" => "555-555-5555",
                //         "email" => "jane@example.com"
                //     ],
                //     "parcel" => [
                //         "length" => $smallestSortingValue->length ?? $defaultDimension["length"],
                //         "width" => $smallestSortingValue->width ?? $defaultDimension["width"],
                //         "height" => $smallestSortingValue->height ?? $defaultDimension["height"],
                //         "weight" => $smallestSortingValue->weight ?? $defaultDimension["weight"],
                //     ],
                //     "customs_info" => [
                //         "id" => $cstInfoID
                //     ]
                // ]
                "shipment" => [
                    "to_address" => [
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
                    "from_address" => [
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
            $enabledCarrierIds = Carrier::where('id', $carrier_id)
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


    public function getShopInfo($shop)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $shop->password,
        ])->get('https://' . $shop->name . '/admin/api/2025-01/shop.json');

        return $response->json();
    }

    public function createEasyPostLabel($service, $request)
    {

        $apiKey = $service->api_key;

        // Step 3: Prepare API Payload
        $payload = [
            'rate' => [
                'id' => $request->rate_id,
            ],
            'insurance' => $request->rate_price, // Use the rate price from request
        ];
        $easypostUrl = config('app.carrier_service_api_url.easypost');

        // Step 4: Make API Request to EasyPost
        $response = Http::withBasicAuth($apiKey, '')
            ->post("$easypostUrl/shipments/$request->shipment_id/buy", $payload);

        // Step 5: Handle API Response
        if ($response->successful()) {
            $responseData = $response->json();
            $trackingCode = $responseData['tracking_code'] ?? null;
            $labelUrl = $responseData['postage_label']['label_url'] ?? null;
            $status = $responseData['status'] ?? 'unknown';
            $shipmentId = $responseData['id'] ?? null;

            // Check if we have an order ID to update
            if (!empty($request->order_id) && $labelUrl) {
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

    protected function convertImageToPdf($imageUrl)
    {
        PDF::setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        // Fetch image data using file_get_contents (or cURL if necessary)
        $imageData = @file_get_contents($imageUrl);
        if ($imageData) {
            $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
        } else {
            // Fallback to URL if fetching fails
            $base64Image = $imageUrl;
        }

        // Build a clean HTML document. We add a minimal header text to force content rendering on the first page.
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Shipping Label PDF</title>
            <style>
                @page { margin: 0; }
                body { margin: 0; padding: 0; }
                .header-text { font-size: 1px; visibility: hidden; }
                img { display: block; width: 100%; height: auto; }
            </style>
        </head>
        <body>
            <div class="header-text">Shipping Label</div>
            <img src="{$base64Image}" alt="Shipping Label">
        </body>
        </html>
        HTML;

        // Remove any extra BOM/whitespace
        $html = ltrim($html, "\xEF\xBB\xBF \t\n\r");

        // Generate the PDF (A4, portrait)
        $pdf = PDF::setPaper('a4', 'portrait')->loadHTML($html);

        // Save the PDF to storage
        $fileName = 'easypost_label_' . time() . '.pdf';
        $storagePath = 'local_labels/' . $fileName;
        Storage::disk('public')->makeDirectory('local_labels');
        Storage::disk('public')->put($storagePath, $pdf->output());
        $pdfUrl = Storage::disk('public')->url($storagePath);

        return $pdfUrl;
    }

}
