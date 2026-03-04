<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    protected $fillable = [
        'name',
        'email',
        'total_price',
        'city',
        'total_orders',
        'phone',
        'status',
    ];

    
}
