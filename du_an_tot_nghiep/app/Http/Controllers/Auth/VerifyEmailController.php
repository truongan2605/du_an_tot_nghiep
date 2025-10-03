<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;

class VerifyEmailController extends Controller
{

    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            // Phát event
            event(new Verified($request->user()));

            // Kích hoạt account (is_active = true)
            // Lưu ý: nếu bạn muốn admin vẫn phải active thủ công thì bỏ dòng này
            $request->user()->update(['is_active' => true]);
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
