<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NhanVienController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('manage.users');  // Bảo vệ quyền
    // }

    public function index()
    {
        $nhanViens = User::where('vai_tro', 'nhan_vien')->get();  // Chỉ nhan_vien
        return view('admin.nhan-vien.index', compact('nhanViens'));
    }

    public function create()
    {
        return view('admin.nhan-vien.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'so_dien_thoai' => 'nullable|string',
            'phong_ban' => 'required|string|max:255',  // Bắt buộc cho nhan_vien
        ]);
        $validated['vai_tro'] = 'nhan_vien';  // Fixed vai_tro
        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên mới đã được tạo!');
    }

    public function show(User $user)
    {
        return view('admin.nhan-vien.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.nhan-vien.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'so_dien_thoai' => 'nullable|string',
            'phong_ban' => 'required|string|max:255',
        ]);
        $user->update($validated);
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên đã được cập nhật!');
    }

    public function toggleActive(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
        $message = $user->is_active ? 'Kích hoạt thành công!' : 'Vô hiệu hóa thành công!';
        return redirect()->route('admin.nhan-vien.index')->with('success', $message);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên đã được xóa!');
    }
}