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

class ShippingZones extends Controller
{
    use FreeShippingTrait, LocalPickupTrait, FlatRateTrait, TableRateTrait;

    public function apiResonse(Request $request)
    {
        Log::info('Carrier services inPut', ['data' => $request->all()]);
        $rate = [];
        $price = 0;
        $weight = 0;
        $quantity = 0;
        $lineItem = 0;
        $checkOutData = $request->all();
        // Log::info(print_r($checkOutData, true)); 
        $country = $checkOutData['rate']['destination']['country'];
        $state = $checkOutData['rate']['destination']['province'];
        $postCode = $checkOutData['rate']['destination']['postal_code'];
        $lineItem = count($checkOutData['rate']['items']);
        foreach ($checkOutData['rate']['items'] as $checkout) {
            $price = $price + ($checkout['price'] * $checkout['quantity']);
            $weight = $weight + ($checkout['grams'] * $checkout['quantity']);
            $quantity = $quantity + $checkout['quantity'];
        }

        $flateRate = json_decode($this->flatRateShipping($country, $state, $postCode), true);
        $localPickup = json_decode($this->localPickUpShipping($country, $state, $postCode), true);
        $freeShips = json_decode($this->minimumOrderAmount($price, $country, $state, $postCode), true);
        $tableRate = json_decode($this->tableRateShipping($country, $state, $postCode, $price, $weight, $quantity, $lineItem), true);
        Log::info('Carrier services lineItem', ['data' => $lineItem]);
        Log::info(print_r($flateRate, true));
        Log::info(print_r($localPickup, true));
        Log::info(print_r($freeShips, true));
        Log::info(print_r($tableRate, true));
        $shipData = [];
        if ($flateRate) {
            foreach ($flateRate as $flat) {
                if ($flat['status'] == 1) {
                    array_push($shipData, $this->createShippingRates($flat['service_name'], $flat['shipPrice'], $flat['description'], $checkOutData['rate']['currency']));
                }
            }
        }


        if ($localPickup) {
            foreach ($localPickup as $local) {
                if ($local['status'] == 1) {
                    array_push($shipData, $this->createShippingRates($local['service_name'], $local['shipPrice'], $local['description'], $checkOutData['rate']['currency']));
                }
            }
        }

        if ($freeShips) {
            foreach ($freeShips as $free) {
                if ($free['status'] == 1) {
                    array_push($shipData, $this->createShippingRates($free['service_name'], $free['shipPrice'], $free['description'], $checkOutData['rate']['currency']));
                }
            }
        }

        if ($tableRate) {
            foreach ($tableRate as $table) {
                foreach ($table as $tblrate) {
                    if ($tblrate['status'] == 1) {
                        array_push($shipData, $this->createShippingRates($tblrate['service_name'], $tblrate['shipPrice'], $tblrate['description'], $checkOutData['rate']['currency']));
                    }
                }
            }
        }

        $rate['rates'] = $shipData;
        $json = json_encode($rate);
        return response()->json(json_decode($json));
    }

    public function createShippingRates($service_name, $price, $description, $currency)
    {
        $data = [
            "service_name" => $service_name,
            "service_code" => "shipping_rates_" . rand(),
            "total_price" => $price * 100,
            "description" => $description,
            "currency" => $currency,
            "min_delivery_date" => "2013-04-12 14:48:45 -0400",
            "max_delivery_date" => "2013-04-12 14:48:45 -0400"
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
        $url = "http://" . $shop->name . "/admin/api/" . env('SHOPIFY_API_VERSION') . "/checkouts/" . $request->token . ".json";
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
                    $url = "https://" . $shop->name . "/admin/api/" . env('SHOPIFY_API_VERSION') . "/carrier_services.json";

                        $services['carrier_service']= [
                            "name" => "Shipping Rates Provider",
                            "callback_url" => "https://shipping.webziainfotech.com/api/v1/carrier_service",
                            "service_discovery " => true
                        ];

                        $carrierserv = json_encode($services);
                        $result = $this->carrierServices($url, $carrierserv, $shop->password);
                        $resError = json_decode($result, true);
                        if(!empty($resError['errors']) && isset($resError['errors'])){
                            return json_encode(['status' => 0, 'msg' => $resError['errors']['base'][0]]);
                        } else {
                        dd($resError);
                        }

                        // $headers = array();
                        // $headers['Content-Type'] = 'application/json';
                        // $headers['X-Shopify-Access-Token'] = $shop->password;
                        // $carrierService = Http::withHeaders($headers)->post($url, $services);
                        // dd($carrierService->body());

                        // $resp = $shop->api()->rest(
                        //     'POST',
                        //     '/admin/api/' . env('SHOPIFY_API_VERSION') . 'carrier_services.json',
                        //     array('carrier_service' => $data)
                        // )['body'];
                        // dd(json_decode($resp, true));
                    // $url = "https://" . $shop->name . "/admin/api/" . env('SHOPIFY_API_VERSION') . "/carrier_services.json";
                    // // dd($url);
                    // $services['carrier_service'] = [
                    //     "name" => "Shipping Rates Provider",
                    //     "callback_url" => "https://shipping.webziainfotech.com/api/v1/carrier_service",
                    //     "service_discovery " => true
                    // ];

                    // $carrierserv = json_encode($services);
                    // $result = $this->carrierServices($url, $carrierserv, $shop->password);
                    // $response = json_decode($result, true);
                    dd($response);
                    if ($response["carrier_service"]) {
                        $shop->status = "true";
                        $shop->save();
                    }
                }
                $shop_zone =  Shippingzone::with('local_pickup_method')->with('free_shipping_method')->with('flat_rate_method')->with('table_rate_method')->where('user_id', $shop->id)->get();
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

    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        // if(Shippingzone::where('user_id', $shop->id)->count() == 0){
        //     $url = "https://" . $shop->name . "/admin/api/". env('SHOPIFY_API_VERSION') . "/carrier_services.json";

        //     $services['carrier_service']= [
        //         "name" => "Shipping Rates Provider",
        //         "callback_url" => "https://shipping.webziainfotech.com/api/v1/carrier_service",
        //         "service_discovery " => true
        //     ];

        //     $carrierserv = json_encode($services);
        //     $result = $this->carrierServices($url, $carrierserv, $shop->password);
        //     $resError = json_decode($result, true);
        //     if(!empty($resError['errors']) && isset($resError['errors'])){
        //         return json_encode(['status' => 0, 'msg' => $resError['errors']['base'][0]]);
        //     }

        //     // $headers = array();
        //     // $headers['Content-Type'] = 'application/json';
        //     // $headers['X-Shopify-Access-Token'] = $shop->password;
        //     // $carrierService = Http::withHeaders($headers)->post($url, $services);
        //     // dd($carrierService->body());

        //     // $resp = $shop->api()->rest(
        //     //     'POST',
        //     //     '/admin/api/' . env('SHOPIFY_API_VERSION') . 'carrier_services.json',
        //     //     array('carrier_service' => $data)
        //     // )['body'];
        //     // dd(json_decode($resp, true));
        // }

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
            $ship_zone->zone_region = json_encode($request->region); //implode(",", $request->region);
            // $ship_zone->preority = $request->preority['value'];
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
                    $allMethod = $free->unionAll($flat)->unionAll($local)->unionAll($table)->get();


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
