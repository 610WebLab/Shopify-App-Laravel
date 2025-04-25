<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    use HasFactory;

    protected $fillable =[
       'user_id',
        'service_id',
       'name',
       'account_id',
       'status'
    ];
    public function other_carrier()
    {
        return $this->belongsTo(OtherCarrierService::class, 'service_id', 'id');
    }
}
