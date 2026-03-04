<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'number',
        'date',
        'due_date',
        'currency',
        'subtotal',
        'discount',
        'total',
        'paid',
        'notes'
    ];

    protected $appends = ['due', 'status'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }


    public function getDueAttribute(): float
    {
        $total = (float) ($this->total ?? 0);
        $paid  = (float) ($this->paid ?? 0);
        return max(0, round($total - $paid, 2));
    }

   public function getStatusAttribute(): string
    {
        $due = $this->due;

        if ($due <= 0) return 'paid';
        if ((float)$this->paid > 0) return 'partial';
        return 'unpaid';
    }
}
