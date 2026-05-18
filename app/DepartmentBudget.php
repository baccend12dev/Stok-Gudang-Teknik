<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    protected $table = 'department_budgets';
    protected $fillable = ['department_id', 'item_id', 'monthly_limit'];

    public function item() {
        return $this->belongsTo('App\Item');
    }

    public function department() {
        return $this->belongsTo('App\Department');
    }
}