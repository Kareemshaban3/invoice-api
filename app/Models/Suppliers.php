<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suppliers extends Model
{
    protected $fillable = [
        'name','phone','email','country_code','city','address',
        'tax_number','payment_terms_days','credit_limit','opening_balance',
        'default_payment_method','bank_name','bank_account_number','iban',
        'category_id','status','notes','total_price','total_orders'
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_orders' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
