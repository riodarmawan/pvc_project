<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRoleId  = (int) (Auth::user()->role_id ?? 0);
        $allowedIds  = array_map('intval', $roles);

        if (!in_array($userRoleId, $allowedIds, true)) {
            // Cegah redirect loop: logout jika role tidak cocok
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')
                ->withErrors(['username' => 'Akses ditolak: role tidak sesuai.']);
        }

        return $next($request);
    }
}
