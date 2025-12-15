<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VoucherController extends Controller
{
    /** ====== HIỂN THỊ DANH SÁCH VOUCHER ====== */
    /** ====== HIỂN THỊ DANH SÁCH VOUCHER ====== */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Tìm kiếm theo mã / tên
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
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

        // ----- TÍNH ĐIỂM HIỆN CÓ CỦA USER (nếu đã đăng nhập) -----
        $currentPoints = null;
        if (Auth::check()) {
            $user = Auth::user();
            $totalSpent = \App\Models\DatPhong::where('nguoi_dung_id', $user->id)
                ->where('trang_thai', 'hoan_thanh')
                ->sum('tong_tien');

            $earnedPoints = (int) floor((float)$totalSpent / 1000);
            $spentPoints = 0;
            if (Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
                $spentPoints = (int) DB::table('user_voucher')
                    ->where('user_id', $user->id)
                    ->whereNotNull('points_spent')
                    ->sum('points_spent');
            }

            $currentPoints = max(0, $earnedPoints - $spentPoints);
        }

        return view('client.voucher.index', compact('vouchers', 'claimedIds', 'currentPoints'));
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
            $currentClaims = $voucher->users()->count();
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

        // --- Phần điểm: kiểm tra nếu voucher yêu cầu điểm ---
        $pointsRequired = 0;
        if (Schema::hasColumn((new Voucher)->getTable(), 'points_required')) {
            $pointsRequired = (int) ($voucher->points_required ?? 0);
        }

        if ($pointsRequired > 0) {
            $totalSpent = \App\Models\DatPhong::where('nguoi_dung_id', $user->id)
                ->where('trang_thai', 'hoan_thanh')
                ->sum('tong_tien');

            $totalSpent = (float) $totalSpent;

            $earnedPoints = (int) floor($totalSpent / 1000);

            $spentPoints = 0;
            if (Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
                $spentPoints = (int) DB::table('user_voucher')
                    ->where('user_id', $user->id)
                    ->whereNotNull('points_spent')
                    ->sum('points_spent');
            }

            $currentBalance = max(0, $earnedPoints - $spentPoints);

            if ($currentBalance < $pointsRequired) {
                return response()->json([
                    'success' => false,
                    'message' => "Bạn cần {$pointsRequired} điểm để đổi voucher này. Số điểm hiện có: {$currentBalance}."
                ], 400);
            }
        }

        try {
            $attachData = ['claimed_at' => now()];
            if ($pointsRequired > 0 && Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
                $attachData['points_spent'] = $pointsRequired;
            }

            $user->vouchers()->attach($voucher->id, $attachData);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Không thể nhận voucher.'], 400);
        }

        $totalSpent = \App\Models\DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'hoan_thanh')
            ->sum('tong_tien');

        $earnedPoints = (int) floor((float)$totalSpent / 1000);
        $spentPoints = 0;
        if (Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
            $spentPoints = (int) DB::table('user_voucher')
                ->where('user_id', $user->id)
                ->whereNotNull('points_spent')
                ->sum('points_spent');
        }
        $currentPoints = max(0, $earnedPoints - $spentPoints);

        return response()->json([
            'success' => true,
            'message' => 'Nhận voucher thành công!',
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'claimed_at' => now()->toDateTimeString(),
            'points_spent' => $pointsRequired > 0 ? $pointsRequired : 0,
            'currentPoints' => $currentPoints
        ]);
    }


    /** ====== TRANG MY VOUCHERS ====== */
    public function myVouchers(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $perPage = (int) $request->get('per_page', 9);
        if ($perPage <= 0) $perPage = 9;

        $query = $user->vouchers()
            ->orderByDesc('user_voucher.claimed_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('filter')) {
            if ($request->filter === 'valid') {
                $query->whereDate('end_date', '>=', now());
            } elseif ($request->filter === 'expired') {
                $query->whereDate('end_date', '<', now());
            }
        }

        $vouchers = $query->paginate($perPage)->withQueryString();

        if (method_exists($vouchers, 'total') && $vouchers->total() === 0) {
            $totalSpent = \App\Models\DatPhong::where('nguoi_dung_id', $user->id)
                ->where('trang_thai', 'hoan_thanh')
                ->sum('tong_tien');
            $earnedPoints = (int) floor((float)$totalSpent / 1000);
            $spentPoints = 0;
            if (Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
                $spentPoints = (int) DB::table('user_voucher')
                    ->where('user_id', $user->id)
                    ->whereNotNull('points_spent')
                    ->sum('points_spent');
            }
            $currentPoints = max(0, $earnedPoints - $spentPoints);

            return view('account.my', [
                'vouchers'    => $vouchers,
                'usageCounts' => [],
                'currentPoints' => $currentPoints,
            ]);
        }

        $usageModel = new VoucherUsage();
        $usageTable = $usageModel->getTable();

        $userCol = null;
        if (Schema::hasColumn($usageTable, 'nguoi_dung_id')) {
            $userCol = 'nguoi_dung_id';
        } elseif (Schema::hasColumn($usageTable, 'user_id')) {
            $userCol = 'user_id';
        }

        $pageVoucherIds = $vouchers->getCollection()->pluck('id')->toArray();

        $usageCounts = [];
        if ($userCol && !empty($pageVoucherIds)) {
            $usageCounts = VoucherUsage::query()
                ->whereIn('voucher_id', $pageVoucherIds)
                ->where($userCol, $user->id)
                ->groupBy('voucher_id')
                ->selectRaw('voucher_id, COUNT(*) as used_count')
                ->pluck('used_count', 'voucher_id')
                ->toArray();
        }

        $vouchers->getCollection()->transform(function ($voucher) use ($usageCounts) {
            $voucher->used_count = $usageCounts[$voucher->id] ?? 0;
            return $voucher;
        });

        // ----- TÍNH ĐIỂM CỦA USER -----
        $totalSpent = \App\Models\DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'hoan_thanh')
            ->sum('tong_tien');

        $earnedPoints = (int) floor((float)$totalSpent / 1000);
        $spentPoints = 0;
        if (Schema::hasTable('user_voucher') && Schema::hasColumn('user_voucher', 'points_spent')) {
            $spentPoints = (int) DB::table('user_voucher')
                ->where('user_id', $user->id)
                ->whereNotNull('points_spent')
                ->sum('points_spent');
        }
        $currentPoints = max(0, $earnedPoints - $spentPoints);

        return view('account.my', compact('vouchers', 'usageCounts', 'currentPoints'));
    }
}
