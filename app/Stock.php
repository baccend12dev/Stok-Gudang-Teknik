<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 't_stock';
    protected $fillable = ['item_id', 'saldo'];

    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }
}