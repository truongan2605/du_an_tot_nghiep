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

    public function edit(User $user)
    {
        if ($user->vai_tro !== 'khach_hang') {
            abort(403, 'Chỉ chỉnh sửa khách hàng!');
        }
        return view('admin.user.edit', compact('user'));
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
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'vai_tro' => 'required|in:khach_hang,nhan_vien',  
            'phong_ban' => 'required_if:vai_tro,nhan_vien|string|max:255',   
        ]);

       
        $data = [
            'vai_tro' => $validated['vai_tro'],
            'phong_ban' => $validated['vai_tro'] === 'nhan_vien' ? $validated['phong_ban'] : null,
        ];

        $user->update($data);

        
        if ($validated['vai_tro'] === 'nhan_vien' && Auth::check() && Auth::id() === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('success', 'Vai trò đã được thay đổi. Vui lòng đăng nhập lại!');
        }

        return redirect()->route('admin.user.index')->with('success', 'Vai trò đã được cập nhật!');
    }
   
   public function toggleActive(User $user): RedirectResponse 
{
   
    // 1. Kiểm tra vai trò
    if ($user->vai_tro !== 'khach_hang') {
        abort(403, 'Chỉ thay đổi trạng thái khách hàng!');
    }

    // 2. Đảo ngược trạng thái is_disabled (vô hiệu hóa bởi admin)
    $user->is_disabled = !$user->is_disabled;
    $user->save();

    // 3. LOGIC BẢO MẬT: Logout người dùng nếu bị vô hiệu hóa
    if ($user->is_disabled && Auth::check() && Auth::id() === $user->id) { 
        Auth::logout();
        
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị vô hiệu hóa!');
    }

    // 4. Trả về thông báo thành công
    $message = $user->is_disabled ? 'Vô hiệu hóa thành công!' : 'Kích hoạt thành công!';
    return redirect()->route('admin.user.index')->with('success', $message);
}
}