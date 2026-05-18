<?php

namespace App\Helpers;

use App\Item;
use App\ItemMovement;
use Illuminate\Support\Facades\DB;

class InventoryHelper
{
    /**
     * Mencatat pergerakan stok (Masuk/Keluar)
     * * @param int $itemId
     * @param string $date (Y-m-d)
     * @param string $type (LPB, BON, OPNAME, ADJUSTMENT)
     * @param int $refId (ID Header)
     * @param string $refNumber (Nomor Header)
     * @param int $qty (Positif = Masuk, Negatif = Keluar)
     */
    public static function recordMovement($itemId, $date, $type, $refId, $refNumber, $qty)
    {
        // 1. Ambil item
        $item = Item::find($itemId);
        if (!$item) return;

        // 2. Hitung saldo baru
        $newBalance = $item->current_stock + $qty;

        // 3. Simpan ke Ledger (Movement)
        $move = new ItemMovement();
        $move->item_id = $itemId;
        $move->date = $date;
        $move->type = $type;
        $move->reference_id = $refId;
        $move->reference_number = $refNumber;
        $move->quantity = $qty;
        $move->balance_after = $newBalance;
        $move->save();

        // 4. Update Master Item
        $item->current_stock = $newBalance;
        $item->save();
    }

    /**
     * Menghapus transaksi stok & mengembalikan saldo
     * Dipakai saat menghapus LPB atau membatalkan BON
     */
    public static function deleteMovement($type, $refId, $itemId = null)
    {
        $query = ItemMovement::where('type', $type)->where('reference_id', $refId);
        
        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        $movements = $query->get();

        foreach ($movements as $move) {
            $item = Item::find($move->item_id);
            if ($item) {
                // Balikin stok (kebalikan dari qty transaksi)
                $item->current_stock = $item->current_stock - $move->quantity;
                $item->save();
            }
            $move->delete();
        }
    }
}