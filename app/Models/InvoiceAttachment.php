<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceAttachment extends Model
{
    protected $fillable = [
        'invoice_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
