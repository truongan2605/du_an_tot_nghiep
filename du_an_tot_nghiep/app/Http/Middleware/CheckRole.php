<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response; // thêm dòng này

class CheckRole
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!Auth::check() || Auth::user()->vai_tro !== $role) {
            abort(403, 'Bạn không có quyền truy cập!');
        }

        return $next($request);
    }
}
