<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
         'shop_id', 
         'carrier_name',
         'account_id',
         'api_key',
         'status'
        ];

}
