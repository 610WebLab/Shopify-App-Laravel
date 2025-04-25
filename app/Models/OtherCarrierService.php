<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherCarrierService extends Model
{
    use HasFactory;
    protected $fillable = [
        'carrier_type',
        'user_id',
        'api_key',
        "secret_key",
        "token",
        'status',
    ];

    public function carrier()
    {
        return $this->hasMany(Carrier::class, 'service_id', 'id');
    }
}
