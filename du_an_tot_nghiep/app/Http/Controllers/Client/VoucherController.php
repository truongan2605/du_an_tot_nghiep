<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\Schema;

class VoucherController extends Controller
{
    /** ====== HIỂN THỊ DANH SÁCH VOUCHER ====== */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Tìm kiếm theo mã
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Lọc theo hiệu lực thời gian
        if ($request->filled('filter')) {
            if ($request->filter === 'valid') {
                $query->whereDate('end_date', '>=', Carbon::today());
            } elseif ($request->filter === 'expired') {
                $query->whereDate('end_date', '<', Carbon::today());
            }
        }

        // Chỉ lấy voucher đang active
        $query->where('active', 1);

        // Đếm số user đã nhận từng voucher
        $query->withCount('users as claims_count');

        // Sắp xếp theo ngày bắt đầu giảm dần
        $query->orderByDesc('start_date');

        $vouchers = $query->get();

        // Lấy danh sách voucher mà user đã nhận
        $claimedIds = [];
        if (Auth::check()) {
            $user = Auth::user();

            $claimedIds = $user->vouchers()
                ->pluck('voucher.id')
                ->toArray();

            // Logic hiển thị cho user đã đăng nhập:
            // - Nếu user đã nhận voucher => luôn hiển thị (để hiện "Đã nhận")
            // - Nếu chưa nhận:
            //      + Nếu voucher có qty và đã đủ số lượng claim => ẩn
            //      + Nếu qty null hoặc còn slot => hiển thị
            $vouchers = $vouchers->filter(function ($voucher) use ($claimedIds) {
                // User đã nhận rồi → luôn hiển thị
                if (in_array($voucher->id, $claimedIds)) {
                    return true;
                }

                // Voucher có giới hạn số lượng phát hành (qty)
                if (!is_null($voucher->qty)) {
                    if ($voucher->claims_count >= $voucher->qty) {
                        // Đã phát hết số lượng
                        return false;
                    }
                }

                return true;
            })->values();
        } else {
            // Guest:
            // - Chỉ thấy những voucher chưa bị nhận hết số lượng (nếu có qty)
            $vouchers = $vouchers->filter(function ($voucher) {
                if (!is_null($voucher->qty) && $voucher->claims_count >= $voucher->qty) {
                    return false;
                }
                return true;
            })->values();
        }

        return view('client.voucher.index', compact('vouchers', 'claimedIds'));
    }


    /** ====== XỬ LÝ NHẬN VOUCHER ====== */
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

        // Kiểm tra số lượng phát hành (qty): nếu đã hết slot thì không cho nhận thêm
        if (!is_null($voucher->qty)) {
            $currentClaims = $voucher->users()->count(); // số user đã nhận voucher này
            if ($currentClaims >= $voucher->qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher đã được nhận hết số lượng cho phép.',
                ], 400);
            }
        }

        // Kiểm tra user đã nhận chưa (mỗi user chỉ nhận 1 lần / mã)
        $alreadyClaimed = $user->vouchers()->where('voucher.id', $voucher->id)->exists();
        if ($alreadyClaimed) {
            return response()->json(['success' => false, 'message' => 'Bạn đã nhận voucher này trước đó.'], 400);
        }

        try {
            // Gán voucher cho user (không trừ qty nữa, qty là tổng slot, kiểm tra bằng users()->count)
            $user->vouchers()->attach($voucher->id, ['claimed_at' => now()]);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Không thể nhận voucher.'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nhận voucher thành công!',
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'claimed_at' => now()->toDateTimeString(),
        ]);
    }


    /** ====== TRANG MY VOUCHERS ====== */
    public function myVouchers(Request $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    // Danh sách voucher của user
    $query = $user->vouchers()
        ->orderByDesc('user_voucher.claimed_at');

    // Tìm kiếm theo mã / ghi chú
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('code', 'like', '%' . $search . '%')
                ->orWhere('note', 'like', '%' . $search . '%');
        });
    }

    // Lọc theo hiệu lực theo NGÀY (chưa xét số lượt dùng)
    if ($request->filled('filter')) {
        if ($request->filter === 'valid') {
            $query->whereDate('end_date', '>=', now());
        } elseif ($request->filter === 'expired') {
            $query->whereDate('end_date', '<', now());
        }
    }

    $vouchers = $query->get();

    // Nếu không có voucher thì trả về luôn
    if ($vouchers->isEmpty()) {
        return view('account.my', [
            'vouchers'     => $vouchers,
            'usageCounts'  => [],
        ]);
    }

    // Xác định bảng và cột user trong voucher_usage từ model
    $usageModel = new VoucherUsage();
    $usageTable = $usageModel->getTable(); // thường là voucher_usage

    $userCol = null;
    if (Schema::hasColumn($usageTable, 'nguoi_dung_id')) {
        $userCol = 'nguoi_dung_id';
    } elseif (Schema::hasColumn($usageTable, 'user_id')) {
        $userCol = 'user_id';
    }

    // Lấy số lần user này đã dùng từng voucher
    $usageCounts = [];
    if ($userCol) {
        $usageCounts = VoucherUsage::query()
            ->whereIn('voucher_id', $vouchers->pluck('id'))
            ->where($userCol, $user->id)
            ->groupBy('voucher_id')
            ->selectRaw('voucher_id, COUNT(*) as used_count')
            ->pluck('used_count', 'voucher_id')
            ->toArray();
    }

    // Gắn thuộc tính used_count vào từng voucher (0 nếu chưa dùng)
    foreach ($vouchers as $voucher) {
        $voucher->used_count = $usageCounts[$voucher->id] ?? 0;
    }

    // Nếu sau này muốn ẨN voucher đã dùng hết lượt khỏi ví, có thể lọc tiếp ở đây
    // $vouchers = $vouchers->filter(fn ($v) => $v->used_count < ($v->usage_limit_per_user ?? PHP_INT_MAX))->values();

    return view('account.my', compact('vouchers', 'usageCounts'));
}

}
