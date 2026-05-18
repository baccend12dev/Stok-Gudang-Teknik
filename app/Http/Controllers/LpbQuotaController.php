<?php

// app/Http/Controllers/LpbQuotaController.php
namespace App\Http\Controllers;

use App\LpbHeader;
use App\Department;
use App\DepartmentItemQuota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LpbQuotaController extends Controller
{   
    public function __construct()
    {
        $this->middleware('auth');

        // === SECURITY LAYER: PERFEKSIONIS ===
        // Memblokir User Dept (USER) agar tidak bisa akses Controller Admin ini.
        // Jika nekat akses URL manual, tendang balik ke halaman Request.
        $this->middleware(function ($request, $next) {
            $user = \Auth::user();
            if ($user && $user->role === 'USER') {
                return redirect()->route('requests.index')->with('error', 'Akses Ditolak! Anda tidak memiliki izin ke halaman tersebut.');
            }
            return $next($request);
        });
    }
    
    /**
     * Helper: ambil daftar departemen aktif (kompatibel dengan/tanpa kolom is_active)
     */
    protected function getActiveDepartments()
    {
        $q = Department::query();

        // Kalau kolom is_active ada, filter yang aktif saja
        if (Schema::hasColumn('departments', 'is_active')) {
            $q->where('is_active', 1);
        }

        return $q->orderBy('name', 'asc')->get();
    }

    /**
     * Form atur jatah untuk 1 LPB
     */
    public function edit($lpbId)
    {
        $lpb = LpbHeader::with(['details.item'])->findOrFail($lpbId);

        // GANTI: pakai helper yang aman meskipun kolom is_active tidak ada
        $departments = $this->getActiveDepartments();

        $existing = DepartmentItemQuota::whereIn('lpb_detail_id', $lpb->details->pluck('id')->all())
            ->get()
            ->groupBy('lpb_detail_id');

        return view('lpbs.quotas', compact('lpb', 'departments', 'existing'));
    }

    /**
     * Simpan jatah per departemen untuk 1 LPB
     */
    public function update(Request $request, $lpbId)
    {
        $lpb = LpbHeader::with('details')->findOrFail($lpbId);
        $payload = $request->get('quota', []);

        // GANTI: ambil ID departemen valid pakai helper supaya tidak error kolom is_active
        $validDeptIds = $this->getActiveDepartments()->pluck('id')->toArray();

        DB::beginTransaction();
        try {
            foreach ($lpb->details as $d) {
                $rows = isset($payload[$d->id]) ? $payload[$d->id] : [];
                $existing = DepartmentItemQuota::where('lpb_detail_id', $d->id)
                    ->get()
                    ->keyBy('department_id');

                $totalAlloc = 0;

                foreach ($rows as $deptId => $qty) {
                    // pastikan departemen termasuk daftar yang valid
                    if (!in_array((int)$deptId, $validDeptIds, true)) {
                        continue;
                    }

                    $qty = (int)($qty === '' ? 0 : $qty);
                    if ($qty < 0) {
                        $qty = 0;
                    }

                    $totalAlloc += $qty;

                    $data = [
                        'lpb_detail_id'  => $d->id,
                        'item_id'        => $d->item_id,
                        'department_id'  => (int)$deptId,
                        'quota_quantity' => $qty,
                    ];

                    if ($existing->has((int)$deptId)) {
                        // update record yang sudah ada
                        $existing->get((int)$deptId)->update($data);
                    } else {
                        // buat record baru
                        DepartmentItemQuota::create($data);
                    }
                }

                // Validasi: total jatah tidak boleh melebihi qty LPB
                if ($totalAlloc > (int)$d->quantity) {
                    DB::rollBack();
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(
                            'Total jatah melebihi Qty LPB untuk [' .
                            $d->item->code . '] ' . $d->item->name
                        );
                }

                // Bersihkan departemen yang sudah tidak ada di form
                $keepIds = array_map('intval', array_keys($rows));
                if (count($keepIds)) {
                    DepartmentItemQuota::where('lpb_detail_id', $d->id)
                        ->whereNotIn('department_id', $keepIds)
                        ->delete();
                } else {
                    DepartmentItemQuota::where('lpb_detail_id', $d->id)->delete();
                }
            }

            DB::commit();

            return redirect()
                ->route('lpbs.quotas.edit', $lpb->id)
                ->with('success', 'Jatah per departemen berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors('Gagal menyimpan jatah: ' . $e->getMessage());
        }
    }
}
