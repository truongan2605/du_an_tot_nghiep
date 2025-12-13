<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class PasswordOtpResetController extends Controller
{
    public function create()
    {
        return view('auth.reset-password-otp');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record) {
            return back()->withErrors(['email' => 'Mã xác thực không hợp lệ hoặc đã hết hạn.']);
        }

        // Kiểm tra hết hạn (30 phút)
        $createdAt = $record->created_at ? Carbon::parse($record->created_at) : null;
        if (! $createdAt || $createdAt->lt(now()->subMinutes(30))) {
            return back()->withErrors(['code' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu mã mới.']);
        }

        // Xác thực mã OTP
        if (! Hash::check($request->code, $record->token)) {
            return back()->withErrors(['code' => 'Mã xác thực không đúng.'])->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Không tìm thấy tài khoản với email này.']);
        }

        // Cập nhật mật khẩu
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Xóa token sau khi dùng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('status', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập với mật khẩu mới.');
    }
}


