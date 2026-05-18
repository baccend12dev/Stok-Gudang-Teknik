<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepartmentGroup extends Model
{
    protected $table = 'department_groups';
    protected $fillable = ['code', 'name'];

    public function departments()
    {
        return $this->hasMany('App\Department', 'group_id');
    }
}