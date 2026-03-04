<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_name',
        'product_description',
        'unit_price',
        'quantity',
        'line_total'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
