<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tablerateoption;
use App\Models\Tablerates;
use App\Models\Shippingmethod;
use App\Models\Freeshipping;

class FreeShipMethodController extends Controller
{
    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {

            $method =  Shippingmethod::where('ship_method', $request->shippingMethod)->first();

            $saveData = [
                'user_id' => $shop->id,
                'zone_id' => $request->zoneId,
                'shipping_method_id' => $method->id,
                'title' => ucwords(str_replace("_", " ", $method->ship_method)),
                'ship_require' => "none",
                'order_amount' => 0,
                'discount' => 0,
                'status' => $request->status,
            ];

            Freeshipping::insert($saveData);

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

    public function update($id, Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {
            $saveData = [
                'title' => $request->title,
                'ship_require' => $request->ship_requir,
                'order_amount' => $request->amount,
                'discount' => $request->discount,
            ];
            Freeshipping::where('id', $id)->update($saveData);

            $result = [
                'status' => 1,
                'msg' => 'Shipping Method Successfully Updated',
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
        if (!empty($id)) {
            $result = [
                'status' => 1,
                'freeShipping' => Freeshipping::where('id', $id)->first(),

            ];
            return json_encode($result);
        }
    }

    public function destroy(Request $request, $id)
    {

        Freeshipping::where('id', $id)->delete();
        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
