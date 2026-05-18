<?php

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Item;

class ItemExcelSeeder extends Seeder
{
    public function run()
    {
        // Path ke file Excel yang sudah diupload ke folder storage
        $excelFiles = [
            storage_path('app/public/ATK P4 SEPT \'25.xlsx'),
            storage_path('app/public/3. P. UMUM OKT\'25 UP. TEH SHINTA.xlsx')
        ];

        foreach ($excelFiles as $file) {
            if (file_exists($file)) {
                $this->command->info("Processing file: " . basename($file));
                
                Excel::load($file, function($reader) {
                    $results = $reader->get();
                    
                    $this->command->info("Found " . count($results) . " rows in Excel");
                    
                    $imported = 0;
                    $updated = 0;
                    
                    foreach ($results as $row) {
                        // Skip baris kosong atau header
                        if (empty($row->kode) || empty($row->nama_barang) || 
                            strpos(strtolower($row->kode), 'kode') !== false) {
                            continue;
                        }
                        
                        // Bersihkan dan format data
                        $code = trim(preg_replace('/\s+/', ' ', $row->kode));
                        $name = trim(preg_replace('/\s+/', ' ', $row->nama_barang));
                        $unit = isset($row->sat) && !empty($row->sat) ? 
                                trim($row->sat) : 'BH';
                                
                        // Hitung stok awal dari data Excel
                        $initialStock = 0;
                        if (isset($row->stok_awal) && is_numeric($row->stok_awal)) {
                            $initialStock = (int)$row->stok_awal;
                        } elseif (isset($row->{'(18)'}) && is_numeric($row->{'(18)'})) {
                            // Format khusus dari file ATK
                            $initialStock = (int)$row->{'(18)'};
                        }

                        // Cek apakah item sudah ada
                        $item = Item::where('code', $code)->first();
                        
                        if (!$item) {
                            Item::create([
                                'code' => $code,
                                'name' => $name,
                                'unit' => $unit,
                                'buffer_min' => 20, // Default buffer
                                'current_stock' => $initialStock,
                                'current_status' => 'ACTIVE',
                                'notes' => 'Imported from ' . basename($file)
                            ]);
                            $imported++;
                        } else {
                            // Update stok jika item sudah ada
                            $item->update([
                                'current_stock' => $initialStock,
                                'notes' => 'Updated from ' . basename($file)
                            ]);
                            $updated++;
                        }
                    }
                    
                    $this->command->info("Imported: $imported items, Updated: $updated items");
                });
            } else {
                $this->command->warn("File not found: " . basename($file));
            }
        }
        
        $this->command->info("✅ SEMUA DATA EXCEL BERHASIL DIIMPORT!");
    }
}