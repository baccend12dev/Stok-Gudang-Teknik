<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'm_items';
    public $timestamps = false;
    protected $fillable = ['code','name','uom','buffer_min','is_active'];
}
