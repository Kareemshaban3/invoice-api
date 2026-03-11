<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name','site_url','site_email',
        'items_per_page','default_',
        'logo_path','invoice_footer'
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) return null;
        return asset('storage/' . $this->logo_path);
    }
}
