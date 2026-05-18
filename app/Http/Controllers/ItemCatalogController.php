<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Item;
use Illuminate\Support\Facades\DB;

class ItemCatalogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Katalog Barang Read-Only untuk semua user (terutama Dept/USER).
     * Menampilkan HANYA: Kode, Nama Barang, Unit.
     * Data real-time dari tabel items (sinkron dengan perubahan admin).
     */
    public function index(Request $request)
    {
        $search  = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 50);
        if ($perPage < 1) $perPage = 50;

        $query = Item::where('current_status', 'ACTIVE')
            ->select('id', 'code', 'name', 'unit');

        // Search by code or name
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('code', 'ILIKE', $like)
                  ->orWhere('name', 'ILIKE', $like);
            });
        }

        $items = $query->orderBy('code', 'asc')
                       ->paginate($perPage)
                       ->appends($request->query());

        return view('items.catalog', array(
            'items'   => $items,
            'search'  => $search,
            'perPage' => $perPage,
        ));
    }
}
