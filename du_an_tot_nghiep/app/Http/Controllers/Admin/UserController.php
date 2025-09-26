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