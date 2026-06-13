<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosRefund extends Model
{
    protected $table = 'pos_refunds';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'approved_by',
        'reason',
        'created_at',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'sale_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
