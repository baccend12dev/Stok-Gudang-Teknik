<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $table = 'divisions';

    protected $fillable = ['department_id', 'code', 'name', 'description'];

    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id');
    }

    protected function asDateTime($value)
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \Carbon\Carbon::instance($value);
        }

        if (is_numeric($value)) {
            return \Carbon\Carbon::createFromTimestamp($value);
        }

        if (is_string($value) && preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\.(\d+)$/', $value, $matches)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
        }

        return parent::asDateTime($value);
    }
}
