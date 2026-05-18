<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockOpnameHeader extends Model
{
    protected $table = 'stock_opname_headers';

    protected $fillable = array('opname_date', 'notes', 'status'); // Tambah status

    protected $dates = array('opname_date', 'created_at', 'updated_at');

    public function details()
    {
        return $this->hasMany('App\StockOpnameDetail', 'opname_header_id');
    }

    public function getTotalDifferenceAttribute()
    {
        if ($this->relationLoaded('details')) {
            return (int) $this->details->sum('difference');
        }
        return (int) $this->details()->sum('difference');
    }

    public function setOpnameDateAttribute($value)
    {
        if ($value instanceof Carbon) {
            $this->attributes['opname_date'] = $value;
        } else {
            $this->attributes['opname_date'] = Carbon::parse($value);
        }
    }
}