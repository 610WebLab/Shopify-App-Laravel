<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Localpickup;
use App\Models\Zonemethod;
use App\Models\Shippingmethod;
use App\Models\Flaterate;
use App\Models\Freeshipping;


class LocalPickupMethodController extends Controller
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
                'status' => $request->status,
            ];

            Localpickup::insert($saveData);

            $result = [
                'status' => 1,
                'msg' => 'Local Pickup Rate Save Successfully',
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
                'tax_status' => $request->taxstatus,
                'pickup_cost' => $request->cost,
            ];
            Localpickup::where('id', $id)->update($saveData);

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


    public function show($id)
    {
        if (!empty($id)) {
            $result = [
                'status' => 1,
                'localPickUp' => Localpickup::where('id', $id)->first(),

            ];
            return json_encode($result);
        }
    }

    public function destroy(Request $request, $id)
    {

        Localpickup::where('id', $id)->delete();
        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
