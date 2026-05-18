<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IssueDet extends Model
{
    protected $table = 't_issue_det';
    public $timestamps = false;
    protected $fillable = ['hdr_id','item_id','qty'];

    public function item() { return $this->belongsTo('App\Models\Item','item_id'); }
}
