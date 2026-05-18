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
        
        $from    = $request->has('from') ? $request->get('from') : date('Y-m-01');
        $to      = $request->has('to') ? $request->get('to') : date('Y-m-d');
        
        // FIX POIN 9: Default Pagination 100
        $perPage = (int) $request->get('perPage', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = LpbHeader::with(array('details.item'))
            ->orderBy('date', 'desc');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('lpb_number', 'like', '%' . $q . '%')
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
   
    public function create()
    {
        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $itemsQuery = Item::orderBy('name', 'asc');
        if (is_array($allowedItemIds)) {
            $itemsQuery->whereIn('id', $allowedItemIds);
        }

        $items = $itemsQuery->get();

        return view('lpbs.create', compact('items'));
    }

    public function store(Request $request)
    {
        $this->validate($request, array(
            'lpb_number' => 'required|max:50|unique:lpb_headers,lpb_number',
            'date'       => 'required|date',
            'items'      => 'required|array'
        ), array(
            'lpb_number.unique' => 'No LPB sudah digunakan. Gunakan nomor lain.'
        ));

        try {
            DB::beginTransaction();

            $hdr = LpbHeader::create(array(
                'lpb_number' => $request->get('lpb_number'),
                'date'       => $request->get('date'),
                'notes'      => $request->get('notes')
            ));

            $items = $request->get('items');
            if (is_array($items)) {
                foreach ($items as $row) {
                    $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                    
                    // FIX: GANTI (int) JADI (float) AGAR SUPPORT DECIMAL
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

            DB::commit();

            return redirect()
                ->route('lpbs.index')
                ->with('success', 'LPB berhasil disimpan.');

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
            
            // FIX: TAMPILKAN PESAN ERROR DATABASE YANG JELAS
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

        return view('lpbs.edit', array(
            'header' => $hdr,
            'hdr'    => $hdr, 
            'items'  => $items,
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
            
            InventoryHelper::deleteMovement('LPB', $hdr->id);

            $hdr->date  = $request->get('date');
            $hdr->notes = $request->get('notes');
            $hdr->save();

            LpbDetail::where('lpb_header_id', $hdr->id)->delete();

            $items = $request->get('items');
            if (is_array($items)) {
                foreach ($items as $row) {
                    $itemId = isset($row['item_id']) ? (int) $row['item_id'] : 0;
                    
                    // FIX: GANTI (int) JADI (float) AGAR SUPPORT DECIMAL
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
            
            InventoryHelper::deleteMovement('LPB', $hdr->id);

            LpbDetail::where('lpb_header_id', $hdr->id)->delete();
            $hdr->delete();

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