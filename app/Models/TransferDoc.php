<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferDoc extends Model
{
    protected $table = 'transfer_docs';
    public $timestamps = false;

    protected $fillable = ['transfer_id', 'doc_no', 'qr_token'];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }
}
