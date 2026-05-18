<?php

namespace App\Http\Controllers;

use App\Department;
use App\Item;
use App\ItemDepartmentBuffer;
use App\LpbHeader;
use App\LpbDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function importExcel()
    {
        // Pastikan department sudah ada
        $this->setupDepartments();
        
        // Import data dari CSV files
        $this->importFromCsvFiles();
        
        return redirect()->route('dashboard')->with('success', 'Data berhasil diimpor dari CSV');
    }

    private function setupDepartments()
    {
        $departments = [
            ['code' => '1', 'name' => 'F & A'],
            ['code' => '2', 'name' => 'PPIC'],
            ['code' => '3', 'name' => 'Q M'],
            ['code' => '4', 'name' => 'GBB'],
            ['code' => '5', 'name' => 'KMS'],
            ['code' => '6', 'name' => 'DISTRIBUSI'],
            ['code' => '7', 'name' => 'ADM. PRODUKSI'],
            ['code' => '8', 'name' => 'CLAIM RETUR'],
            ['code' => '9', 'name' => 'GOJ'],
            ['code' => '10', 'name' => 'UMUM'],
            ['code' => '11', 'name' => 'MIXING'],
            ['code' => '12', 'name' => 'TABLET'],
            ['code' => '13', 'name' => 'KAPSUL'],
            ['code' => '14', 'name' => 'COATING'],
            ['code' => '15', 'name' => 'SYRUP'],
            ['code' => '16', 'name' => 'STRIPING'],
            ['code' => '17', 'name' => 'PACKING'],
            ['code' => '18', 'name' => 'B.LACTAM'],
            ['code' => '19', 'name' => 'QC'],
            ['code' => '20', 'name' => 'QA'],
            ['code' => '21', 'name' => 'R & D'],
            ['code' => '22', 'name' => 'INJEKSI'],
            ['code' => '23', 'name' => 'TEKNIK']
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name']]
            );
        }
    }

    private function importFromCsvFiles()
    {
        $csvFiles = [
            public_path('excel/NAMA_KODEBRG.csv'),
            public_path('excel/NAMA_KODEBRG_ATK.csv')
        ];

        foreach ($csvFiles as $file) {
            if (!file_exists($file)) continue;
            
            $this->processCsvFile($file);
        }
    }

    private function processCsvFile($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) return;

        // Lewati header jika ada
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            // Ambil data dari CSV
            // Format CSV: KODE_BRG, NAMA_BARANG, SAT
            if (count($row) < 3) continue;
            
            $itemCode = trim($row[0]);
            $itemName = trim($row[1]);
            $itemUnit = !empty(trim($row[2])) ? trim($row[2]) : 'BH';
            
            // Skip jika data tidak valid
            if (empty($itemCode) || empty($itemName)) continue;
            
            // Simpan item ke database
            $item = Item::updateOrCreate(
                ['code' => $itemCode],
                [
                    'name' => $itemName,
                    'unit' => $itemUnit,
                    'buffer_min' => 5,
                    'current_status' => 'ACTIVE',
                    'notes' => 'Imported from CSV'
                ]
            );
            
            // Set default buffer untuk semua department
            $this->setDefaultDepartmentBuffers($item);
        }
        
        fclose($handle);
    }

    private function setDefaultDepartmentBuffers($item)
    {
        $departments = Department::all();
        foreach ($departments as $department) {
            ItemDepartmentBuffer::updateOrCreate(
                ['item_id' => $item->id, 'department_id' => $department->id],
                ['buffer_min' => 5]
            );
        }
    }
}