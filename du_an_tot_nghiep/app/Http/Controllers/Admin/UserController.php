<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Http\RedirectResponse;


class UserController extends Controller
{
   

    public function index()
    {
        $users = User::where('vai_tro', 'khach_hang')->get();
        return view('admin.user.index', compact('users'));
    }

    public function show(User $user)
    {
        if ($user->vai_tro !== 'khach_hang') {
            abort(403, 'Chỉ xem chi tiết khách hàng!');
        }
        return view('admin.user.show', compact('user'));
    }
    
    public function create()
    {
        return view('admin.user.create');
    }

    public function store(Request $request)
    {
      
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'so_dien_thoai' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed', 
        ]);
        
       
        $validated['vai_tro'] = 'khach_hang'; 
        $validated['password'] = bcrypt($validated['password']); 
        $validated['is_active'] = true; 

        // Tạo người dùng
        User::create($validated);
        
        return redirect()->route('admin.user.index')->with('success', 'Khách hàng mới đã được thêm!');
    }
    
   
   public function toggleActive(User $user): RedirectResponse 
{
   
    // 1. Kiểm tra vai trò
    if ($user->vai_tro !== 'khach_hang') {
        abort(403, 'Chỉ thay đổi trạng thái khách hàng!');
    }

    // 2. Đảo ngược trạng thái
    $user->is_active = !$user->is_active;
    $user->save();

    // 3. LOGIC BẢO MẬT: Logout người dùng nếu bị vô hiệu hóa
    if (!$user->is_active && Auth::check() && Auth::id() === $user->id) { 
        Auth::logout();
        
        // SỬA LỖI TẠI ĐÂY: Dùng helper function request()
        request()->session()->invalidate(); // Hủy session
        request()->session()->regenerateToken(); // Tạo token mới
        
        return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa!');
    }

    // 4. Trả về thông báo thành công
    $message = $user->is_active ? 'Kích hoạt thành công!' : 'Vô hiệu hóa thành công!';
    return redirect()->route('admin.user.index')->with('success', $message);
}
}