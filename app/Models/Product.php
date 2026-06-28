<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'products';

    public $timestamps = false;

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'uom_id',
        'hpp',
        'selling_price',
        'track_by_meter',
        'material',
        'series',
        'pattern_code',
        'finish',
        'length_cm',
        'width_mm',
        'thickness_mm',
        'barcode',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'uom_id' => 'integer',
        'hpp' => 'float',
        'selling_price' => 'float',
        'track_by_meter' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
