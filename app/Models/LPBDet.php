<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LPBDet extends Model
{
    protected $table = 't_lpb_det';
    public $timestamps = false;
    protected $fillable = ['hdr_id','item_id','qty'];

    public function item() { return $this->belongsTo('App\Models\Item','item_id'); }
}
