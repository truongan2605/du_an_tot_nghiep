<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('role:admin');  // Chỉ admin access
    // }

    public function index()
    {
        $users = User::where('vai_tro', 'khach_hang')->get();  // Chỉ list khach_hang
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
        return view('admin.user.create');  // View cho thêm khách hàng
    }

public function store(Request $request)
{
    // Cập nhật quy tắc validation để bao gồm 'confirmed'
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'so_dien_thoai' => 'nullable|string|max:20',
        'password' => 'required|string|min:8|confirmed', 
    ]);
    
    // Gán vai trò cố định và Hash mật khẩu
    $validated['vai_tro'] = 'khach_hang'; 
    $validated['password'] = bcrypt($validated['password']); 
    $validated['is_active'] = true; 

    // Tạo người dùng
    User::create($validated);
    
    return redirect()->route('admin.user.index')->with('success', 'Khách hàng mới đã được thêm!');
}

    
    public function toggleActive(User $user)
    {
        if ($user->vai_tro !== 'khach_hang') {
            abort(403, 'Chỉ thay đổi trạng thái khách hàng!');
        }
        $user->is_active = !$user->is_active;  // Toggle
        $user->save();
        $message = $user->is_active ? 'Kích hoạt thành công!' : 'Vô hiệu hóa thành công!';
        return redirect()->route('admin.user.index')->with('success', $message);
    }
}