<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ManageUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra đăng nhập
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập!');
        }

        // Chỉ cho phép admin
        if (Auth::user()->vai_tro !== 'admin') {
            abort(403, 'Bạn không có quyền quản lý người dùng!');
        }

        // Không cho toggle chính mình
        if (
            $request->route()->getName() === 'admin.user.toggle'
            && Auth::id() === $request->route('user')->id
        ) {
            return redirect()->back()->with('error', 'Không thể thay đổi trạng thái tài khoản của chính bạn!');
        }

        return $next($request);
    }
}
