<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request)
    {
        $previous = url()->previous();
        $loginUrl = route('login');
        $registerUrl = route('register');
        $forgotUrl = route('password.request');

        if (!$request->session()->has('url.intended')) {
            if ($previous && $previous !== $loginUrl && $previous !== $registerUrl && $previous !== $forgotUrl) {
                $request->session()->put('url.intended', $previous);
            }
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);


        if (!$this->attemptLogin($request)) {
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không chính xác hoặc tài khoản đã bị vô hiệu hóa.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && $user->vai_tro === 'admin') {
            return redirect()->route('admin.tien-nghi.index');
        }

        if ($user && $user->vai_tro === 'nhan_vien') {
            return redirect()->route('staff.index');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function attemptLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        return Auth::attempt($credentials, $request->filled('remember'));
    }


    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => 'Tài khoản của bạn đã bị vô hiệu hóa!',
            ])->withInput($request->only('email', 'remember'));
        }
        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->withInput($request->only('email', 'remember'));
    }
}
