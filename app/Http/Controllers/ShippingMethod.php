<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Localpickup;
use App\Models\Zonemethod;
use App\Models\Flaterate;
use App\Models\ShippingOption;

class ShippingMethod extends Controller
{
    public function index(Request $request)
    {
        $result = [];
        $shop =  User::where('name', $request->shop)->first();
        if($shop){
            $shippingOption =  ShippingOption::where('user_id', $shop->id)->first();
            if($shippingOption){
                $result = [
                    'status' => 1,
                    'ShippingOption' => $shippingOption
                ];
            }
        } else {
            $result = [
                'status' => 0,
                'msg' => "Shop Not Found"
            ];
        }
        return json_encode($result);
    }

    public function store(Request $request)
    {
     
        $shop = User::where('name', $request->shop)->first();

        if ($shop) 
        {
            $shippingOption = [];
            if($request->optionId){
                $shipOption = ShippingOption::where('id', $request->optionId)->first();
            } else {
                $shipOption = new ShippingOption();
            }
            $optionEnable = ($request->enable_shipping == true) ? 1 : 0;
            $optionDisable = ($request->hide_shipping == true) ? 1 : 0; 
            $optionDest = $request->ship_destination;
            $optionEnableDebut = ($request->enable_debug == true) ? 1 : 0; 

          
                $shipOption->user_id = $shop->id;
                $shipOption->enable_shipping = $optionEnable;
                $shipOption->hide_shipping =$optionDisable;
                $shipOption->ship_destination =$optionDest;	
                $shipOption->enable_debug =$optionEnableDebut;
         

            $shipOption->save();
            
            $result = [
                'status' => 1,
                'msg' => 'Shipping Option Saved Successfully',
                'option_id' => $shipOption->id
            ];
        } else {
            $result = [
                'status' => 1,
                'msg' => 'Shop Not Found'
            ];
        }
        return json_encode($result);

    }
}
