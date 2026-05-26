<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = ['group_id', 'code', 'name', 'is_active', 'description'];

    public function group()
    {
        return $this->belongsTo('App\DepartmentGroup', 'group_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}