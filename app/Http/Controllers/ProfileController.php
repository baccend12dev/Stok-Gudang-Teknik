<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed', // butuh field new_password_confirmation di view
        ]);

        $user = Auth::user();

        // Cek password lama
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password lama tidak sesuai.');
        }

        // Update password baru
        $user->password = bcrypt($request->new_password);
        $user->save();

        return back()->with('success', 'Password berhasil diubah.');
    }
}