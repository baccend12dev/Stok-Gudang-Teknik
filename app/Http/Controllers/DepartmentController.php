<?php

namespace App\Http\Controllers;

use App\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::all();
        return view('departments.index', compact('departments'));
    }
    
    public function create()
    {
        return view('departments.create');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:departments',
            'name' => 'required',
        ]);
        
        try {
            Department::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description
            ]);
            
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
    
    public function edit($id)
    {
        $department = Department::findOrFail($id);
        return view('departments.edit', compact('department'));
    }
    
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|unique:departments,code,'.$id,
            'name' => 'required',
        ]);
        
        try {
            $department->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description
            ]);
            
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
    
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        
        try {
            // Cek apakah masih ada item yang terkait
            $hasItems = DB::table('item_department_buffers')
                ->where('department_id', $id)
                ->exists();
                
            if ($hasItems) {
                return back()->with('error', 'Departemen tidak bisa dihapus karena masih terkait dengan beberapa item');
            }
            
            $department->delete();
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}