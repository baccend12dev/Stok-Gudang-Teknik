<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ItemMovement extends Model
{
    protected $table = 'item_movements';

    protected $fillable = [
        'item_id', 'date', 'type', 'reference_id', 
        'reference_number', 'quantity', 'balance_after'
    ];

    public function item()
    {
        return $this->belongsTo('App\Item');
    }
}