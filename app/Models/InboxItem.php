<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxItem extends Model
{
    protected $table = 'inbox_items';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'object_type',
        'object_id',
        'subject',
        'status',
        'created_at',
        'read_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
