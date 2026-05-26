<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number', 'date', 'supplier_name', 'status', 'notes'
    ];

    public function details()
    {
        return $this->hasMany('App\PurchaseOrderDetail', 'purchase_order_id');
    }

    public function lpbs()
    {
        return $this->hasMany('App\LpbHeader', 'purchase_order_id');
    }

    public static function updateStatus($poId)
    {
        $po = self::with('details')->find($poId);
        if (!$po) return;

        // If PO is cancelled or in draft, don't auto-update from LPB
        if (in_array($po->status, ['DRAFT', 'CANCELLED'])) {
            return;
        }

        $allFullyReceived = true;
        $anyReceived = false;

        foreach ($po->details as $detail) {
            $receivedQty = (float) \App\LpbDetail::where('item_id', $detail->item_id)
                ->whereHas('header', function ($q) use ($poId) {
                    $q->where('purchase_order_id', $poId);
                })
                ->sum('quantity');

            if ($receivedQty > 0) {
                $anyReceived = true;
            }

            if ($receivedQty < (float) $detail->quantity) {
                $allFullyReceived = false;
            }
        }

        if ($allFullyReceived) {
            $po->status = 'RECEIVED';
        } elseif ($anyReceived) {
            $po->status = 'PARTIALLY_RECEIVED';
        } else {
            $po->status = 'ORDERED';
        }

        $po->save();
    }
}
