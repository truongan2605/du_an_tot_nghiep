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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn chưa đăng nhập!'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user(); 
        $userRole = $user->vai_tro;
        $allowedRoles = explode('|', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            // Log với $user đã assign (hoặc Auth::user()->id nếu lazy)
            \Log::warning("Access denied: User {$user->id} ({$userRole}) tried {$request->path()}");

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền truy cập!'], 403);
            }

            // UX fix: Redirect home với flash
            return redirect()->route('home')->with('error', 'Bạn không có quyền truy cập khu vực quản trị. Vui lòng liên hệ admin nếu cần hỗ trợ.');
        }

        return $next($request);
    }
}