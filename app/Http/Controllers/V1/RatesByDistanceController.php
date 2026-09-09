<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shippingmethod;
use App\Models\RatesByDistance;

class RatesByDistanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {

            $method =  Shippingmethod::where('ship_method', $request->shippingMethod)->first();

            $saveData = [
                'user_id' => $shop->id,
                'zone_id' => $request->zoneId,
                'shipping_method_id' => $method->id,
                'title' =>  ucwords(str_replace("_", " ", $method->ship_method)),
                'description' => "",
                'status' => $request->status
            ];

            RatesByDistance::insert($saveData);

            $result = [
                'status' => 1,
                'msg' => 'Shipping Method Saved Successfully',
            ];
        } else {
            $result = [
                'status' => 0,
                'msg' => 'Shop Not Found',
            ];
        }
        return json_encode($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!empty($id)) {
            $result = [
                'status' => 1,
                'distanceRates' => RatesByDistance::where('id', $id)->first(),
            ];
            return json_encode($result);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {
            $ratePriceLimit = $request->rate_price_limit === 'yes' ? 'yes' : 'no';
            $maxDeliveryRate = $ratePriceLimit === 'yes' ? $request->max_delivery_rate : null;

            $saveData = [
                'title' => $request->rate_name,
                'description' => $request->description,
                'rates' => $request->rates,
                'rate_price_limit' => $ratePriceLimit,
                'max_delivery_rate' => $maxDeliveryRate,
                'min_order_price' => $request->min_order_price,
                'max_order_price' => $request->max_order_price,
                'min_order_weight' => $request->min_order_weight,
                'max_order_weight' => $request->max_order_weight,
                'min_distance' => $request->min_distance,
                'max_distance' => $request->max_distance,
                'base_delivery_price' => $request->base_delivery_price,
                'price_per_kilometer' => $request->price_per_kilometer,
                'weight_unit' => $request->selectedWeight
            ];
            RatesByDistance::where('id', $id)->update($saveData);
            $result = ['status' => 1, 'msg' => 'Shipping Method Successfully Updated'];
        } else {
            $result = ['status' => 0, 'msg' => 'Shop Not Found'];
        }
        return response()->json($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        RatesByDistance::whereIn('id', $id)->delete();

        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
