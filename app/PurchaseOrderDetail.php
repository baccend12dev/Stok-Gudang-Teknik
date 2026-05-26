<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    protected $table = 'purchase_order_details';

    protected $fillable = [
        'purchase_order_id', 'item_id', 'quantity', 'notes'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo('App\PurchaseOrder', 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }
}
