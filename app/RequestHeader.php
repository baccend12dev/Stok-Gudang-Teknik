<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestHeader extends Model
{
    protected $table = 'request_headers';

    protected $fillable = array(
        'request_number',
        'date',
        'department_id',
        'user_id',
        'notes',
        'status',
        'bon_header_id',
    );

    protected $dates = array('date', 'created_at', 'updated_at');

    public function details()
    {
        return $this->hasMany('App\RequestDetail', 'request_header_id');
    }

    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function bon()
    {
        return $this->belongsTo('App\BonHeader', 'bon_header_id');
    }
}
