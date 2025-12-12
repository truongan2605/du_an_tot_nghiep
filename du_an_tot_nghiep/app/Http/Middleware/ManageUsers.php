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
        // Kiểm tra đăng nhập và quyền admin
        if (!Auth::check() || Auth::user()->vai_tro !== 'admin') {
            abort(403, 'Bạn không có quyền quản lý!');
        }

        
        $user = $request->route('user');

        if ($user && in_array($request->route()->getName(), ['admin.nhan-vien.toggle', 'admin.nhan-vien.destroy'])) {
            
            // Không cho thao tác chính mình
            if ($user->id === Auth::id()) {
                return redirect()->back()->with('error', 'Không thể thay đổi/xóa chính bạn!');
            }

            // Không cho thao tác với admin khác
            if ($user->vai_tro === 'admin') {
                return redirect()->back()->with('error', 'Không thể thay đổi/xóa admin khác!');
            }

            // Đảm bảo chỉ áp dụng cho nhân viên
            if ($user->vai_tro !== 'nhan_vien') {
                return redirect()->back()->with('error', 'Đây không phải nhân viên hợp lệ!');
            }
        }

        return $next($request);
    }
}