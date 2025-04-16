<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Flaterate;
use App\Models\Shippingmethod;

class FlatRateMethodController extends Controller
{
    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {

            $method =  Shippingmethod::where('ship_method', $request->shippingMethod)->first();

            $saveData = [
                'user_id' => $shop->id,
                'zone_id' => $request->zoneId,
                'shipping_method_id' => @$method->id,
                'title' => ucwords(str_replace("_", " ", $method->ship_method)),
                'tax_status' => "taxable",
                'flat_rate_cost' =>    0,
                'ship_class_cost' => 0,
                'no_ship_class_cost' =>    0,
                'calculation_type' => "order",
                'status' => $request->status,
            ];

            Flaterate::insert($saveData);

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

    public function update(Request $request, $id)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {

            $saveData = [
                'title' => $request->title,
                'tax_status' => $request->taxstatus,
                'flat_rate_cost' => $request->cost,
                'ship_class_cost' => $request->ship,
                'no_ship_class_cost' => $request->noship,
                'calculation_type' => $request->calculation,
            ];
            Flaterate::where('id', $id)->update($saveData);

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
                'flatRate' => Flaterate::where('id', $id)->first(),

            ];
            return json_encode($result);
        }
    }


    public function destroy(Request $request, $id)
    {

        Flaterate::where('id', $id)->delete();
        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
