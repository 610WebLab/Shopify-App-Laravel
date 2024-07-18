<?php
namespace App\Traits;
use App\Models\Shippingzone;
// use App\Models\Flaterate;

trait FetchShippingZoneTrate {
    public function getShippingZones($country, $state, $postCode)
    {
        if($country || $state || $postCode)
        {
            $state = $country.":".$state;
            
            $zone =  Shippingzone::whereRaw('FIND_IN_SET(?, country)', [$country])->orWhereRaw('FIND_IN_SET(?, state)', [$state])->where('status', 1)->first();

            if(empty($zone)){
                $zone = Shippingzone::where('country','')->where('state','')->where('status', 1)->first();
                
            }
            //->orderBy('preority', 'ASC')

            return json_encode($zone);
        }

    }
}