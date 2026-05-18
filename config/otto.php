<?php

return [

    // ===== MASTER DEPARTEMEN (kode & nama) =====
    'departments_master' => [
        ['code' => '1',  'name' => 'MOA/MKT, BUSDEV'],
        ['code' => '3',  'name' => 'PLANT MANAGER, PROSDEV'],
        ['code' => '7',  'name' => 'CEPHA'],
        ['code' => '9',  'name' => 'LOGISTIK (GOJ), EXPEDISI, MAKLOON, NBL, RECEPTIONIST, RETUR'],
        ['code' => '10', 'name' => 'KOPERASI, UMUM, SDM, KLINIK, KANTIN, ISS'],
        ['code' => '17', 'name' => 'PBA / PGA / NBL / PENATU, BINATU, LAUNDRY'],
        ['code' => '19', 'name' => 'MIKRO, QC, IPC'],
        ['code' => '20', 'name' => 'VALIDASI, QA, IPC'],
        ['code' => '21', 'name' => 'LITBANG'],
        ['code' => '24', 'name' => 'TOT/MAKLOON'],

        // Tambahan dari sheet ATK P4 (opsional, jika dipakai)
        ['code' => '2',  'name' => 'PPIC'],
        ['code' => '4',  'name' => 'Q M'],
        ['code' => '5',  'name' => 'GBB'],
        ['code' => '6',  'name' => 'KMS'],
        ['code' => '8',  'name' => 'DISTRIBUSI'],
        ['code' => '11', 'name' => 'MIXING'],
        ['code' => '12', 'name' => 'TABLET'],
        ['code' => '13', 'name' => 'KAPSUL'],
        ['code' => '14', 'name' => 'COATING'],
        ['code' => '15', 'name' => 'SYRUP'],
        ['code' => '16', 'name' => 'STRIPPING'],
        ['code' => '18', 'name' => 'B LACTAM'],
        ['code' => '22', 'name' => 'R & D'],
        ['code' => '23', 'name' => 'INJEKSI'],
        ['code' => '25', 'name' => 'TEKNIK'],
        ['code' => '26', 'name' => 'KSO'],
        ['code' => '27', 'name' => 'ADM. PRODUKSI'],
        ['code' => '28', 'name' => 'CLAIM RETUR'],
        ['code' => '29', 'name' => 'GQU'],
        ['code' => '30', 'name' => 'UMUM'],
    ],

    // ===== Alert stok default (fallback kalau item tidak punya buffer_min) =====
    'stock' => [
        'alert_at' => 25,
    ],

    // ===== Aturan kategori barang untuk rekap ALL =====
    'categories' => [
        ['code' => 'ATK', 'name' => 'ATK / LAINNYA', 'order' => 10,
            'prefixes' => array('A0','B','K0','UM'),
            'keywords' => array('amplop','baterai','bolpoin','stapler','map','kertas','binder','spidol','gunting')
        ],
        ['code' => 'AK',  'name' => 'ALAS KAKI', 'order' => 20,
            'prefixes' => array('AK'),
            'keywords' => array('sepatu','boot','sandal')
        ],
        ['code' => 'PK',  'name' => 'PAKAIAN', 'order' => 30,
            'prefixes' => array('PK'),
            'keywords' => array('seragam','wear pack','masker','jas lab','hair cap','sarung tangan')
        ],
        ['code' => 'SB',  'name' => 'SABUN', 'order' => 40,
            'prefixes' => array('SB'),
            'keywords' => array('sabun','deterg','sunlight','rinso','wipol','hand soap','cleaner')
        ],
        ['code' => 'KB',  'name' => 'ALAT KEBERSIHAN', 'order' => 50,
            'prefixes' => array('AB'),
            'keywords' => array('sapu','pel','kanebo','ember','pengki','sikat','lap','tong','tempat sampah')
        ],
        ['code' => 'UMM', 'name' => 'UMUM', 'order' => 60,
            'prefixes' => array('UM'),
            'keywords' => array('kopi','gula','tissue','keset','kamper','baygon')
        ],
    ],
];
