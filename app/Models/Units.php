<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Units extends Model
{
    protected $fillable = ['name', 'description', 'code'];

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
