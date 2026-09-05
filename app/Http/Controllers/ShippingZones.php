<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Shippingzone;
use App\Models\Shippingmethod;
use App\Models\Zonemethod;
use App\Models\Flaterate;
use App\Models\Localpickup;
use App\Models\Freeshipping;
use App\Models\Tablerateoption;
use App\Models\Tablerates;
use App\Traits\FreeShippingTrait;
use App\Traits\TableRateTrait;
use App\Traits\LocalPickupTrait;
use App\Traits\FlatRateTrait;
use App\Traits\DistanceRatesTrait;
use App\Models\RatesByDistance;
use App\Models\OtherCarrierService;
use Carbon\Carbon;
use App\Resolvers\ShippingServiceResolver;
use Exception;
use DB;

class ShippingZones extends Controller
{
    use FreeShippingTrait, LocalPickupTrait, FlatRateTrait, TableRateTrait, DistanceRatesTrait;
    protected $shippingServiceResolver;

    public function __construct(ShippingServiceResolver $shippingServiceResolver)
    {
        $this->shippingServiceResolver = $shippingServiceResolver;
    }

    public function apiResonse(Request $request)
    {
        try {
            $checkOutData = $request->all();
            $shopId = $request->query('shop_id') ?? ($checkOutData['shop_id'] ?? null);

            Log::info('[CarrierService] Incoming request', [
                'shop_id' => $shopId,
                'method' => $request->method(),
                'query' => $request->query(),
                'has_rate_payload' => isset($checkOutData['rate']),
                'payload' => $checkOutData,
            ]);

            if (empty($shopId)) {
                Log::warning('[CarrierService] shop_id missing in request');
                return response()->json(['rates' => []]);
            }

            if (!isset($checkOutData['rate']['destination']) || !isset($checkOutData['rate']['items'])) {
                Log::warning('[CarrierService] Invalid Shopify rate payload', [
                    'shop_id' => $shopId,
                    'payload_keys' => array_keys($checkOutData),
                ]);
                return response()->json(['rates' => []]);
            }

            $rate = [];
            $price = 0;
            $weight = 0;
            $quantity = 0;
            $lineItem = 0;

            $destination = $checkOutData['rate']['destination'];
            $country = $destination['country'] ?? '';
            $state = $destination['province'] ?? '';
            $postCode = $destination['postal_code'] ?? '';
            $address = $destination['address1'] ?? '';

            Log::info('[CarrierService] Destination parsed', [
                'shop_id' => $shopId,
                'country' => $country,
                'state' => $state,
                'post_code' => $postCode,
                'address' => $address,
                'city' => $destination['city'] ?? '',
            ]);

            $lineItem = count($checkOutData['rate']['items']);
            foreach ($checkOutData['rate']['items'] as $checkout) {
                $price = $price + ($checkout['price'] * $checkout['quantity']);
                $weight = $weight + ($checkout['grams'] * $checkout['quantity']);
                $quantity = $quantity + $checkout['quantity'];
            }

            Log::info('[CarrierService] Cart summary', [
                'shop_id' => $shopId,
                'line_items' => $lineItem,
                'total_price_cents' => $price,
                'total_weight_grams' => $weight,
                'total_quantity' => $quantity,
                'currency' => $checkOutData['rate']['currency'] ?? null,
            ]);

            $flateRate = $this->decodeShippingResult($this->flatRateShipping($country, $state, $postCode, $shopId));
            $localPickup = $this->decodeShippingResult($this->localPickUpShipping($country, $state, $postCode, $shopId));
            $freeShips = $this->decodeShippingResult($this->minimumOrderAmount($price, $country, $state, $postCode, $shopId));
            $tableRate = $this->decodeShippingResult($this->tableRateShipping($country, $state, $postCode, $price, $weight, $quantity, $lineItem, $shopId));
            $distanceRate = $this->decodeShippingResult($this->DistanceRateShipping($country, $state, $postCode, $address, $price, $weight, $quantity, $lineItem, $shopId));
            $easyPostRate = $this->calculateRate('easypost', $shopId, $checkOutData);
            $goShippoRate = $this->calculateRate('shippo', $shopId, $checkOutData);

            Log::info('[CarrierService] Rate sources resolved', [
                'shop_id' => $shopId,
                'flat_rate_count' => is_array($flateRate) ? count($flateRate) : 0,
                'local_pickup_count' => is_array($localPickup) ? count($localPickup) : 0,
                'free_shipping_count' => is_array($freeShips) ? count($freeShips) : 0,
                'table_rate_count' => is_array($tableRate) ? count($tableRate) : 0,
                'distance_rate_count' => is_array($distanceRate) ? count($distanceRate) : 0,
                'easypost' => $easyPostRate,
                'goshippo' => $goShippoRate,
            ]);

            $shipData = [];
        if ($flateRate) {
            foreach ($flateRate as $flat) {
                if ($flat['status'] == 1) {
                    array_push($shipData, $this->createShippingRates(
                        $flat['service_name'],
                        "shipping_rates_" . rand(),
                        $flat['shipPrice'],
                        $flat['description'],
                        $checkOutData['rate']['currency']
                    ));
                }
            }
        }


        if ($localPickup) {
            foreach ($localPickup as $local) {
                if ($local['status'] == 1) {
                    array_push($shipData, $this->createShippingRates(
                        $local['service_name'],
                        "shipping_rates_" . rand(),
                        $local['shipPrice'],
                        $local['description'],
                        $checkOutData['rate']['currency']
                    ));
                }
            }
        }

        if ($freeShips) {
            foreach ($freeShips as $free) {
                if ($free['status'] == 1) {
                    array_push($shipData, $this->createShippingRates(
                        $free['service_name'],
                        "shipping_rates_" . rand(),
                        $free['shipPrice'],
                        $free['description'],
                        $checkOutData['rate']['currency']
                    ));
                }
            }
        }

        if ($tableRate) {
            foreach ($tableRate as $table) {
                foreach ($table as $tblrate) {
                    if ($tblrate['status'] == 1) {
                        array_push($shipData, $this->createShippingRates(
                            $tblrate['service_name'],
                            "shipping_rates_" . rand(),
                            $tblrate['shipPrice'],
                            $tblrate['description'],
                            $checkOutData['rate']['currency']
                        ));
                    }
                }
            }
        }

        if ($distanceRate) {
            foreach ($distanceRate as $distance) {
                foreach ($distance as $distRate) {
                    if ($distRate['status'] == 1) {
                        array_push($shipData, $this->createShippingRates(
                            $distRate['service_name'],
                            "shipping_rates_" . rand(),
                            $distRate['shipPrice'],
                            $distRate['description'],
                            $checkOutData['rate']['currency']
                        ));
                    }
                }
            }
        }
        if (!empty($easyPostRate['rate']) && is_array($easyPostRate['rate'])) {
            if (isset($easyPostRate['service']) && !empty($easyPostRate['service'])) {
                foreach ($easyPostRate['rate'] as $serviceRate) {
                    foreach ($serviceRate as $carrier) {
                        if (!empty($carrier)) {
                            array_push($shipData, $this->createShippingRates(
                                $carrier['service'],
                                $easyPostRate['service'] . "_" . $carrier['carrier'] . "_" . $carrier['service'] . "_" . $carrier['id'],
                                $carrier['rate'],
                                "",
                                $checkOutData['rate']['currency']
                            ));
                        }
                    }
                }
            }
        }
        if (!empty($goShippoRate['rate']) && is_array($goShippoRate['rate'])) {
            if (isset($goShippoRate['service']) && !empty($goShippoRate['service'])) {
                foreach ($goShippoRate['rate'] as $serviceRate) {
                    foreach ($serviceRate as $carrier) {
                        if (!empty($carrier)) {
                            array_push($shipData, $this->createShippingRates(
                                $carrier['servicelevel']['name'],
                                $goShippoRate['service'] . "_" . $carrier['provider'] . "_" . $carrier['servicelevel']['name'] . "_" . $carrier['object_id'],
                                $carrier['amount'],
                                "",
                                $checkOutData['rate']['currency']
                            ));
                        }
                    }
                }
            }
        }

            $rate['rates'] = $shipData;

            Log::info('[CarrierService] Final response', [
                'shop_id' => $shopId,
                'rates_count' => count($shipData),
                'rates' => $shipData,
            ]);

            return response()->json($rate);
        } catch (Exception $e) {
            Log::error('[CarrierService] Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['rates' => []]);
        }
    }

    private function decodeShippingResult($result)
    {
        if (is_array($result)) {
            return $result;
        }

        if (is_string($result)) {
            return json_decode($result, true) ?? [];
        }

        if (is_object($result) && method_exists($result, 'getContent')) {
            return json_decode($result->getContent(), true) ?? [];
        }

        return [];
    }

    public function calculateRate($service, $shopId, $checkOutData)
    {
        try {
            $packageDetails = OtherCarrierService::where('carrier_type', $service)->where('user_id', $shopId)->where('status', 'active')->get()->toArray();
            $shippingService = $this->shippingServiceResolver->resolve($service);
            $rate = $shippingService->getShippingRate($packageDetails, $checkOutData);
            return ['service' => $service, 'rate' => $rate];
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function createShippingRates($service_name, $service_code, $price, $description, $currency)
    {
        $data = [
            "service_name" => $service_name,
            "service_code" => $service_code,
            "total_price" => $price * 100,
            "description" => $description,
            "currency" => $currency,
            "min_delivery_date" => Carbon::now()->format('Y-m-d H:i:s O'),
            "max_delivery_date" => Carbon::now()->format('Y-m-d H:i:s O')
        ];
        return $data;
    }

    public function calCulateTableRateShipping(Request $request)
    {

        $flatRate = json_decode($this->flatRateShipping($request->country, $request->state, $request->zip), true);
        $localPickup = json_decode($this->localPickUpShipping($request->country, $request->state, $request->zip), true);
        $freeShips = json_decode($this->minimumOrderAmount($request->price, $request->country, $request->state, $request->zip), true);
        $tableRate = json_decode($this->tableRateShipping($request->country, $request->state, $request->zip, $request->price, $request->weight, $request->item, $request->line), true);

        return [
            'flatRate' => $flatRate,
            'localPickup' => $localPickup,
            'freeShips' => $freeShips,
            'tableRate' => $tableRate
        ];
    }

    public function updateCheckoutApi(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        $url = "http://" . $shop->name . "/admin/api/" . config('shopify-app.api_version') . "/checkouts/" . $request->token . ".json";
        $data["token"] = $request->token;
        $data["shipping_line"] = ["handle" => "local pickup"];
        $headers = array();
        $headers['Content-Type'] = 'application/json';
        $headers['X-Shopify-Access-Token'] = $shop->password;
        $checkout = Http::withHeaders($headers)->put($url);
        return $checkout->body();
    }



    public function index(Request $request)
    {
        $input = $request->all();
        if (isset($input['shop']) && !empty($input['shop'])) {
            $shop = User::where('name', $input['shop'])->first();
            if ($shop) {
                if ($shop->status === 'false') {
                    $url = "https://" . $shop->name . "/admin/api/" . config('shopify-app.api_version') . "/carrier_services.json";

                    $services['carrier_service'] = [
                        "name" => "Shipping Rates Provider-1",
                        "callback_url" => route('shipping-rates-webhook') . '?shop_id=' . $shop->id,
                        "service_discovery" => true
                    ];

               
                    $carrierserv = json_encode($services);
                    $result = $this->carrierServices($url, $carrierserv, $shop->password);
                    $resError = json_decode($result, true);
             
                    if (!empty($resError['errors']) && isset($resError['errors'])) {
                        return json_encode(['status' => 0, 'msg' => $resError['errors']['base'][0]]);
                    } 
                   
                    $response = json_decode($result, true);
                    if (isset($response["carrier_service"]) && !empty($response["carrier_service"])) {
                        $shop->status = "true";
                        $shop->save();
                    }
                }
                $shop_zone =  Shippingzone::with('local_pickup_method')->with('free_shipping_method')->with('flat_rate_method')->with('table_rate_method')->with('rate_by_distance')->where('user_id', $shop->id)->get();
                // $shopZone = Shippingzone::where('user_id', $shop->id)->get();
                $region = [];
                foreach ($shop_zone as $zone) {
                    $zone->zone_region = json_decode($zone->zone_region, true);
                    if (!empty($zone->zone_region)) {
                        foreach ($zone->zone_region as $value) {
                            if (str_word_count($value['label'])) {
                                array_push($region, str_replace(strchr($value['label'], ","), "", $value['label']));
                            } else {
                                array_push($region, $value['label']);
                            }
                        }
                        $zone->zone_region = implode(", ", $region);
                        $region = [];
                    } else {
                        $zone->zone_region = $zone->ship_zone;
                    }
                }
                $result = [
                    'status' => 1,
                    'shopZone' => $shop_zone
                ];
            } else {
                $result = [
                    'status' => 0,
                    'mesage' => 'Shop Not Found'
                ];
            }
            return json_encode($result);
        }
    }

    public function getShippingzoneOptions(Request $request)
    {
        $input = $request->all();
        if (isset($input['shop']) && !empty($input['shop'])) {
            $shop = User::where('name', $input['shop'])->first();
            if ($shop) {
                // Retrieve all shipping zones for the shop.
                $zones = Shippingzone::where('user_id', $shop->id)->get();
    
                // For each zone, fetch shipping methods using UNION ALL.
                foreach ($zones as $zone) {
                    // Build query for free shipping methods.
                    $free = Freeshipping::select(
                                'freeshippings.id',
                                'freeshippings.title',
                                'freeshippings.status',
                                'shippingmethods.ship_method',
                                'shippingmethods.ship_desc'
                            )
                            ->join('shippingmethods', 'freeshippings.shipping_method_id', '=', 'shippingmethods.id')
                            ->where('zone_id', $zone->id);
    
                    // Build query for flat rate methods.
                    $flat = Flaterate::select(
                                'flaterates.id',
                                'flaterates.title',
                                'flaterates.status',
                                'shippingmethods.ship_method',
                                'shippingmethods.ship_desc'
                            )
                            ->join('shippingmethods', 'flaterates.shipping_method_id', '=', 'shippingmethods.id')
                            ->where('zone_id', $zone->id);
    
                    // Build query for local pickup methods.
                    $local = Localpickup::select(
                                'localpickups.id',
                                'localpickups.title',
                                'localpickups.status',
                                'shippingmethods.ship_method',
                                'shippingmethods.ship_desc'
                            )
                            ->join('shippingmethods', 'localpickups.shipping_method_id', '=', 'shippingmethods.id')
                            ->where('zone_id', $zone->id);
    
                    // Build query for table rate methods.
                    $table = Tablerates::select(
                                'tablerates.id',
                                'tablerates.title',
                                'tablerates.status',
                                'shippingmethods.ship_method',
                                'shippingmethods.ship_desc'
                            )
                            ->join('shippingmethods', 'tablerates.shipping_method_id', '=', 'shippingmethods.id')
                            ->where('zone_id', $zone->id);
    
                    // Build query for rate by distance methods.
                    $distance = RatesByDistance::select(
                                'rates_by_distances.id',
                                'rates_by_distances.title',
                                'rates_by_distances.status',
                                'shippingmethods.ship_method',
                                'shippingmethods.ship_desc'
                            )
                            ->join('shippingmethods', 'rates_by_distances.shipping_method_id', '=', 'shippingmethods.id')
                            ->where('zone_id', $zone->id);
    
                    // Combine all method queries using unionAll
                    $methods = $free->unionAll($flat)
                                   ->unionAll($local)
                                   ->unionAll($table)
                                   ->unionAll($distance)
                                   ->get();
    
                    // Attach the merged methods to the zone
                    $zone->methods = $methods;
                }
    
                $result = [
                    'status'   => 1,
                    'shopZone' => $zones,
                ];
            } else {
                $result = [
                    'status'  => 0,
                    'message' => 'Shop Not Found',
                ];
            }
            return response()->json($result);
    }
    }

    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {
            $country = [];
            $state = [];
            $shipmethod = Shippingmethod::where('ship_method', $request->method)->first();
            $status = ($request->status) ? 1 : 0;
            if ($request->zoneId) {
                $ship_zone = Shippingzone::where('id', $request->zoneId)->first();
            } else {
                $ship_zone = new Shippingzone();
            }

            foreach ($request->data as $code) {
                $data = explode(":", $code);
                if (count($data) >= 2) {
                    array_push($state, $code);
                } else {
                    array_push($country, $code);
                }

                // array_push($country, $data[0]);


            }

            $ship_zone->user_id = $shop->id;
            $ship_zone->ship_zone = $request->shipzone;
            $ship_zone->status = $request->status;
            if (empty($country) && empty($state) && !empty($request->region)) {
                foreach ($request->region as $regionItem) {
                    $code = is_array($regionItem) ? ($regionItem['value'] ?? '') : $regionItem;
                    if (empty($code)) {
                        continue;
                    }
                    $parts = explode(':', $code);
                    if (count($parts) >= 2) {
                        $state[] = $code;
                    } else {
                        $country[] = $code;
                    }
                }
            }

            $ship_zone->zone_region = json_encode($request->region);
            $ship_zone->country = implode(",", $country);
            $ship_zone->state = implode(",", $state);
            $ship_zone->zip = $request->postcode;
            $ship_zone->save();

            $result = [
                'status' => 1,
                'msg' => 'Shipping Method Saved Successfully',
                'zond_id' => $ship_zone->id
            ];
        } else {
            $result = [
                'status' => 0,
                'msg' => 'Shop Not Found',
            ];
        }

        return json_encode($result);
    }

    public function show($id, Request $request)
    {
        $input = $request->all();
        $result = [];
        if (isset($input['shop']) && !empty($input['shop'])) {
            $shop =  User::where('name', $input['shop'])->first();
            if ($shop) {
                $zoneCount = Shippingzone::count();
                if (!empty($id)) {
                    $zone_exist =  Shippingzone::with('zone_method')->where('id', $id)->where('user_id', $shop->id)->first();
                    if ($zone_exist->zone_region) {
                        $zone_exist->zone_region = json_decode($zone_exist->zone_region, true);
                    }

                    $free = Freeshipping::select('freeshippings.id', 'freeshippings.title', 'freeshippings.status', 'shippingmethods.ship_method', 'shippingmethods.ship_desc')->join('shippingmethods', 'freeshippings.shipping_method_id', '=', 'shippingmethods.id')->where('zone_id', $id);
                    $flat = Flaterate::select('flaterates.id', 'flaterates.title', 'flaterates.status', 'shippingmethods.ship_method', 'shippingmethods.ship_desc')->join('shippingmethods', 'flaterates.shipping_method_id', '=', 'shippingmethods.id')->where('zone_id', $id);
                    $local = Localpickup::select('localpickups.id', 'localpickups.title', 'localpickups.status', 'shippingmethods.ship_method', 'shippingmethods.ship_desc')->join('shippingmethods', 'localpickups.shipping_method_id', '=', 'shippingmethods.id')->where('zone_id', $id);
                    $table = Tablerates::select('tablerates.id', 'tablerates.title', 'tablerates.status', 'shippingmethods.ship_method', 'shippingmethods.ship_desc')->join('shippingmethods', 'tablerates.shipping_method_id', '=', 'shippingmethods.id')->where('zone_id', $id);
                    $distance = RatesByDistance::select('rates_by_distances.id', 'rates_by_distances.title', 'rates_by_distances.status', 'shippingmethods.ship_method', 'shippingmethods.ship_desc')->join('shippingmethods', 'rates_by_distances.shipping_method_id', '=', 'shippingmethods.id')->where('zone_id', $id);
                    $allMethod = $free->unionAll($flat)->unionAll($local)->unionAll($table)->unionAll($distance)->get();


                    if (!empty($zone_exist)) {
                        $result = [
                            'status' => 1,
                            'zone_exist' => $zone_exist,
                            'zone_mthd' => $allMethod,
                            'zoneCount' => $zoneCount
                        ];
                    }
                } else {
                    $result = [
                        'status' => 1,
                        'zone_exist' => "",
                        'zone_mthd' => "",
                        'zoneCount' => $zoneCount
                    ];
                }
            } else {
                $result = [
                    'status' => 0,
                    'msg' => "Shop Not Found"
                ];
            }
            return response()->json($result);
        }
    }

    public function destroy($id, Request $request)
    {
        $input = $request->all();
        $shop =  User::where('name', $input['shop'])->first();
        if ($shop) {
            $shipZone = Shippingzone::where('id', $id)->where('user_id', $shop->id)->first();
            $tableRate = Tablerates::where('zone_id', $id)->first();
            if (Tablerates::where('zone_id', $id)->count() > 0) {
                if (Tablerateoption::where('table_rate_id', $tableRate->id)->count() > 0) {
                    Tablerateoption::where('table_rate_id', $tableRate->id)->delete();
                }
                Tablerates::where('zone_id', $id)->delete();
            }
            // $tableRateOpt = Tablerateoption::where('table_rate_id', $tableRate->id)->get();

            Zonemethod::where('zone_id', $shipZone->id)->delete();
            Flaterate::where('zone_id', $shipZone->id)->delete();
            Localpickup::where('zone_id', $shipZone->id)->delete();
            Freeshipping::where('zone_id', $shipZone->id)->delete();

            $shipZone->delete();
            echo json_encode(array("status" => 1, 'msg' => 'Shipping Zone Delete Successfully'));
        } else {
            echo json_encode(array("status" => 0, 'msg' => 'Shop Not Found'));
        }
    }
    public function updateShipZoneStatus($id, Request $request)
    {
        $input = $request->all();
        $shop =  User::where('name', $input['shop'])->first();
        if ($shop) {
            $status = ($request->status == "true") ? 1 : 0;

            $shippingZone = Shippingzone::find($id);
            if ($shippingZone) {
                $shippingZone->status = $status;
                $shippingZone->save();
                return json_encode(array("status" => 1, 'msg' => 'Shipping Zone Status Update Successfully'));
            }
        } else {
            echo json_encode(array("status" => 0, 'msg' => 'Shop Not Found'));
        }
    }
    public function updateShipMethodStatus($id, Request $request)
    {
        $input = $request->all();
        $shop =  User::where('name', $input['shop'])->first();

        if ($shop) {
            $status = ($request->status == "true") ? 1 : 0;
            if ($input['mthName'] == "free_shipping") {
                $freeShipping = Freeshipping::find($id);
                if ($freeShipping) {
                    $freeShipping->status = $status;
                    $freeShipping->save();
                    return json_encode(array("status" => 1, 'msg' => 'Shipping Method Status Update Successfully'));
                }
            } else if ($input['mthName'] == "flat_rate") {
                $flateRate = Flaterate::find($id);
                if ($flateRate) {
                    $flateRate->status = $status;
                    $flateRate->save();
                    return json_encode(array("status" => 1, 'msg' => 'Shipping Method Status Update Successfully'));
                }
            } else if ($input['mthName'] == "local_pickup") {
                $localPickup = Localpickup::find($id);
                if ($localPickup) {
                    $localPickup->status = $status;
                    $localPickup->save();
                    return json_encode(array("status" => 1, 'msg' => 'Shipping Method Status Update Successfully'));
                }
            } else if ($input['mthName'] == "rates_by_distance") {
                $distance = RatesByDistance::find($id);
                if ($distance) {
                    $distance->status = $status;
                    $distance->save();
                    return json_encode(array("status" => 1, 'msg' => 'Shipping Method Status Update Successfully'));
                }
            } else {
                $tableRate = Tablerates::find($id);
                if ($tableRate) {
                    $tableRate->status = $status;
                    $tableRate->save();
                    return json_encode(array("status" => 1, 'msg' => 'Shipping Method Status Update Successfully'));
                }
            };
        } else {
            echo json_encode(array("status" => 0, 'msg' => 'Shop Not Found'));
        }
    }

    public function carrierServices($url, $data, $access_token)
    {
        $curl = curl_init();

        // Set the URL
        curl_setopt($curl, CURLOPT_URL, $url);
        // Set the request method to POST
        curl_setopt($curl, CURLOPT_POST, true);
        // Set the POST data
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        // Set the headers
        $headers = array(
            "Content-Type: application/json",
            "X-Shopify-Access-Token: {$access_token}"
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        // Set option to return the response as a string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Execute the request and get the response
        $response = curl_exec($curl);

        // Check for errors
        if ($response === false) {
            $error = curl_error($curl);
            // Handle the error appropriately
            // ...
        }

        // Close cURL resource
        curl_close($curl);

        return $response;
    }
}
