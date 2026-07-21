<?php

namespace App\Http\Controllers;

use App\User;
use App\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Proteksi: Hanya Super Admin
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'SUPER_ADMIN') {
                abort(403, 'Akses Ditolak. Halaman ini khusus Super Admin.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $query = User::with('department')->orderBy('id', 'asc'); // Order ID asc biar rapi sesuai DB lama

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $departments = Department::orderBy('name', 'asc')->get();
        return view('users.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'role'          => 'required|in:SUPER_ADMIN,ADMIN,USER,APPROVAL',
            'department_id' => 'nullable|exists:departments,id', // Nullable jika Super Admin
            'password'      => 'required|min:6',
        ]);

        $user = new User();
        $user->name            = $request->name;
        $user->email           = $request->email;
        $user->role            = $request->role;
        $user->department_id   = $request->department_id;
        $user->inventory_scope = $request->inventory_scope ?: 'ALL';
        $user->password        = bcrypt($request->password);
        $user->save();

        return redirect()->route('users.index')->with('success', 'User <strong>'.$user->name.'</strong> berhasil dibuat.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $departments = Department::orderBy('name', 'asc')->get();
        return view('users.edit', compact('user', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,'.$id,
            'role'          => 'required|in:SUPER_ADMIN,ADMIN,USER,APPROVAL',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->name            = $request->name;
        $user->email           = $request->email;
        $user->role            = $request->role;
        $user->department_id   = $request->department_id;
        $user->inventory_scope = $request->inventory_scope ?: 'ALL';
        $user->save();

        return redirect()->route('users.index')->with('success', 'Data user <strong>'.$user->name.'</strong> berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (Auth::id() == $id) return back()->with('error', 'Tidak bisa menghapus akun sendiri!');
        
        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User <strong>'.$name.'</strong> telah dihapus.');
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $defaultPass = '123456'; 
        $user->password = bcrypt($defaultPass);
        $user->save();

        return back()->with('success', 'Password <strong>'.$user->name.'</strong> di-reset jadi: <strong>'.$defaultPass.'</strong>');
    }
}