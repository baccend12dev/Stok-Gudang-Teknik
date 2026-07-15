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

        // 1. LOAD CATEGORIES (DENGAN SCOPE FILTER)
        $catQuery = Category::orderBy('name', 'asc');

        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
            if ($allowedCodes !== null) {
                $catQuery->whereIn('code', $allowedCodes);
            }
        }

        $categories = $catQuery->get();

        if ($categories->isEmpty()) {
            return view('thresholds.index', [
                'categories'  => [],
                'items'       => [],
                'activeCatId' => null
            ])->with('error', 'Anda tidak memiliki akses ke kategori manapun.');
        }

        // 2. TENTUKAN KATEGORI AKTIF
        $activeCatId = $request->get('cat_id');

        if ($activeCatId && !$categories->contains('id', $activeCatId)) {
            $activeCatId = null;
        }

        if (!$activeCatId) {
            $activeCatId = $categories->first()->id;
        }

        // 3. LOAD DATA Items
        $items = [];
        if ($activeCatId) {
            $items = Item::where('category_id', $activeCatId)
                        ->where('current_status', 'ACTIVE')
                        ->orderBy('code', 'asc')
                        ->get();
        }

        return view('thresholds.index', [
            'categories'  => $categories,
            'items'       => $items,
            'activeCatId' => $activeCatId
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
                    if ($itemCheck && !in_array($itemCheck->category->code, $allowedCodes)) {
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
            
            $catId = $request->get('active_cat_id');
            return redirect()->route('thresholds.index', ['cat_id' => $catId])
                ->with('success', 'Batas Threshold berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}
