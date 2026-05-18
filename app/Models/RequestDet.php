<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RequestDet extends Model
{
    protected $table = 't_req_det';
    public $timestamps = false;
    protected $fillable = ['hdr_id','item_id','qty'];
}
