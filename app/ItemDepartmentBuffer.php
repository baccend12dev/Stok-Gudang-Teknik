<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemDepartmentBuffer extends Model
{
    protected $table = 'item_department_buffers';
    public $timestamps = false; // kalau tabel ini memang tanpa timestamps

    protected $fillable = ['item_id','department_id','buffer_min'];

    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id', 'id');
    }
}
