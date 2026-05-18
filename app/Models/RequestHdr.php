<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RequestHdr extends Model
{
    protected $table = 't_req_hdr';
    public $timestamps = false;
    protected $fillable = ['req_no','req_date','dept_id','requester','status','note'];

    public function details(){ return $this->hasMany('App\Models\RequestDet','hdr_id'); }
}
