<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            return redirect()->route('login');
        }

  
        if ($user->vai_tro !== 'admin') {
            if (! $request->isMethod('GET')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Bạn chỉ có quyền xem, không được thực hiện hành động này.'], 403);
                }
                return redirect()->back()->with('error', 'Bạn chỉ có quyền xem, không được thực hiện hành động này.');
            }
          
            session(['read_only_mode' => true]);
        }

        return $next($request);
    }
}