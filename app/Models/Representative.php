<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Representative extends Model
{

    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'country_code',
        'address',
        'tax_number',
        'commercial_register',
        'credit_limit',
        'opening_balance',
        'default_payment_method',
        'sales_rep_id',
        'internal_notes'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
