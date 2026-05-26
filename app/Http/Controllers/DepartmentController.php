<?php

namespace App\Http\Controllers;

use App\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $has = \Schema::hasTable('departments');
        $q = $request->get('q', '');

        if ($has) {
            $query = Department::with('divisions');
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

        return view('departments.index', array(
            'has'  => $has,
            'q'    => $q,
            'rows' => $rows
        ));
    }
    
    public function create()
    {
        return view('departments.create');
    }
    
    public function store(Request $request)
    {     
        $this->validate($request, [
            'code' => 'required|unique:departments,code',
            'name' => 'required|string|max:255',
            'divisions.*.code' => 'required|string|max:10',
            'divisions.*.name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $department = Department::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description
            ]);

            $divisions = (array) $request->get('divisions', []);
            foreach ($divisions as $div) {
                if (empty($div['code']) || empty($div['name'])) {
                    continue;
                }
                \App\Division::create([
                    'department_id' => $department->id,
                    'code' => $div['code'],
                    'name' => $div['name'],
                    'description' => isset($div['description']) ? $div['description'] : null,
                ]);
            }

            DB::commit();
            return redirect()->route('departments.index')->with('success', 'Departemen dan divisi berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function edit($id)
    {
        $department = Department::with('divisions')->findOrFail($id);
        return view('departments.edit', compact('department'));
    }
    
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
    
        $this->validate($request, [
            'code' => 'required|unique:departments,code,' . $id,
            'name' => 'required|string|max:255',
            'divisions.*.code' => 'required|string|max:10',
            'divisions.*.name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $department->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description
            ]);

            $submittedDivs = (array) $request->get('divisions', []);
            $submittedIds = [];

            foreach ($submittedDivs as $div) {
                if (empty($div['code']) || empty($div['name'])) {
                    continue;
                }
                
                if (isset($div['id']) && !empty($div['id'])) {
                    $existingDiv = \App\Division::where('department_id', $department->id)->find($div['id']);
                    if ($existingDiv) {
                        $existingDiv->update([
                            'code' => $div['code'],
                            'name' => $div['name'],
                            'description' => isset($div['description']) ? $div['description'] : null,
                        ]);
                        $submittedIds[] = $existingDiv->id;
                    }
                } else {
                    $newDiv = \App\Division::create([
                        'department_id' => $department->id,
                        'code' => $div['code'],
                        'name' => $div['name'],
                        'description' => isset($div['description']) ? $div['description'] : null,
                    ]);
                    $submittedIds[] = $newDiv->id;
                }
            }

            // Hapus divisi yang tidak disubmit
            \App\Division::where('department_id', $department->id)
                ->whereNotIn('id', $submittedIds)
                ->delete();

            DB::commit();
            return redirect()->route('departments.index')->with('success', 'Departemen dan divisi berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Cek apakah masih ada item yang terkait
            $hasItems = DB::table('item_department_buffers')
                ->where('department_id', $id)
                ->exists();
                
            if ($hasItems) {
                return back()->with('error', 'Departemen tidak bisa dihapus karena masih terkait dengan beberapa item');
            }
            
            // Hapus semua divisi di bawah departemen ini terlebih dahulu
            \App\Division::where('department_id', $id)->delete();
            
            $department->delete();
            
            DB::commit();
            return redirect()->route('departments.index')->with('success', 'Departemen dan divisinya berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}