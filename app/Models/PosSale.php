<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $table = 'pos_sales';

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'cashier_id',
        'customer_id',
        'project_id',
        'sale_datetime',
        'status',
        'total',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosSaleLine::class, 'pos_sale_id');
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(PosServiceLine::class, 'pos_sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_sale_id');
    }
}
