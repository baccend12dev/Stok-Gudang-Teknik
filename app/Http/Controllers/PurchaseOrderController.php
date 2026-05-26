<?php

namespace App\Http\Controllers;

use App\Item;
use App\PurchaseOrder;
use App\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Auth;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Security Layer: Blokir User Dept (Hanya Admin / Super Admin yang boleh akses PO)
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak!');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));
        $status = $request->get('status', 'all');
        $perPage = (int) $request->get('perPage', 100);
        if ($perPage <= 0) $perPage = 100;

        $query = PurchaseOrder::with('details.item')->orderBy('date', 'desc');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('po_number', 'ilike', '%' . $q . '%')
                  ->orWhere('supplier_name', 'ilike', '%' . $q . '%')
                  ->orWhere('notes', 'ilike', '%' . $q . '%');
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $pos = $query->paginate($perPage);

        return view('purchase_orders.index', compact('pos', 'q', 'status', 'perPage'));
    }

    public function create(Request $request)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        // Ambil barang terpilih dari Monitor Stok (jika ada)
        $selectedItemIds = $request->get('item_ids', []);
        if (!is_array($selectedItemIds)) {
            $selectedItemIds = [$selectedItemIds];
        }

        // Filter selectedItemIds dengan allowedItemIds
        if (is_array($allowedItemIds)) {
            $selectedItemIds = array_intersect($selectedItemIds, $allowedItemIds);
        }

        $selectedItems = [];
        if (!empty($selectedItemIds)) {
            $selectedItems = Item::whereIn('id', $selectedItemIds)->get();
            // Tambahkan saran qty pembelian untuk setiap item
            $selectedItems->transform(function ($item) {
                $stok = (float) $item->current_stock;
                $buff = (float) $item->buffer_min;
                $saran = 0.0;
                if ($stok <= 0) {
                    $saran = $buff;
                } elseif ($stok < $buff) {
                    $saran = max(0.0, $buff - $stok);
                } else {
                    $saran = 0.0;
                }
                $item->suggested_qty = $saran;
                return $item;
            });
        }

        // List semua barang untuk pilihan dinamis
        $itemsQuery = Item::where('current_status', 'ACTIVE')->orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $allItems = $itemsQuery->get();

        // Generate PO Number Otomatis format PO-YYYYMMDD-XXXX
        $today = Carbon::today();
        // Hitung total PO yang dibuat hari ini untuk increment
        $todayCount = PurchaseOrder::whereDate('created_at', $today)->count();
        $autoPoNumber = 'PO-' . date('Ymd') . '-' . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return view('purchase_orders.create', compact('selectedItems', 'allItems', 'autoPoNumber'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'po_number' => 'required|max:50|unique:purchase_orders,po_number',
            'date'      => 'required|date',
            'items'     => 'required|array'
        ], [
            'po_number.unique' => 'No PO sudah digunakan. Sistem akan membuat nomor baru otomatis.'
        ]);

        try {
            DB::beginTransaction();

            $status = $request->get('action') === 'ordered' ? 'ORDERED' : 'DRAFT';

            $po = PurchaseOrder::create([
                'po_number'     => $request->get('po_number'),
                'date'          => $request->get('date'),
                'supplier_name' => $request->get('supplier_name'),
                'status'        => $status,
                'notes'         => $request->get('notes')
            ]);

            $items = $request->get('items');
            foreach ($items as $row) {
                $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                $qty = isset($row['quantity']) ? (float) $row['quantity'] : 0;
                $notes = isset($row['notes']) ? $row['notes'] : null;

                if ($itemId > 0 && $qty > 0) {
                    PurchaseOrderDetail::create([
                        'purchase_order_id' => $po->id,
                        'item_id'           => $itemId,
                        'quantity'          => $qty,
                        'notes'             => $notes
                    ]);
                }
            }

            DB::commit();

            $message = $status === 'ORDERED' 
                ? 'Purchase Order berhasil dibuat dan berstatus dalam pemesanan.' 
                : 'Rencana Pembelian berhasil disimpan sebagai Draft.';

            return redirect()->route('purchase-orders.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan PO: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $po = PurchaseOrder::with('details.item')->findOrFail($id);

        // Tambahkan informasi sisa quantity yang belum diterima untuk setiap detail
        foreach ($po->details as $detail) {
            $receivedQty = (float) \App\LpbDetail::where('item_id', $detail->item_id)
                ->whereHas('header', function ($q) use ($po) {
                    $q->where('purchase_order_id', $po->id);
                })
                ->sum('quantity');

            $detail->received_qty = $receivedQty;
            $detail->remaining_qty = max(0.0, (float) $detail->quantity - $receivedQty);
        }

        return view('purchase_orders.show', compact('po'));
    }

    public function edit($id)
    {
        $po = PurchaseOrder::with('details.item')->findOrFail($id);

        if (in_array($po->status, ['RECEIVED', 'CANCELLED'])) {
            return redirect()->route('purchase-orders.show', $po->id)
                ->with('error', 'PO yang sudah selesai / dibatalkan tidak dapat diedit.');
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();
        $itemsQuery = Item::where('current_status', 'ACTIVE')->orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }
        $allItems = $itemsQuery->get();

        return view('purchase_orders.edit', compact('po', 'allItems'));
    }

    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if (in_array($po->status, ['RECEIVED', 'CANCELLED'])) {
            return redirect()->route('purchase-orders.show', $po->id)
                ->with('error', 'PO yang sudah selesai / dibatalkan tidak dapat diupdate.');
        }

        $this->validate($request, [
            'date'  => 'required|date',
            'items' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $status = $po->status;
            // Jika tombol "Simpan & Pesan" ditekan, naikkan status dari DRAFT ke ORDERED
            if ($request->get('action') === 'ordered' && $status === 'DRAFT') {
                $status = 'ORDERED';
            }

            $po->date = $request->get('date');
            $po->supplier_name = $request->get('supplier_name');
            $po->notes = $request->get('notes');
            $po->status = $status;
            $po->save();

            // Hapus detail lama dan ganti dengan yang baru
            PurchaseOrderDetail::where('purchase_order_id', $po->id)->delete();

            $items = $request->get('items');
            foreach ($items as $row) {
                $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                $qty = isset($row['quantity']) ? (float) $row['quantity'] : 0;
                $notes = isset($row['notes']) ? $row['notes'] : null;

                if ($itemId > 0 && $qty > 0) {
                    PurchaseOrderDetail::create([
                        'purchase_order_id' => $po->id,
                        'item_id'           => $itemId,
                        'quantity'          => $qty,
                        'notes'             => $notes
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', 'Purchase Order berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui PO: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        
        try {
            DB::beginTransaction();
            // Cascade delete di DB migration akan menghapus detail otomatis, tapi kita hapus manual untuk keamanan Eloquent event
            PurchaseOrderDetail::where('purchase_order_id', $po->id)->delete();
            $po->delete();
            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', 'PO berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus PO: ' . $e->getMessage());
        }
    }

    public function createFromAlerts(Request $request)
    {
        $itemIds = $request->get('item_ids', []);
        if (empty($itemIds)) {
            return redirect()->route('buffer-alerts.index')->with('error', 'Silakan pilih minimal satu barang.');
        }

        return redirect()->route('purchase-orders.create', ['item_ids' => $itemIds]);
    }

    public function markAsOrdered($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status !== 'DRAFT') {
            return back()->with('error', 'Hanya PO Draft yang dapat diubah menjadi Dipesan.');
        }

        $po->status = 'ORDERED';
        $po->save();

        return redirect()->route('purchase-orders.show', $po->id)
            ->with('success', 'Status PO berhasil diubah menjadi Dalam Pemesanan.');
    }

    public function cancel($id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if (in_array($po->status, ['RECEIVED', 'CANCELLED'])) {
            return back()->with('error', 'PO yang sudah selesai atau dibatalkan tidak dapat dibatalkan lagi.');
        }

        $po->status = 'CANCELLED';
        $po->save();

        return redirect()->route('purchase-orders.show', $po->id)
            ->with('success', 'Purchase Order berhasil dibatalkan.');
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = Auth::user();
        if (!$user) return null;

        $scope = $user->inventory_scope;
        if ($scope === 'ALL' || $scope === null || $scope === '') return null;

        $categoryCodes = [];
        if (method_exists($user, 'categoryCodesForScope')) {
            $categoryCodes = $user->categoryCodesForScope();
        } else {
            if ($scope === 'GENERAL') {
                $categoryCodes = ['UNCAT', 'ATK', 'SBN', 'AKB', 'UMM'];
            } elseif ($scope === 'APPAREL') {
                $categoryCodes = ['AK', 'PK'];
            }
        }

        if (empty($categoryCodes)) return null;

        return Item::whereHas('category', function ($q) use ($categoryCodes) {
            $q->whereIn('code', $categoryCodes);
        })->pluck('id')->toArray();
    }
}
