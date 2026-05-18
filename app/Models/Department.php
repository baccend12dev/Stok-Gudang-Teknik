<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'm_departments';
    public $timestamps = false;
    protected $fillable = ['code','name','is_active'];
}
