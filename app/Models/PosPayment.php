<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosPayment extends Model
{
    protected $table = 'pos_payments';

    public $timestamps = false;

    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'ref_no',
    ];

    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }
}
