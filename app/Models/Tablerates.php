<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tablerates extends Model
{
    use HasFactory;

    public function table_rate_options() {
        return $this->hasMany(Tablerateoption::class,'table_rate_id');
    }
}
