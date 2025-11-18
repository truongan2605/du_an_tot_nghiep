<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (!$user->is_active) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Account not active.'], 403);
                }

                return redirect()->route('account.settings')->with('warning', 'Your account is not activated yet. Please check your email to verify or contact support.');
            }
        }

        return $next($request);
    }
}