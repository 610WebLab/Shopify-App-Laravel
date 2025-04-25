<?php
namespace App\Traits;
use App\Models\Shippingzone;
// use App\Models\Flaterate;

trait FetchShippingZoneTrate {
    public function getShippingZones($country, $state, $postCode, $shopId)
    {
        if($country || $state || $postCode)
        {
            $state = $country.":".$state;
            $shopId = (int) $shopId;
            
            $zone = Shippingzone::whereRaw(
                '(FIND_IN_SET(?, country) OR FIND_IN_SET(?, state)) AND user_id = ? AND status = 1',
                [$country, $state, $shopId]
            )->first();

            if(empty($zone)){
                $zone = Shippingzone::where('country','')->where('state','')->where('user_id', $shopId)->where('status', 1)->first();
                
            }
            //->orderBy('preority', 'ASC')

            return json_encode($zone);
        }

    }
}