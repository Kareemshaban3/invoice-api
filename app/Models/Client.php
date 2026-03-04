<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['name','phone','email','country'];

    protected $appends = ['total_due'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTotalDueAttribute()
    {
        return (float) $this->invoices()
            ->selectRaw('COALESCE(SUM(total - paid), 0) as due_sum')
            ->value('due_sum');
    }
}
