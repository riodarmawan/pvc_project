<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';
    public $timestamps = false;

    protected $fillable = [
        'branch_from_id', 'branch_to_id', 'status', 'requested_by', 'approved_by',
        'shipped_at', 'received_at', 'notes',
    ];

    public function branchFrom(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_from_id');
    }

    public function branchTo(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_to_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class, 'transfer_id');
    }
}
