<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class NhanVienController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('manage.users');
    // }

    public function index()
    {
        $users = User::where('vai_tro', 'nhan_vien')->get();
        return view('admin.nhan-vien.index', compact('users'));
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
            'so_dien_thoai' => 'nullable|string|max:20',
            'phong_ban' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $validated['vai_tro'] = 'nhan_vien';
        $validated['password'] = bcrypt($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên mới đã được thêm!');
    }

    // FIX: Đổi $user → $nhan_vien
    public function show(User $nhan_vien)
    {
        if ($nhan_vien->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ xem chi tiết nhân viên!');
        }
        return view('admin.nhan-vien.show', compact('nhan_vien'));  // Đổi compact('user') → compact('nhan_vien')
    }

    // FIX: Đổi $user → $nhan_vien
    public function edit(User $nhan_vien)
    {
        if ($nhan_vien->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ chỉnh sửa nhân viên!');
        }
        return view('admin.nhan-vien.edit', compact('nhan_vien'));  // Đổi compact('user') → compact('nhan_vien')
    }

    // FIX: Đổi $user → $nhan_vien
    public function update(Request $request, User $nhan_vien)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $nhan_vien->id,
            'so_dien_thoai' => 'nullable|string|max:20',
            'phong_ban' => 'required|string|max:255',
        ]);

        $nhan_vien->update($validated);  // Đổi $user → $nhan_vien

        return redirect()->route('admin.nhan-vien.index')->with('success', 'Thông tin nhân viên đã được cập nhật!');
    }

    // FIX: Đổi $user → $nhan_vien
    public function toggleActive(User $nhan_vien, Request $request)
    {
        if ($nhan_vien->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ thay đổi trạng thái nhân viên!');
        }

        $nhan_vien->is_active = !$nhan_vien->is_active;  // Đổi $user → $nhan_vien
        $nhan_vien->save();  // Đổi $user → $nhan_vien

        if (!$nhan_vien->is_active && Auth::check() && Auth::id() === $nhan_vien->id) {  // Đổi $user → $nhan_vien
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa!');
        }

        $message = $nhan_vien->is_active ? 'Kích hoạt thành công!' : 'Vô hiệu hóa thành công!';  // Đổi $user → $nhan_vien
        return redirect()->route('admin.nhan-vien.index')->with('success', $message);
    }

    // FIX: Đổi $user → $nhan_vien
    public function destroy(User $nhan_vien)
    {
        if ($nhan_vien->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ xóa nhân viên!');
        }

        if ($nhan_vien->datPhongs()->exists()) {  // Đổi $user → $nhan_vien
            return redirect()->back()->with('error', 'Không thể xóa nhân viên vì đã có đơn đặt phòng!');
        }

        $nhan_vien->delete();  // Đổi $user → $nhan_vien
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên đã được xóa!');
    }
}