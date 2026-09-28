<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LpbHeader extends Model
{
    protected $table = 'lpb_headers';
    protected $fillable = array('lpb_number', 'date', 'vendor', 'no_po', 'notes', 'purchase_order_id');
    public $timestamps = true;

    // penting agar $lpb->date->format() jalan
    protected $dates = array('date', 'created_at', 'updated_at');

    // biar bisa akses $lpb->total & $lpb->supplier
    protected $appends = array('total', 'supplier');

    public function details()
    {
        return $this->hasMany('App\LpbDetail', 'lpb_header_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\PurchaseOrder', 'purchase_order_id');
    }

    public function getTotalAttribute()
    {
        return (float) $this->details()->sum('total');
    }

    /**
     * Alias supplier -> vendor (atau PO supplier jika ada)
     */
    public function getSupplierAttribute()
    {
        if (!empty($this->vendor)) {
            return $this->vendor;
        }

        if ($this->purchaseOrder && !empty($this->purchaseOrder->supplier_name)) {
            return $this->purchaseOrder->supplier_name;
        }

        return null;
    }
}
