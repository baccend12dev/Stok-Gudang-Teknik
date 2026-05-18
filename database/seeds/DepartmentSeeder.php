<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        try {
            $ts = Carbon::now()->toDateTimeString();

            // 1) Nonaktifkan semua yang ada (divisi lama tetap ada sbg arsip)
            if (Schema::hasTable('departments')) {
                DB::table('departments')->update(['is_active' => false, 'updated_at' => $ts]);
            }

            // 2) Daftar DEPARTEMEN INDUK (sesuai permintaan Bu Ani)
            $parents = [
                ['code' => 'PROD',  'name' => 'PRODUKSI'],
                ['code' => 'LOG',   'name' => 'LOGISTIK'],
                ['code' => 'QC',    'name' => 'QC'],
                ['code' => 'RND',   'name' => 'R & D'],
                ['code' => 'BUS',   'name' => 'BUSDEV & REGISTRASI'],
                ['code' => 'MOA',   'name' => 'MOA'],
                ['code' => 'FA',    'name' => 'F & A'],
                ['code' => 'UMUM',  'name' => 'UMUM'],
                ['code' => 'QA',    'name' => 'QA'],
                ['code' => 'TEK',   'name' => 'TEKNIK'],
                ['code' => 'PROS',  'name' => 'PROSDEV'],
                ['code' => 'OTTO',  'name' => 'OTTO JKT'],
                ['code' => 'BUY',   'name' => 'PEMBELIAN'],
            ];

            foreach ($parents as $p) {
                // Cari by NAME (lebih stabil untuk display)
                $exists = DB::table('departments')->where('name', $p['name'])->first();

                if ($exists) {
                    DB::table('departments')->where('id', $exists->id)->update([
                        'code'       => substr($exists->code ?: $p['code'], 0, 50),
                        'is_active'  => true,
                        'updated_at' => $ts,
                    ]);
                } else {
                    // Pastikan code unik
                    $base = substr($p['code'], 0, 50);
                    $code = $base; $i = 1;
                    while (DB::table('departments')->where('code', $code)->exists()) {
                        $code = substr($base, 0, 45) . '_' . $i++;
                    }

                    DB::table('departments')->insert([
                        'group_id'   => null,                 // tidak perlu group untuk induk
                        'code'       => $code,
                        'name'       => $p['name'],
                        'is_active'  => true,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}