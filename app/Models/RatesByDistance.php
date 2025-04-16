<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatesByDistance extends Model
{
    use HasFactory;

    protected $table = 'rates_by_distances';

    protected $fillable = [
        'user_id',
        'zone_id',
        'shipping_method_id',
        'title',
        'description',
        'location_name',
        'country_region',
        'city',
        'street',
        'postal_code',
        'set_latitude_longitude',
        'rates',
        'rate_price_limit',
        'max_delivery_rate',
        'latitude',
        'longitude',
        'min_order_price',
        'max_order_price',
        'min_order_weight',
        'max_order_weight',
        'min_distance',
        'max_distance',
        'base_delivery_price',
        'price_per_kilometer',
        'status',
        'weight_unit'
    ];
}
