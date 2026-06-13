<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockQuant extends Model
{
    protected $table = 'stock_quants';
    public $timestamps = false;

    protected $fillable = ['product_id', 'location_id', 'qty'];

    protected $casts = ['qty' => 'float'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}
