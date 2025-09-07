<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branches';
    public $timestamps = false;
    protected $fillable = ['code','name','address','phone','is_active'];
}
