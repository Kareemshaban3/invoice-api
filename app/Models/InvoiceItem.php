<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_type',
        'product_id',
        'product_name',
        'product_description',
        'unit_price',
        'quantity',
        'discount_type',
        'discount_value',
        'tax_type',
        'tax_rate',
        'line_subtotal',
        'line_discount',
        'line_after_discount',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_after_discount' => 'decimal:2',
        'line_tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}