<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Department;
use App\Category;
use App\Item;
use App\DepartmentBudget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DepartmentBudgetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // === SECURITY LAYER: PERFEKSIONIS ===
        // 1. Memblokir User Dept (USER) agar tidak bisa akses halaman ini sama sekali.
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

        // ============================================================
        // 1. LOAD CATEGORIES (DENGAN SCOPE FILTER)
        // ============================================================
        $catQuery = Category::orderBy('name', 'asc');

        // Cek Scope User (General / Apparel / All)
        // Method categoryCodesForScope() mengembalikan array kode kategori atau NULL (jika All)
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
            
            // Jika user punya batasan scope (bukan NULL)
            if ($allowedCodes !== null) {
                $catQuery->whereIn('code', $allowedCodes);
            }
        }

        $categories = $catQuery->get();

        // Jika tidak ada kategori yang diizinkan (misal user baru tanpa scope), return empty
        if ($categories->isEmpty()) {
            return view('budgets.index', [
                'categories'  => [],
                'departments' => [],
                'items'       => [],
                'budgets'     => [],
                'activeCatId' => null
            ])->with('error', 'Anda tidak memiliki akses ke kategori manapun.');
        }

        // ============================================================
        // 2. TENTUKAN KATEGORI AKTIF
        // ============================================================
        $activeCatId = $request->get('cat_id');

        // VALIDASI KEAMANAN:
        // Pastikan cat_id yang diminta ada di dalam daftar kategori milik user ini.
        // Jika user iseng ganti ID di URL ke kategori orang lain, kita tolak.
        if ($activeCatId && !$categories->contains('id', $activeCatId)) {
            $activeCatId = null; // Reset jika tidak valid/tidak berhak
        }

        // Default ke kategori pertama yang dia punya jika tidak ada pilihan valid
        if (!$activeCatId) {
            $activeCatId = $categories->first()->id;
        }

        // ============================================================
        // 3. LOAD DATA (Items & Budgets)
        // ============================================================
        $departments = Department::orderBy('name', 'asc')->get();
        $items = [];
        $budgets = [];

        if ($activeCatId) {
            // Ambil item hanya di kategori aktif (yang sudah dipastikan aman scopenya)
            $items = Item::where('category_id', $activeCatId)
                        ->where('current_status', 'ACTIVE')
                        ->orderBy('code', 'asc')
                        ->get();

            // Mapping Budget: [item_id][dept_id] = value
            if ($items->count() > 0) {
                $itemIds = $items->pluck('id')->toArray();
                $rawBudgets = DepartmentBudget::whereIn('item_id', $itemIds)->get();

                foreach ($rawBudgets as $b) {
                    $budgets[$b->item_id][$b->department_id] = (int)$b->monthly_limit;
                }
            }
        }

        return view('budgets.index', [
            'categories'  => $categories,
            'departments' => $departments,
            'items'       => $items,
            'budgets'     => $budgets,
            'activeCatId' => $activeCatId
        ]);
    }

    public function store(Request $request)
    {
        // Input format: budget[item_id][dept_id] = value
        $inputs = $request->get('budget'); 

        if (!is_array($inputs)) {
            return redirect()->back()->with('warning', 'Tidak ada data yang diubah.');
        }

        // Ambil scope user untuk validasi extra (Optional tapi recommended)
        $user = Auth::user();
        $allowedCodes = null;
        if (method_exists($user, 'categoryCodesForScope')) {
            $allowedCodes = $user->categoryCodesForScope();
        }

        DB::beginTransaction();
        try {
            foreach ($inputs as $itemId => $deptData) {
                
                // VALIDASI EXTRA: Pastikan item yang diedit adalah milik scope user
                // Mencegah "Inspect Element" attack
                if ($allowedCodes !== null) {
                    $itemCheck = Item::with('category')->find($itemId);
                    if ($itemCheck && !in_array($itemCheck->category->code, $allowedCodes)) {
                        continue; // Skip item ini jika bukan haknya
                    }
                }

                foreach ($deptData as $deptId => $limit) {
                    $limit = (int)$limit;

                    // Cek exist
                    $budget = DepartmentBudget::where('item_id', $itemId)
                                ->where('department_id', $deptId)
                                ->first();

                    if ($budget) {
                        // Update
                        $budget->monthly_limit = $limit;
                        $budget->updated_at = \Carbon\Carbon::now();
                        $budget->save();
                    } else {
                        // Create (Hanya jika limit > 0 untuk hemat database)
                        if ($limit > 0) {
                            $new = new DepartmentBudget();
                            $new->item_id = $itemId;
                            $new->department_id = $deptId;
                            $new->monthly_limit = $limit;
                            $new->save();
                        }
                    }
                }
            }

            DB::commit();
            
            // Redirect kembali ke tab kategori yang sama
            $catId = $request->get('active_cat_id');
            return redirect()->route('budgets.index', ['cat_id' => $catId])
                ->with('success', 'Plafon Anggaran berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}