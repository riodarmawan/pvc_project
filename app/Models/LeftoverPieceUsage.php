<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeftoverPieceUsage extends Model
{
    protected $table = 'leftover_piece_usages';

    public $timestamps = false;

    protected $fillable = [
        'leftover_piece_id',
        'project_id',
        'length_used_m',
        'used_at',
        'note',
    ];

    protected $casts = [
        'length_used_m' => 'decimal:3',
    ];

    public function leftoverPiece(): BelongsTo
    {
        return $this->belongsTo(LeftoverPiece::class, 'leftover_piece_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
