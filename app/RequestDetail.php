<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestDetail extends Model
{
    protected $table = 'request_details';

    protected $fillable = array(
        'request_header_id',
        'item_id',
        'quantity',
        'remarks',
        'processed_qty', // <--- TAMBAHAN BARU
    );

    public function header()
    {
        return $this->belongsTo('App\RequestHeader', 'request_header_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }

    // Helper Attribute: Sisa yang belum diproses
    public function getRemainingQtyAttribute()
    {
        return $this->quantity - $this->processed_qty;
    }
}