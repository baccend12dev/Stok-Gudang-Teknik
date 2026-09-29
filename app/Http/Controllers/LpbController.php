<?php

namespace App\Http\Controllers;

use App\Item;
use App\LpbHeader;
use App\LpbDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Helpers\InventoryHelper; 

class LpbController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');

        // === SECURITY LAYER ===
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Anda tidak memiliki izin ke halaman tersebut.');
            }
            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        $q       = trim($request->get('q', ''));
        
        // Default filter tanggal: awal tahun berjalan agar seluruh transaksi tahun ini langsung terlihat di index
        $from    = $request->has('from') ? $request->get('from') : date('Y-01-01');
        $to      = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        // FIX POIN 9: Default Pagination 100
        $perPage = (int) $request->get('perPage', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = LpbHeader::with(array('details.item'))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('lpb_number', 'like', '%' . $q . '%')
                  ->orWhere('vendor', 'like', '%' . $q . '%')
                  ->orWhere('no_po', 'like', '%' . $q . '%')
                  ->orWhere('notes', 'like', '%' . $q . '%');
            });
        }

        if ($from) {
            $query->where('date', '>=', $from);
        }

        if ($to) {
            $query->where('date', '<=', $to);
        }

        if (is_array($allowedItemIds)) {
            $query->whereHas('details', function ($d) use ($allowedItemIds) {
                $d->whereIn('item_id', $allowedItemIds);
            });
        }

        $lpbs = $query->paginate($perPage);

        return view('lpbs.index', array(
            'lpbs'    => $lpbs,
            'q'       => $q,
            'from'    => $from,
            'to'      => $to,
            'perPage' => $perPage,
        ));
    }
   
    public function create(Request $request)
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $itemsQuery = Item::orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }

        $items = $itemsQuery->get();

        return view('lpbs.create', compact('items'));
    }

    public function checkExists(Request $request)
    {
        $noLpb = trim($request->get('no_lpb', ''));
        if ($noLpb === '') {
            return response()->json(array('exists' => false));
        }

        $existing = LpbHeader::where('lpb_number', $noLpb)->first();
        if ($existing) {
            $formattedDate = $existing->date ? $existing->date->format('d/m/Y') : '-';
            $vendorName = $existing->supplier ?: ($existing->vendor ?: '-');
            return response()->json(array(
                'exists'  => true,
                'id'      => $existing->id,
                'no_lpb'  => $existing->lpb_number,
                'date'    => $formattedDate,
                'vendor'  => $vendorName,
                'message' => 'Nomor LPB "' . $noLpb . '" sudah pernah diinput pada tanggal ' . $formattedDate . ' (Vendor: ' . $vendorName . '). Tidak dapat diinput ulang untuk menghindari double pencatatan.'
            ));
        }

        return response()->json(array('exists' => false));
    }

    public function checkForeignLpb(Request $request)
    {
        $noLpb    = trim($request->get('no_lpb', ''));
        $year     = trim($request->get('year', ''));
        $itemType = trim($request->get('item_type', ''));

        if ($noLpb === '') {
            return response()->json(array(
                'status'  => 'error',
                'message' => 'Silakan masukkan No. LPB terlebih dahulu.'
            ), 422);
        }

        // Cek apakah No LPB sudah pernah diinput di Header LPB lokal
        $existing = LpbHeader::where('lpb_number', $noLpb)->first();
        if ($existing) {
            $formattedDate = $existing->date ? $existing->date->format('d/m/Y') : '-';
            $vendorName = $existing->supplier ?: ($existing->vendor ?: '-');
            return response()->json(array(
                'status'  => 'duplicate',
                'message' => 'Nomor LPB "' . $noLpb . '" sudah pernah diinput sebelumnya pada tanggal ' . $formattedDate . ' (Vendor: ' . $vendorName . '). Tidak dapat diinput lagi untuk menghindari double pencatatan.',
                'header'  => array(
                    'id'            => $existing->id,
                    'no_lpb'        => $existing->lpb_number,
                    'formatted_date'=> $formattedDate,
                    'nama_supplier' => $vendorName
                )
            ), 422);
        }

        $query = \App\OttoMasterLpb::where('no_lpb', $noLpb);

        // Filter Jenis jika dipilih (bukan default/semua)
        if ($itemType !== '') {
            $query->where('item_type', $itemType);
        }

        // Filter Tahun jika dipilih (bukan semua)
        if ($year !== '') {
            $query->where('tgl_lpb', 'LIKE', '%' . $year . '%');
        }

        $items = $query->orderBy('tgl_lpb', 'desc')->get();

        if ($items->isEmpty()) {
            $keterangan = 'Data LPB "' . $noLpb . '" tidak ditemukan di Otto Master';
            $detailFilter = array();
            if ($year !== '') {
                $detailFilter[] = 'Tahun ' . $year;
            }
            if ($itemType !== '') {
                $detailFilter[] = 'Jenis ' . ($itemType === 'OPI_ENGINEERING' ? 'Teknik' : 'Expense');
            }
            if (!empty($detailFilter)) {
                $keterangan .= ' untuk ' . implode(' & ', $detailFilter);
            }
            $keterangan .= '.';

            return response()->json(array(
                'status'  => 'error',
                'message' => $keterangan
            ), 404);
        }

        $first = $items->first();

        // Format tanggal jika ada (misal: "01-AUG-2026" -> "2026-08-01")
        $formattedDate = '';
        if ($first->tgl_lpb) {
            $time = strtotime($first->tgl_lpb);
            if ($time) {
                $formattedDate = date('Y-m-d', $time);
            }
        }

        $matchedCount = 0;
        $enrichedItems = array();

        foreach ($items as $it) {
            $oracleCode = trim($it->item_code);
            $oracleDesc = trim($it->item_desc);

            // LOGIKA DIBALIK: Ambil kandidat dari master items lokal berdasarkan oracle_code atau code
            $candidates = \App\Item::where('oracle_code', $oracleCode)
                ->orWhere('code', $oracleCode)
                ->get();

            $bestMatch = null;
            $maxLen = 0;

            foreach ($candidates as $c) {
                $cName = trim($c->name);
                if (empty($cName)) continue;

                // 1. Cek exact match
                if (strcasecmp($oracleDesc, $cName) === 0) {
                    $bestMatch = $c;
                    break;
                }

                // 2. Cek apakah deskripsi Oracle diawali dengan nama item di database
                if (stripos($oracleDesc, $cName) === 0) {
                    if (strlen($cName) > $maxLen) {
                        $bestMatch = $c;
                        $maxLen = strlen($cName);
                    }
                }
            }

            // 3. Fallback: jika tidak diawali persis di depan, cek apakah nama lokal ada di dalam deskripsi Oracle
            if (!$bestMatch) {
                foreach ($candidates as $c) {
                    $cName = trim($c->name);
                    if (empty($cName) || strlen($cName) < 5) continue;

                    if (stripos($oracleDesc, $cName) !== false) {
                        if (strlen($cName) > $maxLen) {
                            $bestMatch = $c;
                            $maxLen = strlen($cName);
                        }
                    }
                }
            }

            $qty       = (float) ($it->units ? $it->units : ($it->unitb ? $it->unitb : 1));
            $thUnitb   = (float) $it->th_unitb;
            $unitPrice = $qty > 0 ? ($thUnitb / $qty) : 0;

            if ($bestMatch) {
                $matchedCount++;
            }

            $enrichedItems[] = array(
                'oracle_item_id'    => $it->item_id,
                'item_type'         => $it->item_type,
                'item_code'         => $it->item_code,
                'item_desc'         => $it->item_desc,
                'unitb'             => $it->unitb,
                'units'             => $it->units,
                'quantity'          => $qty,
                'th_unitb'          => $thUnitb,
                'price'             => $unitPrice,
                'no_po'             => $it->no_po,
                'nama_supplier'     => $it->nama_supplier,
                // Data Pencocokan Master Lokal
                'is_matched'        => $bestMatch ? true : false,
                'matched_item_id'   => $bestMatch ? $bestMatch->id : null,
                'matched_item_code' => $bestMatch ? $bestMatch->code : null,
                'matched_item_name' => $bestMatch ? $bestMatch->name : null,
                'matched_stock'     => $bestMatch ? (float) $bestMatch->current_stock : 0,
                'matched_unit'      => $bestMatch && !empty($bestMatch->unit) ? $bestMatch->unit : 'PCS',
            );
        }

        return response()->json(array(
            'status' => 'success',
            'header' => array(
                'no_lpb'         => $first->no_lpb,
                'tgl_lpb'        => $first->tgl_lpb,
                'formatted_date' => $formattedDate,
                'no_po'          => $first->no_po,
                'nama_supplier'  => $first->nama_supplier,
                'item_type'      => $first->item_type,
                'total_items'    => count($items),
                'matched_count'  => $matchedCount,
            ),
            'items'  => $enrichedItems
        ));
    }

    public function getPoRemainingItems($id)
    {
        $po = \App\PurchaseOrder::with('details.item')->find($id);
        if (!$po) {
            return response()->json(['error' => 'PO tidak ditemukan'], 404);
        }

        $items = [];
        foreach ($po->details as $detail) {
            $receivedQty = (float) \App\LpbDetail::where('item_id', $detail->item_id)
                ->whereHas('header', function ($q) use ($po) {
                    $q->where('purchase_order_id', $po->id);
                })
                ->sum('quantity');

            $remaining = max(0.0, (float) $detail->quantity - $receivedQty);

            if ($remaining > 0) {
                $items[] = [
                    'item_id' => $detail->item_id,
                    'code'    => $detail->item->code,
                    'name'    => $detail->item->name,
                    'unit'    => $detail->item->unit,
                    'remaining_qty' => $remaining
                ];
            }
        }

        return response()->json([
            'po_id' => $po->id,
            'supplier_name' => $po->supplier_name,
            'items' => $items
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, array(
            'lpb_number' => 'required|max:50|unique:lpb_headers,lpb_number',
            'date'       => 'required|date',
            'items'      => 'required|array'
        ), array(
            'lpb_number.required' => 'Nomor LPB wajib diisi.',
            'lpb_number.unique'   => 'Nomor LPB ini sudah pernah diinput dan tersimpan di Header LPB. Tidak dapat diinput ulang untuk menghindari double pencatatan.',
            'date.required'       => 'Tanggal penerimaan LPB wajib diisi.',
            'items.required'      => 'Daftar barang LPB wajib diisi.'
        ));

        try {
            DB::beginTransaction();

            $poId = $request->get('purchase_order_id') ? (int) $request->get('purchase_order_id') : null;

            $hdr = LpbHeader::create(array(
                'lpb_number'        => $request->get('lpb_number'),
                'date'              => $request->get('date'),
                'vendor'            => $request->get('vendor'),
                'no_po'             => $request->get('no_po'),
                'notes'             => $request->get('notes'),
                'purchase_order_id' => $poId
            ));

            $items = $request->get('items');
            $savedCount = 0;

            if (is_array($items)) {
                foreach ($items as $row) {
                    $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                    $qty    = isset($row['quantity']) ? (float) $row['quantity'] : 0;
                    $price  = isset($row['price']) ? (float) $row['price'] : 0;

                    if ($itemId > 0 && $qty > 0) {
                        LpbDetail::create(array(
                            'lpb_header_id' => $hdr->id,
                            'item_id'       => $itemId,
                            'quantity'      => $qty,
                            'price'         => $price,
                            'total'         => $qty * $price
                        ));

                        InventoryHelper::recordMovement(
                            $itemId,
                            $hdr->date,
                            'LPB',
                            $hdr->id,
                            $hdr->lpb_number,
                            $qty
                        );

                        $savedCount++;
                    }
                }
            }

            if ($savedCount === 0) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', 'Tidak ada barang yang terhubung ke Master Gudang. Minimal 1 barang harus terdaftar di master agar stok dapat diperbarui.');
            }

            if ($poId) {
                \App\PurchaseOrder::updateStatus($poId);
            }

            DB::commit();

            // Pastikan rentang tanggal mencakup tanggal LPB yang baru disimpan agar langsung tampil di baris teratas index
            $redirectFrom = date('Y-01-01', strtotime($hdr->date));
            if (strtotime($hdr->date) < strtotime(date('Y-01-01'))) {
                $redirectFrom = date('Y-m-d', strtotime($hdr->date));
            }

            return redirect()
                ->route('lpbs.index', array(
                    'from' => $redirectFrom,
                    'to'   => date('Y-m-d')
                ))
                ->with('success', 'LPB ' . $hdr->lpb_number . ' berhasil disimpan dan stok (' . $savedCount . ' item) telah diperbarui.');

        } catch (QueryException $e) {
            DB::rollBack();
            $code = $e->getCode();
            $msg  = $e->getMessage();

            if (
                $code == '23505' ||
                $code == '23000' ||
                strpos($msg, 'duplicate') !== false ||
                strpos($msg, 'Integrity constraint') !== false
            ) {
                return back()
                    ->withInput()
                    ->withErrors(array(
                        'lpb_number' => 'No LPB sudah digunakan. Gunakan nomor lain.'
                    ));
            }
            
            return back()
                ->withInput()
                ->with('error', 'Gagal Database: ' . $msg); 

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        return redirect()->route('lpbs.index');
    }

    public function edit($id)
    {
        $hdr = LpbHeader::with('details.item')->findOrFail($id);

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $itemsQuery = Item::orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }

        $items = $itemsQuery->get();

        $purchaseOrders = \App\PurchaseOrder::whereIn('status', ['ORDERED', 'PARTIALLY_RECEIVED'])
            ->orWhere('id', $hdr->purchase_order_id)
            ->orderBy('po_number', 'asc')
            ->get();

        return view('lpbs.edit', array(
            'header'         => $hdr,
            'hdr'            => $hdr, 
            'items'          => $items,
            'purchaseOrders' => $purchaseOrders,
        ));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, array(
            'date'  => 'required|date',
            'items' => 'required|array'
        ));

        DB::beginTransaction();
        try {
            $hdr = LpbHeader::findOrFail($id);
            $oldPoId = $hdr->purchase_order_id;
            $newPoId = $request->get('purchase_order_id') ? (int) $request->get('purchase_order_id') : null;
            
            InventoryHelper::deleteMovement('LPB', $hdr->id);

            $hdr->date              = $request->get('date');
            $hdr->vendor            = $request->get('vendor');
            $hdr->no_po             = $request->get('no_po');
            $hdr->notes             = $request->get('notes');
            $hdr->purchase_order_id = $newPoId;
            $hdr->save();

            LpbDetail::where('lpb_header_id', $hdr->id)->delete();

            $items = $request->get('items');
            if (is_array($items)) {
                foreach ($items as $row) {
                    $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                    $qty    = isset($row['quantity']) ? (float) $row['quantity'] : 0;
                    $price  = isset($row['price']) ? (float) $row['price'] : 0;

                    if ($itemId > 0 && $qty > 0) {
                        LpbDetail::create(array(
                            'lpb_header_id' => $hdr->id,
                            'item_id'       => $itemId,
                            'quantity'      => $qty,
                            'price'         => $price,
                            'total'         => $qty * $price
                        ));

                        InventoryHelper::recordMovement(
                            $itemId,
                            $hdr->date,
                            'LPB',
                            $hdr->id,
                            $hdr->lpb_number,
                            $qty
                        );
                    }
                }
            }

            if ($oldPoId) {
                \App\PurchaseOrder::updateStatus($oldPoId);
            }
            if ($newPoId && $newPoId !== $oldPoId) {
                \App\PurchaseOrder::updateStatus($newPoId);
            }

            DB::commit();

            return redirect()
                ->route('lpbs.index')
                ->with('success', 'LPB berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui LPB: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $hdr = LpbHeader::findOrFail($id);
            $poId = $hdr->purchase_order_id;
            
            InventoryHelper::deleteMovement('LPB', $hdr->id);

            LpbDetail::where('lpb_header_id', $hdr->id)->delete();
            $hdr->delete();

            if ($poId) {
                \App\PurchaseOrder::updateStatus($poId);
            }

            DB::commit();

            return redirect()
                ->route('lpbs.index')
                ->with('success', 'LPB berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal menghapus LPB.');
        }
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return null;
        }

        if (!method_exists($user, 'categoryCodesForScope')) {
            return null;
        }

        $codes = $user->categoryCodesForScope();

        if (!is_array($codes) || count($codes) === 0) {
            return null;
        }

        $ids = \App\Item::select('items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->whereIn('categories.code', $codes)
            ->pluck('items.id')
            ->toArray();

        if (count($ids) === 0) {
            return [-1];
        }

        return $ids;
    }
}