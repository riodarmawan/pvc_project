<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReturnLine extends Model
{
    protected $table = 'material_return_lines';

    public $timestamps = false;

    protected $fillable = [
        'material_return_id',
        'product_id',
        'uom_id',
        'qty_returned',
        'condition',
        'writeoff_qty',
    ];

    protected $casts = [
        'uom_id' => 'integer',
    ];

    public function materialReturn(): BelongsTo
    {
        return $this->belongsTo(MaterialReturn::class, 'material_return_id');
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
