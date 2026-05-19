## Project / Sistem

| Project / Sistem | Tujuan | Manfaat |
|---|---|---|
| **System Monitoring Stock Gudang Teknik** | Memonitoring stok barang-barang teknik di gudang teknik | Mengetahui barang masuk/keluar, sisa stok, fast/slow moving, dan batas limit stok |

## Proyek 2: System Monitoring Stock Gudang Teknik

```mermaid
flowchart TD
    Start([MULAI]) --> Step1["1. Barang datang -> Admin Gudang Teknik input jumlah barang masuk ke sistem"]
    Step1 --> Step2["2. Ada permintaan barang -> Admin Gudang input jumlah barang yang diminta (keluar)"]
    Step2 --> Step3["3. Sistem otomatis menghitung sisa stok; alert jika stok menyentuh batas limit"]
    Step3 --> Step4["4. Admin Gudang & Supervisor Teknik menentukan: limit stok, fast moving, slow moving"]
    Step4 --> Step5["5. Jika stok <= limit -> Admin Gudang melakukan pemesanan barang"]
    Step5 --> Step6["6. Sistem menghasilkan laporan stok, tren barang, dan analisa fast/slow moving"]
    Step6 --> End([SELESAI])
```
