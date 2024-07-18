<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zonemethod extends Model
{
    use HasFactory;

    public function shipping_method()
    {
        return $this->belongsTo(Shippingmethod::class,'method_id', 'id');
    }

    public function local_pickup() 
    {
        return $this->hasOne(Localpickup::class, 'zone_method_id');
    }
    public function flat_rate() 
    {
        return $this->hasOne(Flaterate::class, 'zone_method_id');
    }
    public function free_shipping() 
    {
        return $this->hasOne(Freeshipping::class, 'zone_method_id');
    }
}
