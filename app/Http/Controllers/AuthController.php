<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required','string'],
            'password' => ['required','string'],
        ]);

        // Pastikan hanya user aktif yang bisa login
        $credentials = [
            'username'  => $data['username'],
            'password'  => $data['password'],
            'is_active' => 1,
        ];

        if (Auth::attempt($credentials, false)) {
            $request->session()->regenerate();
            return $this->redirectByRole();
        }

        return back()->withErrors(['username' => 'Username atau password salah.'])
                     ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectByRole()
    {
        $roleId = (int) (auth()->user()->role_id ?? 0);

        return match ($roleId) {
            1       => redirect()->route('owner.home'), // OWNER
            2       => redirect()->route('kc.home'),    // KC
            3       => redirect()->route('kasir.home'), // KASIR
            default => function () {
                // Role tidak dikenali → cegah loop
                auth()->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
                return redirect()->route('login')
                    ->withErrors(['username' => 'Role pengguna belum diset.']);
            },
        };
    }
}
