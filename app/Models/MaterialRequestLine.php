<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestLine extends Model
{
    protected $table = 'material_request_lines';

    public $timestamps = false;

    protected $fillable = [
        'mr_id',
        'product_id',
        'uom_id',
        'qty_requested',
        'qty_approved',
        'qty_issued',
    ];

    protected $casts = [
        'uom_id' => 'integer',
    ];

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'mr_id');
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
