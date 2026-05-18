<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Opname extends Model
{
    protected $table = 't_opname';
    public $timestamps = false;
    protected $fillable = ['opname_date','item_id','qty_system','qty_physical','note'];
}
