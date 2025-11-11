<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Events\StaffCreated;
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

        $staff = User::create($validated);

        // Dispatch event for automatic notifications
        event(new StaffCreated($staff));
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên mới đã được thêm!');
    }

    public function show(User $user)
    {
        if ($user->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ xem chi tiết nhân viên!');
        }
        return view('admin.nhan-vien.show', compact('user'));
    }

    public function edit(User $user)
    {
        if ($user->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ chỉnh sửa nhân viên!');
        }
        return view('admin.nhan-vien.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'so_dien_thoai' => 'nullable|string|max:20',
            'phong_ban' => 'required|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('admin.nhan-vien.index')->with('success', 'Thông tin nhân viên đã được cập nhật!');
    }

    public function toggleActive(User $user, Request $request)
    {
        if ($user->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ thay đổi trạng thái nhân viên!');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active && Auth::check() && Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa!');
        }

        $message = $user->is_active ? 'Kích hoạt thành công!' : 'Vô hiệu hóa thành công!';
        return redirect()->route('admin.nhan-vien.index')->with('success', $message);
    }

    public function destroy(User $user)
    {
        if ($user->vai_tro !== 'nhan_vien') {
            abort(403, 'Chỉ xóa nhân viên!');
        }

        if ($user->datPhongs()->exists()) {
            return redirect()->back()->with('error', 'Không thể xóa nhân viên vì đã có đơn đặt phòng!');
        }

        $user->delete();
        return redirect()->route('admin.nhan-vien.index')->with('success', 'Nhân viên đã được xóa!');
    }
}