<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->provider) {
            return back()->withErrors([
                'email' => 'Tài khoản này đang sử dụng đăng nhập bằng ' . ucfirst($user->provider) . '. Vui lòng đăng nhập bằng ' . ucfirst($user->provider) . '.',
            ]);
        }

        if (! $user) {
            return back()->withErrors([
                'email' => 'Không tìm thấy tài khoản với email này.',
            ]);
        }

        // Tạo mã OTP 6 chữ số
        $code = (string) random_int(100000, 999999);
        $expiresInMinutes = 15;

        // Lưu (hoặc cập nhật) token vào bảng password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        // Gửi email mã OTP
        Mail::to($user->email)->send(new PasswordResetOtp($code, $user->name, $expiresInMinutes));

        return back()->with('status', 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư (bao gồm cả Spam).');
    }
}
