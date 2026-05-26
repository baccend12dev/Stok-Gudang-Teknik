<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Item;
use App\Category;
use Session;

class ItemController extends Controller
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
        $q       = $request->get('q', '');
        $status  = $request->get('status', 'all');
        $unit    = $request->get('unit', 'all');
        
        // FIX POIN 9: Default Pagination 100
        $perPage = (int) $request->get('per_page', 100);
        if ($perPage < 1) {
            $perPage = 100;
        }

        $sort    = $request->get('sort', 'code');
        $dir     = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $units = DB::table('items')
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit', 'asc')
            ->pluck('unit')
            ->all();

        $allowedItemIds = $this->getAllowedItemIdsForCurrentUser();

        $query = DB::table('v_item_saldo as v')
            ->leftJoin('items as i', 'i.id', '=', 'v.id')
            ->select(
                'v.id',
                'v.code',
                'v.name',
                DB::raw("COALESCE(i.unit, '') as unit"),
                DB::raw("COALESCE(i.location, '-') as location"),
                DB::raw("COALESCE(i.current_stock, 0) as current_stock"),
                DB::raw("COALESCE(i.buffer_min, 0) as buffer_min"),
                DB::raw("COALESCE(i.current_status, '') as current_status"),
                DB::raw("COALESCE((
                    SELECT SUM(
                        GREATEST(0, pod.quantity - COALESCE(
                            (SELECT SUM(lpd.quantity)
                             FROM lpb_details lpd
                             JOIN lpb_headers lph ON lph.id = lpd.lpb_header_id
                             WHERE lph.purchase_order_id = po.id AND lpd.item_id = pod.item_id
                            ), 0
                        ))
                    )
                    FROM purchase_order_details pod
                    JOIN purchase_orders po ON po.id = pod.purchase_order_id
                    WHERE po.status IN ('ORDERED', 'PARTIALLY_RECEIVED') AND pod.item_id = v.id
                ), 0) as ordered_qty")
            );

        if (is_array($allowedItemIds)) {
            $query->whereIn('v.id', $allowedItemIds);
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            if (DB::getDriverName() === 'pgsql') {
                $query->where(function ($w) use ($like) {
                    $w->where('v.code', 'ILIKE', $like)
                      ->orWhere('v.name', 'ILIKE', $like)
                      ->orWhere('i.location', 'ILIKE', $like);
                });
            } else {
                $query->where(function ($w) use ($like) {
                    $w->where('v.code', 'like', $like)
                      ->orWhere('v.name', 'like', $like)
                      ->orWhere('i.location', 'like', $like);
                });
            }
        }

        if ($status !== 'all' && $status !== '' && $status !== null) {
            $map        = array('AKTIF' => 'ACTIVE', 'NONAKTIF' => 'NONACTIVE');
            $key        = strtoupper($status);
            $normalized = isset($map[$key]) ? $map[$key] : $status;
            $query->where('i.current_status', '=', $normalized);
        }

        if ($unit !== 'all' && $unit !== '' && $unit !== null) {
            $query->where('i.unit', $unit);
        }

        $allowedSort = array('code', 'name', 'unit', 'location', 'current_stock', 'buffer_min', 'current_status');
        if (!in_array($sort, $allowedSort)) {
            $sort = 'code';
        }

        switch ($sort) {
            case 'name':
                $query->orderBy('v.name', $dir);
                break;
            case 'unit':
                $query->orderBy('i.unit', $dir);
                break;
            case 'location':
                $query->orderBy('i.location', $dir);
                break;
            case 'current_stock':
                $query->orderBy('i.current_stock', $dir);
                break;
            case 'buffer_min':
                $query->orderBy('i.buffer_min', $dir);
                break;
            case 'current_status':
                $query->orderBy('i.current_status', $dir)
                      ->orderBy('v.code', 'asc');
                break;
            default:
                $query->orderBy('v.code', $dir);
        }

        $items = $query->paginate($perPage);

        $items->appends(array(
            'q'        => $q,
            'status'   => $status,
            'unit'     => $unit,
            'per_page' => $perPage,
            'sort'     => $sort,
            'dir'      => $dir
        ));

        return view('items.index', array(
            'items'   => $items,
            'q'       => $q,
            'status'  => $status,
            'unit'    => $unit,
            'perPage' => $perPage,
            'sort'    => $sort,
            'dir'     => $dir,
            'units'   => $units
        ));
    }

    public function create()
    {
        $user = auth()->user();
        
        $codes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $codes = $user->categoryCodesForScope();
        }

        if ($codes && count($codes) > 0) {
            $categories = Category::whereIn('code', $codes)->get();
        } else {
            $categories = Category::all();
        }

        $units = DB::table('items')
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit', 'asc')
            ->pluck('unit')
            ->all();

        return view('items.create', array(
            'categories' => $categories,
            'units'      => $units
        ));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code'        => 'required', 
            'name'        => 'required',
            'unit'        => 'required',
            'category_id' => 'required',
            // FIX: Validasi NUMERIC agar bisa terima desimal (cth: 0.5)
            'current_stock' => 'numeric|min:0',
            'buffer_min'    => 'numeric|min:0',
        ]);

        $item = new Item;
        $item->code = $request->code;
        $item->name = $request->name;
        $item->unit = $request->unit;
        
        // FIX: Simpan sebagai float/decimal
        $item->current_stock = $request->input('current_stock', 0);
        $item->buffer_min    = $request->input('buffer_min', 0);
        
        $item->book          = $request->input('book', 'ATK'); 
        $item->location      = $request->input('location', '-');
        
        $item->notes         = $request->notes;
        $item->category_id   = $request->category_id;
        
        $item->save();

        return redirect()->route('items.index')
            ->with('success', 'Berhasil menyimpan ' . $item->name);
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);

        $user = auth()->user();
        $codes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $codes = $user->categoryCodesForScope();
        }

        if ($codes && count($codes) > 0) {
            $categories = Category::whereIn('code', $codes)->get();
        } else {
            $categories = Category::all();
        }

        $units = DB::table('items')
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit', 'asc')
            ->pluck('unit')
            ->all();

        return view('items.edit', array(
            'item'       => $item,
            'categories' => $categories,
            'units'      => $units
        ));
    }

    public function update(Request $request, $id)
    {
        // FIX: Ubah validasi 'integer' menjadi 'numeric' agar support desimal
        $rules = [
            'code'        => 'required', 
            'name'        => 'required',
            'unit'        => 'required',
            'category_id' => 'required',
            'current_stock' => 'numeric|min:0', // <--- INI KUNCINYA BRO!
            'buffer_min'    => 'numeric|min:0'  // <--- INI JUGA!
        ];

        if ($request->has('book')) {
            $rules['book'] = 'required';
        }

        $this->validate($request, $rules);

        $item = Item::findOrFail($id);
        
        $item->code = $request->code;
        $item->name = $request->name;
        $item->unit = $request->unit;
        
        if ($request->has('buffer_min')) {
            $item->buffer_min = $request->buffer_min;
        }
        if ($request->has('location')) {
            $item->location = $request->location;
        }
        if ($request->has('notes')) {
            $item->notes = $request->notes;
        }
        $item->category_id = $request->category_id;

        if ($request->has('current_stock')) {
            $item->current_stock = $request->current_stock;
        }

        if ($request->has('book')) {
            $item->book = $request->book;
        }
        
        if ($request->has('current_status')) {
            $item->current_status = $request->current_status;
        }

        $item->save();

        return redirect()->route('items.index')
            ->with('success', 'Berhasil memperbarui ' . $item->name);
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $itemName = $item->name; 
        $item->delete();
        
        return redirect()->route('items.index')
            ->with('success', 'Item ' . $itemName . ' berhasil dihapus.');
    }

    protected function getAllowedItemIdsForCurrentUser()
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'categoryCodesForScope')) {
            return null;
        }

        $codes = $user->categoryCodesForScope();
        if (!is_array($codes) || count($codes) === 0) {
            return null;
        }

        $ids = \App\Item::whereHas('category', function ($q) use ($codes) {
                $q->whereIn('code', $codes);
            })
            ->pluck('id')
            ->all();

        if (count($ids) === 0) {
            return array(-1); 
        }

        return $ids;
    }
}