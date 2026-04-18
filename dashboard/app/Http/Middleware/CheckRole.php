<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        $role = (string) ($user->role ?? '');
        if (!in_array($role, $roles, true)) {
            abort(403, 'Kamu tidak punya izin untuk aksi ini.');
        }

        return $next($request);
    }
}
