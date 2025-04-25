<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'dimension_unit',
        'length',
        'width',
        'height',
        'weight',
        'weight_unit',
        'sorting'
    ];
}
