<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tablerates;
use App\Models\Tablerateoption;
use App\Models\Shippingmethod;

class TableRateMethodController extends Controller
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
                'title' =>  ucwords(str_replace("_", " ", $method->ship_method)),
                'description' => "",
                'tax_status' => "taxable",
                'taxship_include' => "yes",
                'handling_fee' => 0,
                'maxship_cost' => 0,
                'calculation_type' => "per_order",
                'handlingfee_peritem' => 0,
                'mincost_peritem' => 0,
                'maxcost_peritem' => 0,
                'dicount_minmax' => 0,
                'tax_minmax' => 0,
                'status' => $request->status,
            ];

            Tablerates::insert($saveData);

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
                'title' =>  $request->title,
                'description' => $request->description,
                'tax_status' => $request->tax_status,
                'taxship_include' => $request->taxship_include,
                'handling_fee' => $request->handling_fee,
                'maxship_cost' => $request->maxship_cost,
                'calculation_type' => $request->calculation_type,
                'handlingfee_peritem' => $request->handlingfee_peritem,
                'mincost_peritem' => $request->mincost_peritem,
                'maxcost_peritem' => $request->maxcost_peritem,
                'dicount_minmax' => $request->dicount_minmax,
                'tax_minmax' => $request->tax_minmax,
            ];

            Tablerates::where('id', $id)->update($saveData);

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
                'tableRate' => Tablerates::where('id', $id)->first(),
                'tableRateOption' => Tablerateoption::where('table_rate_id', $id)->get()
            ];
            return json_encode($result);
        }
    }


    public function destroy(Request $request, $id)
    {

        Tablerates::where('id', $id)->delete();
        Tablerateoption::where('table_rate_id', $id)->delete();

        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
