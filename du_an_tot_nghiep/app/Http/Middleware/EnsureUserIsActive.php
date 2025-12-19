<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Chỉ chặn nếu user bị admin vô hiệu hóa (is_disabled = true)
            // User mới đăng ký (is_active = false) vẫn được truy cập
            if ($user->is_disabled) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Tài khoản đã bị vô hiệu hóa.'], 403);
                }

                return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.');
            }
        }

        return $next($request);
    }
}