<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branche extends Model
{
    protected $fillable = [
        'name',
        'number',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'branches_id');
    }
}
