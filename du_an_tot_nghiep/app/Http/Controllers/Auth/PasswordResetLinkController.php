<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;

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
            return back()->withErrors(['email' => 'This account is logged in with ' . ucfirst($user->provider) . '. Please log in with ' . ucfirst($user->provider) . '.']);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withErrors(['email' => __($status)]);
    }
}
