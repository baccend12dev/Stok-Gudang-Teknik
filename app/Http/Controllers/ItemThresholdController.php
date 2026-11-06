<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ItemThresholdController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // SECURITY LAYER:
        // Memblokir User Dept (USER) agar tidak bisa akses halaman ini sama sekali.
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('dashboard')->with('error', 'Akses Ditolak. Halaman ini khusus Admin.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $q       = $request->get('q', '');
        $perPage = (int) $request->get('per_page', 50);
        if ($perPage < 1) {
            $perPage = 50;
        }

        // Base Query Item Aktif
        $query = Item::with('category')
                    ->where('current_status', 'ACTIVE');

        // Scope Filter User (General / Apparel / All)
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
            if ($allowedCodes !== null) {
                $query->whereHas('category', function($cq) use ($allowedCodes) {
                    $cq->whereIn('code', $allowedCodes);
                });
            }
        }

        // Pencarian Nama / Kode Barang
        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->where('name', 'ILIKE', '%' . $q . '%')
                    ->orWhere('code', 'ILIKE', '%' . $q . '%');
            });
        }

        $items = $query->orderBy('code', 'asc')
                       ->paginate($perPage)
                       ->appends($request->except('page'));

        return view('thresholds.index', [
            'items'   => $items,
            'q'       => $q,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        // Input format: threshold[item_id][batas_fast_moving] = value
        //               threshold[item_id][batas_slow_moving] = value
        $inputs = $request->get('threshold'); 

        if (!is_array($inputs)) {
            return redirect()->back()->with('warning', 'Tidak ada data yang diubah.');
        }

        $user = Auth::user();
        $allowedCodes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
        }

        DB::beginTransaction();
        try {
            foreach ($inputs as $itemId => $thresholdData) {
                
                // VALIDASI EXTRA: Pastikan item yang diedit adalah milik scope user
                if ($allowedCodes !== null) {
                    $itemCheck = Item::with('category')->find($itemId);
                    if ($itemCheck && (!isset($itemCheck->category) || !in_array($itemCheck->category->code, $allowedCodes))) {
                        continue;
                    }
                }

                $item = Item::find($itemId);
                if ($item) {
                    $fast = isset($thresholdData['batas_fast_moving']) ? (float)$thresholdData['batas_fast_moving'] : 0.0;
                    $slow = isset($thresholdData['batas_slow_moving']) ? (float)$thresholdData['batas_slow_moving'] : 0.0;

                    $item->batas_fast_moving = $fast;
                    $item->batas_slow_moving = $slow;
                    $item->save();
                }
            }

            DB::commit();
            
            return redirect()->back()->with('success', 'Batas Threshold berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}
