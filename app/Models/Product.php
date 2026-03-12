<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'image_path',
        'category_id',
        'supplier_id',
        'stock',
        'reorder_level',
        'units_id',
        'cost_price',
        'default_tax_type',
        'default_tax_rate',
        'status',
    ];

    protected $casts = [
        'stock' => 'integer',
        'reorder_level' => 'integer',
        'cost_price' => 'decimal:2',
        'default_tax_rate' => 'decimal:2',
    ];

    public const TAX_TYPES = ['no_tax', 'exclusive', 'inclusive'];

    public const STATUSES = ['active', 'suspended', 'archived'];

    protected $appends = [
        'image_url',
        'is_low_stock'
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    public static function generateSku(): string
    {
        return 'PRD-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Suppliers::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Units::class, 'units_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->reorder_level > 0 && $this->stock <= $this->reorder_level;
    }
}