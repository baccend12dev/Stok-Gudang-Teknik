<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $has = \Schema::hasTable('categories');
        $q = $request->get('q', '');

        if ($has) {
            $query = Category::query();
            if ($q !== '') {
                $like = '%' . $q . '%';
                $op = (\DB::getDriverName() === 'pgsql') ? 'ILIKE' : 'like';
                $query->where(function ($w) use ($like, $op) {
                    $w->where('code', $op, $like)
                      ->orWhere('name', $op, $like);
                });
            }
            $rows = $query->paginate(20);
            $rows->appends(array('q' => $q));
        } else {
            $rows = new \Illuminate\Pagination\LengthAwarePaginator(array(), 0, 20);
        }

        return view('category.index', array(
            'has'  => $has,
            'q'    => $q,
            'rows' => $rows
        ));
    }
    
    public function create()
    {
        return view('category.create');
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|unique:categories',
            'name' => 'required',
        ]);
        
        try {
            Category::create([
                'code' => $request->code,
                'name' => $request->name
            ]);
            
            return redirect()->route('category.index')->with('success', 'Kategori berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
    
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('category.edit', compact('category'));
    }
    
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $this->validate($request, [
            'code' => 'required|unique:categories,code,'.$id,
            'name' => 'required',
        ]);
        
        try {
            $category->update([
                'code' => $request->code,
                'name' => $request->name
            ]);
            
            return redirect()->route('category.index')->with('success', 'Kategori berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
    
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        try {
            // Cek apakah masih ada item yang terkait
            $hasItems = $category->items()->exists();
                
            if ($hasItems) {
                return back()->with('error', 'Kategori tidak bisa dihapus karena masih terkait dengan beberapa item');
            }
            
            $category->delete();
            return redirect()->route('category.index')->with('success', 'Kategori berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}
