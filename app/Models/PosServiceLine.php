<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosServiceLine extends Model
{
    protected $table = 'pos_service_lines';

    public $timestamps = false;

    protected $fillable = [
        'pos_sale_id',
        'service_name',
        'price',
        'created_at',
    ];

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
