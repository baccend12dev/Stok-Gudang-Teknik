<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            // Kalau udah login, cek role dulu sebelum lempar
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Proses submit login.
     */
    public function login(Request $request)
    {
        // Validasi sederhana
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $credentials = array(
            'email'    => $request->get('email'),
            'password' => $request->get('password'),
        );

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // === LOGIC REDIRECT PERFEKSIONIS ===
            return $this->redirectBasedOnRole($user);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(array('email' => 'Email atau password salah.'));
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return redirect()->route('login');
    }

    /**
     * Helper: Tentukan tujuan berdasarkan Role
     */
    protected function redirectBasedOnRole($user)
    {
        // 1. Kalau dia USER BIASA (Department), TENDANG KE REQUEST
        if ($user->role === 'USER') {
            session()->flash('success', "Halo <b>{$user->name}</b>. Silakan buat permintaan barang.");
            return redirect()->route('requests.index');
        }

        // 2. Kalau Admin/Super Admin, Silakan ke Dashboard
        $roleName = 'Staff';
        if ($user->role == 'ADMIN') $roleName = 'Administrator';
        if ($user->role == 'SUPER_ADMIN') $roleName = 'Super Administrator';
        
        session()->flash('success', "Selamat Datang, <b>{$user->name}</b>! Login sebagai <b>{$roleName}</b>.");
        return redirect()->route('dashboard');
    }
}