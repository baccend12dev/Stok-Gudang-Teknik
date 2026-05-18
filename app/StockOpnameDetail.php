<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = 'stock_opname_details';

    protected $fillable = array(
        'opname_header_id',
        'item_id',
        'system_quantity',
        'physical_quantity',
        'difference',
        'notes'
    );

    public function header()
    {
        return $this->belongsTo('App\StockOpnameHeader', 'opname_header_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }
}
