<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Controller ini menangani otentikasi user. Semua logic login ada di trait
    | AuthenticatesUsers, tapi kita override dikit biar makin perfect.
    |
    */

    use AuthenticatesUsers;

    /**
     * Setelah login sukses, arahkan ke sini.
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Custom Logic setelah User berhasil Login.
     * Kita kasih Flash Message biar user merasa disapa.
     */
    protected function authenticated(Request $request, $user)
    {
        // 1. Logic Redirection Berdasarkan Role
        if ($user->role === 'USER') {
             // Kalau user biasa, langsung lempar ke Request Department
             // Dan kasih flash message
             $request->session()->flash('success', "Halo <b>{$user->name}</b>. Silakan buat permintaan barang.");
             return redirect()->route('requests.index');
        }

        // 2. Logic Admin / Super Admin
        $roleName = 'Staff';
        if ($user->role == 'ADMIN') $roleName = 'Administrator';
        if ($user->role == 'SUPER_ADMIN') $roleName = 'Super Administrator';
        
        $request->session()->flash('success', "Selamat Datang, <b>{$user->name}</b>! Anda masuk sebagai <b>{$roleName}</b>.");
        return redirect()->intended(route('dashboard'));
    }
}