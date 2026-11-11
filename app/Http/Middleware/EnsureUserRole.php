<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->status !== 'AKTIF') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda berstatus nonaktif. Silakan hubungi administrator.',
            ]);
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Anda tidak memiliki hak akses ke halaman ini.'], 403);
            }

            // Redirect to appropriate dashboard based on user's actual role
            return match ($user->role) {
                'admin' => redirect()->route('admin.dashboard')->with('error', 'Akses ditolak untuk halaman tersebut.'),
                'kasir' => redirect()->route('kasir.dashboard')->with('error', 'Akses ditolak untuk halaman tersebut.'),
                default => redirect()->route('home')->with('error', 'Anda tidak memiliki hak akses ke halaman ini.'),
            };
        }

        return $next($request);
    }
}
