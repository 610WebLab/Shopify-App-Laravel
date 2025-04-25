<?php
namespace App\Traits;

use App\Models\Shippingzone;
use App\Models\Freeshipping;
use App\Traits\FetchShippingZoneTrate;

trait FreeShippingTrait
{
   use FetchShippingZoneTrate;

    public function minimumOrderAmount($price, $country_code, $province_code, $post_code, $shopId)
    {
        // if ($country_code && $province_code || $post_code, $shopId) 
        // {

            // $state = $country_code.":".$province_code;
            
            // $zone =  Shippingzone::whereRaw('FIND_IN_SET(?, country)', [$country_code])->orWhereRaw('FIND_IN_SET(?, state)', [$state])->where('status', 1)->first();

            // if(empty($zone)){
            //     $zone = Shippingzone::where('country','')->where('state','')->where('status', 1)->first();
                
            // }
            $zone = json_decode($this->getShippingZones($country_code, $province_code, $post_code, $shopId));
            if(!empty($zone)) {

                return $this->calCulateFreeShipping($price, $zone->id, $country_code, $province_code);
                
            }else{

                return response()->json([]);

            }

        // }
    }

    public function calCulateFreeShipping($price, $zoneID, $country_code, $province_code)
    {
        $result = [];
        $ship = '0';
        $freeShipping = Freeshipping::where('zone_id', $zoneID)->where('status', 1)->get();

        if ($freeShipping) 
        {
            foreach ($freeShipping as $free) 
            {
               
                if ($free->ship_require == 'min_amount') 
                {
                    if ((int)$price/100 >= $free->order_amount) 
                    {
                        array_push($result, array('status' => 1, 'service_name' => $free->title, 'description'=>'', 'shipPrice' => $ship));
                    }
                } else {
                    array_push($result, array('status' => 1, 'service_name' => $free->title, 'description'=>'', 'shipPrice' => $ship));
                }
                   
            }
            return json_encode($result);
        }
    }
}
