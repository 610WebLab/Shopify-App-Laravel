<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shopify_location_id',
        'shopify_location_gid',
        'name',
        'address1',
        'address2',
        'city',
        'province',
        'province_code',
        'country',
        'country_code',
        'zip',
        'phone',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingZones()
    {
        return $this->hasMany(Shippingzone::class, 'location_id');
    }

    public function getDropdownLabelAttribute(): string
    {
        $parts = array_filter([
            $this->name,
            $this->address1,
            $this->city,
            $this->province_code ?: $this->province,
            $this->zip,
        ]);

        return implode(' — ', $parts) ?: ('Location #' . $this->id);
    }
}
