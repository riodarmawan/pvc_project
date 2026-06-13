<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'attachments';

    public $timestamps = false;

    protected $fillable = [
        'object_type',
        'object_id',
        'kind',
        'path',
        'uploaded_by',
        'created_at',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
