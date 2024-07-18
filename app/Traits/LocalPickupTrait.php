<?php
namespace App\Traits;
use App\Models\Localpickup;
use App\Traits\FetchShippingZoneTrate;
trait LocalPickupTrait
{
    use FetchShippingZoneTrate;

    public function localPickUpShipping($country_code, $province_code, $post_code)
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

                return $this->calCulateLocalPickUp($zone->id);
                
            }else{

                return response()->json([]);

            }

        // }
    }

    public function calCulateLocalPickUp($zoneID)
    {
        $result = [];
        $localPick = Localpickup::where('zone_id', $zoneID)->where('status', 1)->get();

        if ($localPick) 
        {
            foreach ($localPick as $local) 
            {
                if($local->tax_status  == 'taxable')
                {
                    array_push($result, array('status' => 1, 'service_name' => $local->title, 'description'=>'',  'shipPrice' => $local->pickup_cost) );
                }
                else
                {
                    array_push($result, array('status' => 1, 'service_name' => $local->title, 'description'=>'',  'shipPrice' => $local->pickup_cost) );
                }
               
            }
            return json_encode($result);
        }
    }
}
