<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepartmentItemQuota extends Model
{
    protected $table = 'department_item_quotas';

    protected $fillable = [
        'lpb_detail_id',
        'item_id',
        'department_id',
        'quota_quantity',
    ];

    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id');
    }
    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }
    public function lpbDetail()
    {
        return $this->belongsTo('App\LpbDetail', 'lpb_detail_id');
    }
}
