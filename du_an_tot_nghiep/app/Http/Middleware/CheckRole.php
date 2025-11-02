<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, $roles): Response
    {
        if (!Auth::check()) {
            abort(403, 'Bạn chưa đăng nhập!');
        }

        $userRole = Auth::user()->vai_tro;

        $allowedRoles = explode('|', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            abort(403, 'Bạn không có quyền truy cập!');
        }

        return $next($request);
    }
}
