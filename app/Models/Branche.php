<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branche extends Model
{
    protected $fillable = ['name', 'number', 'address', 'status'];
    
}
