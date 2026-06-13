<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    protected $table = 'approvals';

    public $timestamps = false;

    protected $fillable = [
        'object_type',
        'object_id',
        'requested_by',
        'approver_id',
        'status',
        'note',
        'decided_at',
        'created_at',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
