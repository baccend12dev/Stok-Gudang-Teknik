<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LpbDetail extends Model
{
    protected $table = 'lpb_details';
    protected $fillable = ['lpb_header_id','item_id','quantity','price','total'];
    public $timestamps = true;

    protected $casts = [
        'quantity' => 'float',
        'price'    => 'float',
        'total'    => 'float',
    ];

    public function header()
    {
        return $this->belongsTo(LpbHeader::class, 'lpb_header_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
