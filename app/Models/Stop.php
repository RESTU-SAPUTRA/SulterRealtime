<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    use HasFactory;

    protected $table = 'stops';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'order_index',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'order_index' => 'integer',
    ];
}
