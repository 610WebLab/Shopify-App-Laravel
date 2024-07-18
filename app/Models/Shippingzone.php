<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shippingzone extends Model
{
    use HasFactory;

    public function zone_method()
    {
        return $this->hasMany(Zonemethod::class,'zone_id');
    }
    public function local_pickup_method()
    {
        return $this->hasMany(Localpickup::class,'zone_id');
    }
    public function free_shipping_method()
    {
        return $this->hasMany(Freeshipping::class,'zone_id');
    }
    public function flat_rate_method()
    {
        return $this->hasMany(Flaterate::class,'zone_id');
    }
    public function table_rate_method()
    {
        return $this->hasMany(Tablerates::class,'zone_id');
    }
    public function table_rate_option_method()
    {
        return $this->hasMany(Tablerateoption::class,'zone_id');
    }
}
