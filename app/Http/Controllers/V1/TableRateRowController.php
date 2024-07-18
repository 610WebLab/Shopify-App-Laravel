<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tablerateoption;
use App\Models\Shippingmethod;

class TableRateRowController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => Tablerateoption::where('table_rate_id', $request->table_rate_id)->get()
        ]);
    }

    public function store(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {

            $saveData = [
                'table_rate_id' => $request->TableRateID,
                'ship_class' => 0,
                'condition' => "none",
                'ship_min' => 0,
                'ship_max' => 0,
                'ship_break' => 0,
                'ship_abort' => 0,
                'row_cost' => 0,
                'item_cost' => 0,
                'lbs_cost' => 0,
                'percent_cost' => 0,
                'lable' => ""
            ];

            $saveData['id'] =  Tablerateoption::insertGetId($saveData);

            $result = [
                'status' => 1,
                'data' => $saveData,
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
                'ship_class' => $request->ship_class,
                'condition' => $request->condition,
                'ship_min' => $request->ship_min,
                'ship_max' => $request->ship_max,
                'ship_break' => $request->ship_break,
                'ship_abort' => $request->ship_abort,
                'row_cost' => $request->row_cost,
                'item_cost' => $request->item_cost,
                'lbs_cost' => $request->lbs_cost,
                'percent_cost' => $request->percent_cost,
                'lable' => $request->lable
            ];

            Tablerateoption::where('id', $id)->update($saveData);

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


    public function destroy(Request $request, $id)
    {

        Tablerateoption::whereIn('id', $request->ids)->delete();

        $result = [
            'status' => 1,
            'msg' => 'Shipping Method Successfully deleted',
        ];
        return json_encode($result);
    }
}
