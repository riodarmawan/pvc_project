<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialReturn extends Model
{
    protected $table = 'material_returns';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'returned_by',
        'processed_by',
        'status',
        'returned_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MaterialReturnLine::class, 'material_return_id');
    }
}
