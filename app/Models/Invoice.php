<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'number',
        'date',
        'due_date',
        'currency_id',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount',
        'tax_total',
        'total',
        'paid',
        'notes',
        'branches_id',
        'representatives_id',
    ];

    protected $appends = [
        'is_overdue',
        'remaining_amount',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branche::class, 'branches_id');
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class, 'representatives_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class, 'invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isPast()
            && in_array($this->payment_status, ['unpaid', 'partial'], true);
    }

    public function getRemainingAmountAttribute(): string
    {
        $remaining = max(0, (float) $this->total - (float) $this->paid);

        return number_format($remaining, 2, '.', '');
    }
}