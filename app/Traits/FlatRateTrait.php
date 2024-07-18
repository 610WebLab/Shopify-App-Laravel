<?php
namespace App\Traits;
use App\Models\Shippingzone;
use App\Models\Flaterate;
use App\Traits\FetchShippingZoneTrate;

trait FlatRateTrait
{
    use FetchShippingZoneTrate;
    public function flatRateShipping($country_code, $province_code, $post_code)
    {
        // if ($country_code && $province_code || $post_code) 
        // {

            // $state = $country_code.":".$province_code;
            
            // $zone =  Shippingzone::whereRaw('FIND_IN_SET(?, country)', [$country_code])->orWhereRaw('FIND_IN_SET(?, state)', [$state])->where('status', 1)->first();

            // if(empty($zone)){
            //     $zone = Shippingzone::where('country','')->where('state','')->where('status', 1)->first();
                
            // }
            $zone = json_decode($this->getShippingZones($country_code, $province_code, $post_code));
           

            if(!empty($zone)) {

                return $this->calCulateFlatRateShipping($zone->id);
                
            }else{

                return response()->json([]);

            }

        // }
    }

    public function calCulateFlatRateShipping($zoneID)
    {
        $result = [];

        $flatRates = Flaterate::where('zone_id', $zoneID)->where('status', 1)->get();

        if($flatRates)
        {
            foreach($flatRates as $flatrate)
            {
                if($flatrate->tax_status  == 'taxable')
                {
                    array_push($result, array('status' => 1, 'service_name' => $flatrate->title, 'description'=>'','shipPrice' => $flatrate->flat_rate_cost));
                }
                else
                {
                    array_push($result, array('status' => 1, 'service_name' => $flatrate->title, 'description' =>'','shipPrice' => $flatrate->flat_rate_cost));
                }
                
            }
            return json_encode($result);
        }
    }
}