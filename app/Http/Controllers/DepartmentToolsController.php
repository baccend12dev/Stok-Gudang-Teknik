<?php

namespace App\Http\Controllers;

class DepartmentToolsController extends Controller
{
    public function reset()
    {
        // Bersihkan filter pencarian sederhana dari session (kalau ada)
        session()->forget('dept_q');
        return redirect()->to('departments')->with('info', 'Filter department direset.');
    }
}
