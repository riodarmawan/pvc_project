<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleLine extends Model
{
    protected $table = 'pos_sale_lines';

    public $timestamps = false;

    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'uom_id',
        'qty',
        'price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'uom_id' => 'integer',
    ];

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
