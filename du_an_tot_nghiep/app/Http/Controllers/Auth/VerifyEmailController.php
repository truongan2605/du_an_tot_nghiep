<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use App\Providers\RouteServiceProvider;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, $id, $hash): View
    {
        // Tìm user theo ID
        $user = User::findOrFail($id);

        // Kiểm tra email đã được verify chưa
        if ($user->email_verified_at) {
            return view('auth.email-verified', [
                'alreadyVerified' => true,
                'userName' => $user->name
            ]);
        }

        // Verify hash từ URL (Laravel dùng sha1 của email)
        $expectedHash = sha1($user->email);
        
        if (! hash_equals((string) $hash, $expectedHash)) {
            return view('auth.email-verified', [
                'error' => true,
                'message' => 'Link xác nhận không hợp lệ hoặc đã hết hạn.'
            ]);
        }

        // Verify signature từ signed URL
        if (! URL::hasValidSignature($request)) {
            return view('auth.email-verified', [
                'error' => true,
                'message' => 'Link xác nhận không hợp lệ hoặc đã hết hạn.'
            ]);
        }

        // Đánh dấu email đã được verify
        $user->email_verified_at = now();
        $user->is_active = true;
        $user->save();

        return view('auth.email-verified', [
            'success' => true,
            'userName' => $user->name
        ]);
    }
}
