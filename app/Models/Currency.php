<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'conversion_rate', 'status'];



    public function product()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
