<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'order_id',
        'order_name',
        'order_no',
        'labels',
        'date',
        'customer_id',
        'customer_name',
        'customer_email',
        'total',
        'payment_status',
        'fullfilement',
        'item_count',
        'delivery_status',
        'delivery_method',
        'tags',
        'shipping_code',
        'shipping_service',
        'carrier_id',
        'template_id',
        'label_url',
        'group_title',
        'carrier_label',
        'label_count',
        'dimension_id'
    ];
}
