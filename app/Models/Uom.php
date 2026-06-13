<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $table = 'uoms';
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = ['code', 'name'];
}
