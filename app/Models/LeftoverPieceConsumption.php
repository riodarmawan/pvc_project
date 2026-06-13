<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeftoverPieceConsumption extends Model
{
    protected $table = 'leftover_piece_consumptions';

    public $timestamps = false;

    protected $fillable = [
        'piece_id',
        'project_id',
        'used_m',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'used_m' => 'decimal:3',
    ];

    public function piece(): BelongsTo
    {
        return $this->belongsTo(LeftoverPiece::class, 'piece_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
