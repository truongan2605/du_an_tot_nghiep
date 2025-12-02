<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class VoucherController extends Controller
{
    /** Hiển thị danh sách voucher */
    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('filter')) {
            if ($request->filter === 'valid') {
                $query->whereDate('end_date', '>=', Carbon::today());
            } elseif ($request->filter === 'expired') {
                $query->whereDate('end_date', '<', Carbon::today());
            }
        }

        // SECURITY FIX: Only show vouchers that are:
        // 1. Public (active=1) AND has NO user assignments (general vouchers), OR
        // 2. Assigned to current logged-in user (private vouchers)
        if (Auth::check()) {
            $userId = Auth::id();
            $query->where(function($q) use ($userId) {
                // Vouchers assigned to this user
                $q->whereHas('users', function($q2) use ($userId) {
                    $q2->where('users.id', $userId);
                })
                // OR public vouchers with NO assignments (general public)
                ->orWhere(function($q3) {
                    $q3->where('active', 1)
                       ->doesntHave('users');  // Not assigned to anyone
                });
            });
        } else {
            // Guests: only see public vouchers with no assignments
            $query->where('active', 1)->doesntHave('users');
        }
        
        $query->orderByDesc('start_date');
        $vouchers = $query->get();

        // Lấy id voucher đã nhận
        $claimedIds = [];
        if (Auth::check()) {
            $claimedIds = Auth::user()
                ->vouchers()
                ->pluck('voucher.id') // vì bảng là 'voucher'
                ->toArray();
        }

        return view('client.voucher.index', compact('vouchers', 'claimedIds'));
    }

    /** Nhận voucher */
    public function claim(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để nhận voucher.'], 401);
        }

        $user = Auth::user();
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher không tồn tại.'], 404);
        }

        if (!$voucher->active) {
            return response()->json(['success' => false, 'message' => 'Voucher hiện không hoạt động.'], 400);
        }

        $today = Carbon::today();
        if (!empty($voucher->start_date) && $voucher->start_date > $today) {
            return response()->json(['success' => false, 'message' => 'Voucher chưa có hiệu lực.'], 400);
        }
        if (!empty($voucher->end_date) && $voucher->end_date < $today) {
            return response()->json(['success' => false, 'message' => 'Voucher đã hết hạn.'], 400);
        }

        $alreadyClaimed = $user->vouchers()->where('voucher.id', $voucher->id)->exists();
        if ($alreadyClaimed) {
            return response()->json(['success' => false, 'message' => 'Bạn đã nhận voucher này trước đó.'], 400);
        }

        try {
            $user->vouchers()->attach($voucher->id, ['claimed_at' => now()]);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Không thể nhận voucher (đã tồn tại).'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nhận voucher thành công!',
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'claimed_at' => now()->toDateTimeString(),
        ]);
    }

    /** Trang My Vouchers */
    public function myVouchers(Request $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $query = Auth::user()->vouchers()->orderByDesc('user_voucher.claimed_at');

    // Tìm kiếm
    if ($request->filled('search')) {
        $query->where(function($q) use ($request) {
            $q->where('code', 'like', '%'.$request->search.'%')
              ->orWhere('note', 'like', '%'.$request->search.'%');
        });
    }

    // Lọc theo hiệu lực
    if ($request->filled('filter')) {
        if ($request->filter === 'valid') {
            $query->whereDate('end_date', '>=', now());
        } elseif ($request->filter === 'expired') {
            $query->whereDate('end_date', '<', now());
        }
    }

    $vouchers = $query->get();

    return view('account.my', compact('vouchers'));
}

}
