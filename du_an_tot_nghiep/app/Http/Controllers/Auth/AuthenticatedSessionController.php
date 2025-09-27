<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request)
    {
        $previous = url()->previous();
        $loginUrl = route('login');
        $registerUrl = route('register');
        $forgotUrl = route('password.request');

        if (! $request->session()->has('url.intended')) {
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

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && $user->vai_tro === 'admin') {
            return redirect()->route('tien-nghi.index');
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
}
