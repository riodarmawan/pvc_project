<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';
    public $timestamps = false;

    protected $fillable = [
        'branch_id', 'customer_id', 'code', 'title', 'status', 'created_by',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function boms(): HasMany
    {
        return $this->hasMany(ProjectBom::class, 'project_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ProjectService::class, 'project_id');
    }
}
