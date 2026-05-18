<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ItemToolsController extends Controller
{
    public function classify()
    {
        // Placeholder supaya tombol/link di view tidak error
        return redirect()->to('items')->with('info', 'Fitur classify belum diaktifkan.');
    }
}
