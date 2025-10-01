<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');

        try {
            $gUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Unable to authenticate with Google.');
        }

        $gAvatar = $gUser->getAvatar();
        if ($gAvatar && str_contains($gAvatar, 'lh3.googleusercontent.com')) {
            $gAvatar = null;
        }

        $user = User::where('provider', 'google')
                    ->where('provider_id', $gUser->getId())
                    ->first();

        if (! $user) {
            $user = User::where('email', $gUser->getEmail())->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $gUser->getName() ?? $gUser->getNickname() ?? 'Google User',
                'email' => $gUser->getEmail(),
                'email_verified_at' => Carbon::now(),
                'provider' => 'google',
                'provider_id' => $gUser->getId(),
                'avatar' => $gAvatar, 
                'password' => null,
            ]);
        } else {
            $user->update([
                'provider' => $user->provider ?: 'google',
                'provider_id' => $user->provider_id ?: $gUser->getId(),
                'avatar' => $user->avatar ?: $gAvatar,
                'email_verified_at' => $user->email_verified_at ?: Carbon::now(),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('home'));
    }
}
