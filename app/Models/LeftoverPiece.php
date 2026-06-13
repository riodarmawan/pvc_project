<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeftoverPiece extends Model
{
    protected $table = 'leftover_pieces';

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'product_id',
        'length_m',
        'condition',
        'source_type',
        'source_id',
        'reserved_project_id',
        'consumed_at',
        'created_at',
    ];

    protected $casts = [
        'length_m' => 'decimal:3',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function reservedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'reserved_project_id');
    }
}
