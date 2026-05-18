<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IssueHdr extends Model
{
    protected $table = 't_issue_hdr';
    public $timestamps = false;
    protected $fillable = ['issue_no','issue_date','dept_id','requester','note'];

    public function details() {
        return $this->hasMany('App\Models\IssueDet','hdr_id');
    }
}
