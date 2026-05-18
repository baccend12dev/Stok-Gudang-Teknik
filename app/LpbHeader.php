<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LpbHeader extends Model
{
    protected $table = 'lpb_headers';
    protected $fillable = array('lpb_number','date','notes');
    public $timestamps = true;

    // penting agar $lpb->date->format() jalan
    protected $dates = array('date','created_at','updated_at');

    // biar bisa akses $lpb->total
    protected $appends = array('total');

    public function details()
    {
        return $this->hasMany('App\LpbDetail', 'lpb_header_id');
    }

    public function getTotalAttribute()
    {
        return (float) $this->details()->sum('total');
    }
}
