<?php
// app/Http/Middleware/SimpleAuthMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SimpleAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!session('authenticated')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
