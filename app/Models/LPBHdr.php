<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LPBHdr extends Model
{
    protected $table = 't_lpb_hdr';
    public $timestamps = false;
    protected $fillable = ['lpb_no','lpb_date','supplier','note'];

    public function details() {
        return $this->hasMany('App\Models\LPBDet','hdr_id');
    }
}
