<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LabelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'logo_path',
        'phone',
        'email',
        'website',
        'address_source',
        'location_id',
        'location_name',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'default_template_id',
        'include_barcode',
    ];

    protected $casts = [
        'include_barcode' => 'boolean',
        'default_template_id' => 'integer',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function hasUsableAddress(): bool
    {
        return !empty($this->company_name) || !empty($this->address1);
    }

    /**
     * Resolve ship-from address payload for label templates.
     *
     * @param  object  $user
     * @return array{name:string,street1:string,street2:string,city:string,state:string,zip:string,country:string,phone:string,email:string,website:string,logo_url:?string,include_barcode:bool,default_template_id:?int}|null
     */
    public static function resolveFromAddress(object $user): ?array
    {
        $settings = static::where('user_id', $user->id)->first();

        if (!$settings || !$settings->hasUsableAddress()) {
            return null;
        }

        $street1 = trim(($settings->address1 ?? '') . (empty($settings->address2) ? '' : ', ' . $settings->address2));

        return [
            'name' => $settings->company_name ?? '',
            'street1' => $street1,
            'street2' => $settings->address2 ?? '',
            'city' => $settings->city ?? '',
            'state' => $settings->state ?? '',
            'zip' => $settings->zip ?? '',
            'country' => $settings->country ?? '',
            'phone' => $settings->phone ?? '',
            'email' => $settings->email ?? '',
            'website' => $settings->website ?? '',
            'logo_url' => $settings->logo_url,
            'include_barcode' => (bool) $settings->include_barcode,
            'default_template_id' => $settings->default_template_id,
        ];
    }
}
