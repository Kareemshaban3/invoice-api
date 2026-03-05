<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id','number','date','due_date','currency',
        'payment_method','payment_status',
        'subtotal','discount','tax_total','total','paid','notes'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) return false;
        return $this->due_date->isPast() && in_array($this->payment_status, ['unpaid','partial'], true);
    }
}
