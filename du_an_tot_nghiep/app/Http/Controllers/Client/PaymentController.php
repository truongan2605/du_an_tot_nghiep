<?php

namespace App\Http\Controllers\Client;

use App\Models\VoucherUsage;
use Carbon\Carbon;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\GiuPhong;
use App\Mail\PaymentFail;
use Illuminate\Support\Str;
use App\Mail\PaymentSuccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Services\PaymentNotificationService;
use App\Services\MoMoPaymentService;

class PaymentController extends Controller
{
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

    // Tăng giá 10% cho 3 ngày cuối tuần (T6, T7, CN)
    public const WEEKEND_MULTIPLIER = 1.10;

    public function initiateVNPay(Request $request)
    {
        Log::info('initiateVNPay request:', $request->all());
        Log::info('DEBUG: deposit_percentage value', [
            'deposit_percentage' => $request->input('deposit_percentage'),
            'has_deposit' => $request->has('deposit_percentage')
        ]);

        try {
            // Validate input data
            $validated = $request->validate([
                'phong_id' => 'required|exists:phong,id',
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                'amount' => 'required|numeric|min:1',
                'total_amount' => 'required|numeric|min:1',
                'deposit_percentage' => 'nullable|in:50,100',  // Made nullable
                'so_khach' => 'nullable|integer|min:1',
                'adults' => 'required|integer|min:1',
                'children' => 'nullable|integer|min:0',
                'children_ages' => 'nullable|array',
                'children_ages.*' => 'integer|min:0|max:12',
                'addons' => 'nullable|array',
                'rooms_count' => 'required|integer|min:1',
                'phuong_thuc' => 'required|in:vnpay',
                'name' => 'required|string|max:255|min:2',
                'address' => 'required|string|max:500|min:5',
                'phone' => [
                    'required',
                    'string',
                    'regex:/^0[1-9]\d{8,9}$/', // SĐT VN 9–10 số, bắt đầu 0[1-9]
                ],
                // voucher
                'voucher_id'        => 'nullable|integer',
                'voucher_discount'  => 'nullable|numeric|min:0',
                'ma_voucher'        => 'nullable|string|max:50',
            ]);

            // Default to 50% if not provided (radio button not submitted)
            $depositPercentage = isset($validated['deposit_percentage'])
                ? (int) $validated['deposit_percentage']
                : 50;

            // Validate amount vs total_amount (client gửi total_amount sau voucher)
            // Cho phép amount > total_amount tối đa 2000 do làm tròn lên 1000
            if ($validated['amount'] > $validated['total_amount'] + 2000) {
                return response()->json([
                    'error' => "Số tiền thanh toán không hợp lệ (lớn hơn tổng tiền quá nhiều)"
                ], 400);
            }

            // Validate amount khớp tỷ lệ cọc (khi < 100%)
            if ($depositPercentage < 100) {
                $expectedDepositRaw = $validated['total_amount'] * ($depositPercentage / 100);
                $expectedDepositRounded = ceil($expectedDepositRaw / 1000) * 1000;
                $tolerance = 2000;
                $difference = abs($validated['amount'] - $expectedDepositRounded);

                if ($difference > $tolerance) {
                    Log::warning('Deposit validation failed', [
                        'amount' => $validated['amount'],
                        'expected_raw' => $expectedDepositRaw,
                        'expected_rounded' => $expectedDepositRounded,
                        'total_amount' => $validated['total_amount'],
                        'deposit_percentage' => $depositPercentage,
                        'difference' => $difference,
                    ]);
                    return response()->json([
                        'error' => "Deposit không hợp lệ (phải là {$depositPercentage}% tổng tiền). Amount: {$validated['amount']}, Expected: {$expectedDepositRounded}"
                    ], 400);
                }
            } else {
                // Khi 100%, amount phải gần bằng total_amount (đã làm tròn)
                $expectedTotalRounded = ceil($validated['total_amount'] / 1000) * 1000;
                $tolerance = 2000;
                $difference = abs($validated['amount'] - $expectedTotalRounded);

                if ($difference > $tolerance) {
                    Log::warning('Full payment validation failed', [
                        'amount' => $validated['amount'],
                        'total_amount' => $validated['total_amount'],
                        'expected_rounded' => $expectedTotalRounded,
                        'difference' => $difference,
                    ]);
                    return response()->json([
                        'error' => "Số tiền thanh toán không hợp lệ. Amount: {$validated['amount']}, Expected: {$expectedTotalRounded}"
                    ], 400);
                }
            }

            $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides'])
                ->findOrFail($validated['phong_id']);

            $maThamChieu = 'DP' . strtoupper(Str::random(8));

            $from = Carbon::parse($validated['ngay_nhan_phong']);
            $to   = Carbon::parse($validated['ngay_tra_phong']);

            $nights        = $this->calculateNights($validated['ngay_nhan_phong'], $validated['ngay_tra_phong']);
            $weekendNights = $this->countWeekendNights($from, $to);  // số đêm cuối tuần
            $weekdayNights = max(0, $nights - $weekendNights);       // số đêm ngày thường

            $adultsInput    = $validated['adults'];
            $childrenInput  = $validated['children'] ?? 0;
            $childrenAges   = $validated['children_ages'] ?? [];

            $computedAdults     = $adultsInput;
            $chargeableChildren = 0;
            foreach ($childrenAges as $age) {
                $age = (int) $age;
                if ($age >= 13) {
                    $computedAdults++;
                } elseif ($age >= 7) {
                    $chargeableChildren++;
                }
            }

            // Tính sức chứa phòng
            $roomCapacity = 0;
            if ($phong->bedTypes && $phong->bedTypes->count()) {
                foreach ($phong->bedTypes as $bt) {
                    $qty = (int) ($bt->pivot->quantity ?? 0);
                    $cap = (int) ($bt->capacity ?? 1);
                    $roomCapacity += $qty * $cap;
                }
            }
            if ($roomCapacity <= 0) {
                $roomCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));
            }

            $roomsCount         = $validated['rooms_count'];
            $totalRoomCapacity  = $roomCapacity * $roomsCount;
            $countedPersons     = $computedAdults + $chargeableChildren;
            $extraCountTotal    = max(0, $countedPersons - $totalRoomCapacity);
            $adultBeyondBaseTot = max(0, $computedAdults - $totalRoomCapacity);
            $adultExtraTotal    = min($adultBeyondBaseTot, $extraCountTotal);
            $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
            $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);

            $adultsChargePerNight   = $adultExtraTotal * self::ADULT_PRICE;
            $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;

            // Giá base / đêm (ngày thường, chưa cộng phụ thu cuối tuần)
            $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);

            // Addons
            $selectedAddonIds = $validated['addons'] ?? [];
            $selectedAddons   = collect();
            if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
                $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
            }

            $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
            $addonsPerNight        = $addonsPerNightPerRoom * $roomsCount;

            // --- ÁP DỤNG GIÁ CUỐI TUẦN 10% ---
            // Tiền phòng (chỉ phần base) tách riêng ngày thường / cuối tuần
            $baseWeekdayTotal = $basePerNight * $roomsCount * $weekdayNights;
            $baseWeekendTotal = $basePerNight * self::WEEKEND_MULTIPLIER * $roomsCount * $weekendNights;

            // Phụ thu người lớn / trẻ em / addons áp dụng cho mỗi đêm
            $extrasPerNight = $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
            $extrasTotal    = $extrasPerNight * $nights;

            // Tổng tiền trước khi áp voucher (server tính)
            $snapshotTotalServer = $baseWeekdayTotal + $baseWeekendTotal + $extrasTotal;

            // --- ÁP DỤNG VOUCHER (NẾU CÓ) ---
            $voucherId = $validated['voucher_id'] ?? null;
            $voucherDiscount = isset($validated['voucher_discount'])
                ? (float) $validated['voucher_discount']
                : 0.0;

            // Chuẩn hoá discount
            if ($voucherDiscount < 0) {
                $voucherDiscount = 0;
            }
            if ($voucherDiscount > $snapshotTotalServer) {
                $voucherDiscount = $snapshotTotalServer;
            }

            // Tổng tiền sau voucher (net)
            $netTotalServer = $snapshotTotalServer - $voucherDiscount;

            // Giá trung bình mỗi đêm sau voucher (cho toàn bộ booking)
            $finalPerNightServer = $netTotalServer / max(1, $nights);

            // Áp dụng giảm giá theo hạng thành viên
            // $user = Auth::user();
            // $memberDiscountAmount = 0;
            // if ($user && $user->member_level) {
            //     $memberDiscountPercent = $user->getMemberDiscountPercent();
            //     if ($memberDiscountPercent > 0) {
            //         $memberDiscountAmount = ($snapshotTotalServer * $memberDiscountPercent / 100);
            //         $snapshotTotalServer = $snapshotTotalServer - $memberDiscountAmount;
            //     }
            // }

            $snapshotMeta = [
                'phong_id'            => $validated['phong_id'],
                'loai_phong_id'       => $phong->loai_phong_id,
                'adults'              => $adultsInput,
                'children'            => $childrenInput,
                'children_ages'       => $childrenAges,
                'computed_adults'     => $computedAdults,
                'chargeable_children' => $chargeableChildren,
                'room_capacity_single' => $roomCapacity,
                'total_room_capacity'  => $totalRoomCapacity,
                'counted_persons'      => $countedPersons,
                'extra_count_total'    => $extraCountTotal,
                'adult_extra_total'    => $adultExtraTotal,
                'children_extra_total' => $childrenExtraTotal,

                'room_base_per_night'       => $basePerNight,
                'weekday_nights'            => $weekdayNights,
                'weekend_nights'            => $weekendNights,
                'weekend_multiplier'        => self::WEEKEND_MULTIPLIER,
                'base_weekday_total'        => $baseWeekdayTotal,
                'base_weekend_total'        => $baseWeekendTotal,
                'adults_charge_per_night'   => $adultsChargePerNight,
                'children_charge_per_night' => $childrenChargePerNight,
                'addons_per_night'          => $addonsPerNight,

                'addons' => $selectedAddons->map(
                    fn($a) => ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia]
                )->toArray(),
                'final_per_night' => $finalPerNightServer,
                'nights'          => $nights,
                'rooms_count'     => $roomsCount,

                'tong_tien_truoc_voucher' => $snapshotTotalServer,
                'voucher_id'              => $voucherId,
                'voucher_discount'        => $voucherDiscount,
                'tong_tien'               => $netTotalServer,

                'deposit_percentage' => $depositPercentage,
                'phuong_thuc' => $validated['phuong_thuc'],
                'contact_name' => $validated['name'],
                'contact_address' => $validated['address'],
                'contact_phone' => $validated['phone'],
                // 'member_discount_amount' => $memberDiscountAmount,
                // 'member_level' => $user ? ($user->member_level ?? 'dong') : 'dong',
                // 'member_discount_percent' => $user ? $user->getMemberDiscountPercent() : 0,
            ];

            return DB::transaction(function () use (
                $validated, $maThamChieu, $snapshotMeta, $phong, $request, $from, $to, $nights,
                $roomsCount, $finalPerNightServer, $snapshotTotalServer, $selectedAddons, $depositPercentage,
                $netTotalServer,
                $voucherId,
                $voucherDiscount,
            ) {
                // Nếu thanh toán 100% thì không cần thanh toán thêm nữa
                $canThanhToan = $depositPercentage < 100;

                $datPhongData = [
                    'ma_tham_chieu' => $maThamChieu,
                    'nguoi_dung_id' => Auth::id(),
                    'phong_id' => $validated['phong_id'],
                    'ngay_nhan_phong' => $validated['ngay_nhan_phong'],
                    'ngay_tra_phong'  => $validated['ngay_tra_phong'],

                    // Tổng sau voucher
                    'tong_tien'       => $netTotalServer,
                    'deposit_amount'  => $validated['amount'],

                    'so_khach'        => $validated['so_khach'] ?? ($validated['adults'] + ($validated['children'] ?? 0)),
                    'trang_thai'      => 'dang_cho',
                    'can_thanh_toan'  => $canThanhToan,
                    'can_xac_nhan'    => false,
                    'created_by'      => Auth::id(),
                    'snapshot_meta'   => $snapshotMeta,
                    'phuong_thuc'     => $validated['phuong_thuc'],
                    'contact_name'    => $validated['name'],
                    'contact_address' => $validated['address'],
                    'contact_phone' => $validated['phone'],
                    // Lưu voucher vào dat_phong
                    'voucher_id'       => $voucherId,
                    'voucher_discount' => $voucherDiscount,
                    'ma_voucher'       => $validated['ma_voucher'] ?? null,
                ];

                // Thêm member_discount_amount nếu cột tồn tại
                // if (Schema::hasColumn('dat_phong', 'member_discount_amount')) {
                //     $datPhongData['member_discount_amount'] = $memberDiscountAmount;
                // }

                $dat_phong = DatPhong::create($datPhongData);


                Log::info('Payment booking created with contact', [
                    'dat_phong_id'   => $dat_phong->id,
                    'phuong_thuc'    => $dat_phong->phuong_thuc,
                    'contact_name'   => $dat_phong->contact_name,
                    'contact_phone'  => $dat_phong->contact_phone,
                    'validated_data' => $validated,
                ]);

                // Khoá loại phòng để tránh race-condition
                if (Schema::hasTable('loai_phong')) {
                    DB::table('loai_phong')
                        ->where('id', $phong->loai_phong_id)
                        ->lockForUpdate()
                        ->first();
                }

                $requiredSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
                $availableNow = $this->computeAvailableRoomsCount(
                    $phong->loai_phong_id,
                    $from,
                    $to,
                    $requiredSignature
                );
                if ($roomsCount > $availableNow) {
                    Log::warning('Room availability check failed', [
                        'phong_id' => $validated['phong_id'],
                        'loai_phong_id' => $phong->loai_phong_id,
                        'rooms_requested' => $roomsCount,
                        'rooms_available' => $availableNow,
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                        'required_signature' => $requiredSignature,
                    ]);
                    throw new \Exception("Không đủ phòng trống. Hiện có {$availableNow} phòng khả dụng, bạn yêu cầu {$roomsCount} phòng. Vui lòng thử lại sau hoặc chọn ngày khác.");
                }

                $holdBase = [
                    'dat_phong_id'  => $dat_phong->id,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'het_han_luc'   => now()->addMinutes(15),
                    'released'      => false,
                    'meta'          => json_encode([
                        'final_per_night'      => $finalPerNightServer / $roomsCount,
                        'snapshot_total'       => $snapshotTotalServer,
                        'nights'               => $nights,
                        'rooms_count'          => $roomsCount,
                        'addons'               => $selectedAddons->map(
                            fn($a) => ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia]
                        )->toArray(),
                        'spec_signature_hash'  => $this->generateSpecSignatureHash($validated, $phong),
                        'requested_spec_signature' => $requiredSignature,
                    ], JSON_UNESCAPED_UNICODE),
                ];

                $baseSignature   = $phong->spec_signature_hash ?? $phong->specSignatureHash();
                $baseTienNghi    = method_exists($phong, 'effectiveTienNghiIds')
                    ? $phong->effectiveTienNghiIds()
                    : [];
                $selectedAddonIdsArr = $selectedAddons->pluck('id')->map('intval')->toArray();
                $mergedTienNghi      = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
                sort($mergedTienNghi, SORT_NUMERIC);
                $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

                $specArray = [
                    'loai_phong_id' => (int) $phong->loai_phong_id,
                    'tien_nghi'     => $mergedTienNghi,
                    'beds'          => $bedSpec,
                ];
                ksort($specArray);
                $requestedSpecSignature = md5(json_encode(
                    $specArray,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ));

                $requestedPhongId   = $phong->id;
                $requestedReserved  = 0;

                if (Schema::hasColumn('giu_phong', 'phong_id')) {
                    $isBooked = false;
                    if (Schema::hasTable('dat_phong_item')) {
                        $fromStartStr = $from->copy()->setTime(14, 0)->toDateTimeString();
                        $toEndStr     = $to->copy()->setTime(12, 0)->toDateTimeString();
                        $isBooked     = DB::table('dat_phong_item')
                            ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                            ->where('dat_phong_item.phong_id', $requestedPhongId)
                            ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                            ->whereRaw(
                                "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                                 AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                                [$toEndStr, $fromStartStr]
                            )
                            ->exists();
                    }

                    $isHeld = false;
                    if (!$isBooked && Schema::hasTable('giu_phong')) {
                        $isHeld = DB::table('giu_phong')
                            ->where('phong_id', $requestedPhongId)
                            ->where('released', false)
                            ->where('het_han_luc', '>', now())
                            ->exists();
                    }

                    if (!$isBooked && !$isHeld) {
                        $locked = Phong::where('id', $requestedPhongId)->lockForUpdate()->first();
                        if ($locked) {
                            $row                = $holdBase;
                            $row['so_luong']    = 1;
                            $row['phong_id']    = $requestedPhongId;
                            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                $row['spec_signature_hash'] = $baseSignature;
                            }
                            $row['meta'] = json_encode(array_merge(
                                json_decode($row['meta'], true),
                                [
                                    'selected_phong_id'  => $requestedPhongId,
                                    'selected_phong_ids' => [$requestedPhongId],
                                ]
                            ), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($row);
                            $requestedReserved = 1;
                            Log::debug('Payment: giu_phong inserted per-phong (requested)', [
                                'phong_id'     => $requestedPhongId,
                                'dat_phong_id' => $dat_phong->id,
                            ]);
                        }
                    }
                }

                $stillNeeded = max(0, $roomsCount - $requestedReserved);
                $selectedIds = [];
                if ($stillNeeded > 0) {
                    $selectedIds = $this->computeAvailableRoomIds(
                        $phong->loai_phong_id,
                        $from,
                        $to,
                        $stillNeeded,
                        $requestedSpecSignature
                    );
                    if (empty($selectedIds) || count($selectedIds) < $stillNeeded) {
                        $need        = $stillNeeded - count($selectedIds);
                        $fallbackIds = $this->computeAvailableRoomIds(
                            $phong->loai_phong_id,
                            $from,
                            $to,
                            $need,
                            null
                        );
                        $selectedIds = array_values(array_unique(array_merge($selectedIds, $fallbackIds)));
                    }
                    if ($requestedReserved && !empty($selectedIds)) {
                        $selectedIds = array_values(array_diff($selectedIds, [$requestedPhongId]));
                    }
                }

                if (!empty($selectedIds)) {
                    $locked     = Phong::whereIn('id', $selectedIds)
                        ->lockForUpdate()
                        ->get(['id'])
                        ->pluck('id')
                        ->toArray();
                    $selectedIds = array_values(array_intersect($selectedIds, $locked));
                }

                $reservedCount = $requestedReserved;
                if (!empty($selectedIds)) {
                    foreach ($selectedIds as $pid) {
                        if ($reservedCount >= $roomsCount) {
                            break;
                        }
                        $row             = $holdBase;
                        $row['so_luong'] = 1;
                        $row['phong_id'] = $pid;
                        if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                            $row['spec_signature_hash'] = $baseSignature;
                        }
                        $row['meta'] = json_encode(array_merge(
                            json_decode($row['meta'], true),
                            [
                                'selected_phong_id'  => $pid,
                                'selected_phong_ids' => $selectedIds,
                            ]
                        ), JSON_UNESCAPED_UNICODE);
                        DB::table('giu_phong')->insert($row);
                        $reservedCount++;
                        Log::debug('Payment: giu_phong inserted per-phong', [
                            'phong_id'     => $pid,
                            'dat_phong_id' => $dat_phong->id,
                        ]);
                    }
                }

                if ($roomsCount - $reservedCount > 0 && Schema::hasColumn('giu_phong', 'phong_id')) {
                    $aggRow             = $holdBase;
                    $aggRow['so_luong'] = $roomsCount - $reservedCount;
                    if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                        $aggRow['spec_signature_hash'] = $baseSignature;
                    }
                    $aggRow['meta'] = json_encode(array_merge(
                        json_decode($aggRow['meta'], true),
                        ['reserved_count' => $reservedCount]
                    ), JSON_UNESCAPED_UNICODE);
                    DB::table('giu_phong')->insert($aggRow);
                    Log::debug('Payment: giu_phong inserted aggregate remaining', [
                        'remaining'    => $roomsCount - $reservedCount,
                        'dat_phong_id' => $dat_phong->id,
                    ]);
                } elseif (!Schema::hasColumn('giu_phong', 'phong_id')) {
                    $holdBase['so_luong']            = $roomsCount;
                    $holdBase['spec_signature_hash'] = $requestedSpecSignature;
                    DB::table('giu_phong')->insert($holdBase);
                }

                $giao_dich = GiaoDich::create([
                    'dat_phong_id' => $dat_phong->id,
                    'nha_cung_cap' => 'vnpay',
                    'so_tien'      => $validated['amount'],
                    'don_vi'       => 'VND',
                    'trang_thai'   => 'dang_cho',
                    'ghi_chu'      => "Thanh toán đặt cọc phòng:{$dat_phong->ma_tham_chieu}",
                ]);

                $vnp_Url        = env('VNPAY_URL');
                $vnp_TmnCode    = env('VNPAY_TMN_CODE');
                $vnp_HashSecret = env('VNPAY_HASH_SECRET');
                $vnp_ReturnUrl  = env('VNPAY_RETURN_URL');

                $merchantTxnRef = $giao_dich->id . '-' . time();

                $inputData = [
                    "vnp_Version"   => "2.1.0",
                    "vnp_TmnCode"   => $vnp_TmnCode,
                    "vnp_Amount"    => $validated['amount'] * 100,
                    "vnp_Command"   => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode"  => "VND",
                    "vnp_IpAddr"    => $request->ip(),
                    "vnp_Locale"    => "vn",
                    "vnp_OrderInfo" => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    "vnp_OrderType" => "billpayment",
                    "vnp_ReturnUrl" => $vnp_ReturnUrl,
                    "vnp_TxnRef"    => $merchantTxnRef,
                ];

                ksort($inputData);
                $query          = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
                $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
                $redirectUrl    = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

                return response()->json([
                    'redirect_url'  => $redirectUrl,
                    'dat_phong_id'  => $dat_phong->id,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('VNPay initiate validation error', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['error' => 'Dữ liệu không hợp lệ: ' . implode(', ', array_map(function ($errors) {
                return implode(', ', $errors);
            }, $e->errors()))], 422);
        } catch (\Throwable $e) {
            Log::error('VNPay initiate error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->except(['_token'])
            ]);
            return response()->json([
                'error' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }

    public function initiateMoMo(Request $request)
    {
        Log::info('initiateMoMo request:', $request->all());

        try {
            // Check if booking already exists (from BookingController)
            $existingBookingId = $request->input('dat_phong_id');

            if ($existingBookingId) {
                // Use existing booking
                $dat_phong = DatPhong::findOrFail($existingBookingId);

                if ($dat_phong->nguoi_dung_id !== Auth::id()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                Log::info('Using existing booking for MoMo', ['dat_phong_id' => $dat_phong->id]);

                // Check for existing pending transaction to prevent duplicates
                $existingTransaction = GiaoDich::where('dat_phong_id', $dat_phong->id)
                    ->where('nha_cung_cap', 'momo')
                    ->where('trang_thai', 'dang_cho')
                    ->first();

                if ($existingTransaction) {
                    Log::info('Found existing pending MoMo transaction - marking as failed and creating new', [
                        'old_transaction_id' => $existingTransaction->id
                    ]);

                    // Mark old transaction as failed to avoid duplicate orderId error
                    $existingTransaction->update([
                        'trang_thai' => 'that_bai',
                        'ghi_chu' => 'Replaced by new payment attempt',
                    ]);
                }

                // Always create new transaction (MoMo doesn't allow reusing orderId)
                $giao_dich = GiaoDich::create([
                    'dat_phong_id' => $dat_phong->id,
                    'nha_cung_cap' => 'momo',
                    'so_tien' => $request->input('amount'),
                    'trang_thai' => 'dang_cho',
                    'metadata' => json_encode([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]),
                ]);

                // Generate MoMo payment URL
                $momoService = new MoMoPaymentService();
                $paymentData = $momoService->createPaymentUrl([
                    'orderId' => (string)$giao_dich->id,
                    'amount' => (int)$request->input('amount'),
                    'orderInfo' => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    'returnUrl' => config('services.momo.return_url'),
                    'notifyUrl' => config('services.momo.notify_url'),
                    'extraData' => '',
                ]);

                // Store the unique MoMo orderId in transaction metadata
                $metadata = json_decode($giao_dich->metadata, true) ?? [];
                $metadata['momo_order_id'] = $paymentData['orderId'];
                $giao_dich->update(['metadata' => json_encode($metadata)]);

                Log::info('MoMo payment initiated', [
                    'transaction_id' => $giao_dich->id,
                    'momo_order_id' => $paymentData['orderId'],
                    'booking_id' => $dat_phong->id,
                ]);

                return response()->json([
                    'redirect_url' => $paymentData['payUrl'],
                    'dat_phong_id' => $dat_phong->id
                ]);
            }

            // Original flow - create new booking (not used in current flow)
            $validated = $request->validate([
                'phong_id' => 'required|exists:phong,id',
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                'amount' => 'required|numeric|min:1',
                'total_amount' => 'required|numeric|min:1|gte:amount',
                'adults' => 'required|integer|min:1',
                'children' => 'nullable|integer|min:0',
                'rooms_count' => 'required|integer|min:1',
                'phuong_thuc' => 'required|in:momo',
                'name' => 'required|string|max:255|min:2',
                'address' => 'required|string|max:500|min:5',
                'phone' => 'required|string|regex:/^0[3-9]\d{8}$/',
            ]);

            return response()->json(['error' => 'Direct booking creation not supported in this flow'], 400);

        } catch (\Throwable $e) {
            Log::error('MoMo initiate error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
    // Add these methods to PaymentController.php after the handleIpn method

public function handleMoMoCallback(Request $request)
{
    $user = $request->user();
    if (!$user) return response()->json(['error' => 'Authentication required'], 401);

    Log::info('MoMo Callback Received', $request->all());

    $momoService = new MoMoPaymentService();

    // Verify signature
    if (!$momoService->verifySignature($request->all())) {
        return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
    }

    $orderId = $request->input('orderId');
    $resultCode = $request->input('resultCode');
    $amount = $request->input('amount');
    $transId = $request->input('transId');

    // Extract transaction ID from orderId format: {transaction_id}-{timestamp}-{random}
    $transactionId = explode('-', $orderId)[0];

    Log::info('MoMo Callback - Parsing orderId', [
        'full_orderId' => $orderId,
        'extracted_transaction_id' => $transactionId,
    ]);

    $giao_dich = GiaoDich::find($transactionId);
    if (!$giao_dich) {
        Log::error('MoMo Callback - Transaction not found', [
            'orderId' => $orderId,
            'extracted_transaction_id' => $transactionId,
        ]);
        return view('payment.fail', ['code' => '01', 'message' => 'Không tìm thấy giao dịch']);
    }

    $dat_phong = $giao_dich->dat_phong;
    if (!$dat_phong) return view('payment.fail', ['code' => '02', 'message' => 'Không tìm thấy đơn đặt phòng']);

    // Check if booking has been cancelled
    if ($dat_phong->trang_thai === 'da_huy') {
        $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Đơn đặt phòng đã bị hủy bởi quản trị viên']);
        return view('payment.fail', [
            'code' => '98',
            'message' => 'Đơn đặt phòng đã bị hủy. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
        ]);
    }

    // Check if transaction is already failed
    if ($giao_dich->trang_thai === 'that_bai') {
        return view('payment.fail', [
            'code' => '99',
            'message' => 'Giao dịch đã thất bại trước đó. ' . ($giao_dich->ghi_chu ?? '')
        ]);
    }

    $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : json_decode($dat_phong->snapshot_meta, true);
    $roomsCount = $meta['rooms_count'] ?? 1;

    return DB::transaction(function () use ($resultCode, $amount, $transId, $giao_dich, $dat_phong, $roomsCount, $meta) {
        if ($resultCode == 0 && $giao_dich->so_tien == $amount) {
            $giao_dich->update([
                'trang_thai' => 'thanh_cong',
                'provider_txn_ref' => $transId,
            ]);

            // Check if this is full payment (100%) or deposit (50%)
            $depositPercentage = $meta['deposit_percentage'] ?? 50;
            $isFullPayment = ($depositPercentage == 100);

            $dat_phong->update([
                'trang_thai' => 'da_xac_nhan',
                'can_xac_nhan' => true,
                'can_thanh_toan' => !$isFullPayment, // false if 100%, true if 50%
            ]);

            Log::info('MoMo deposit payment successful', [
                'dat_phong_id' => $dat_phong->id,
                'deposit_percentage' => $depositPercentage,
                'is_full_payment' => $isFullPayment,
                'can_thanh_toan' => !$isFullPayment,
            ]);

            $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
            $phongIdsToOccupy = [];

            // AUTO-DISTRIBUTE adults and children separately
            $totalAdults = ($meta['computed_adults'] ?? 0);
            $totalChildren = ($meta['chargeable_children'] ?? 0);
            $baseAdultsPerRoom = floor($totalAdults / $roomsCount);
            $extraAdults = $totalAdults % $roomsCount;
            $baseChildrenPerRoom = floor($totalChildren / $roomsCount);
            $extraChildren = $totalChildren % $roomsCount;
            
            foreach ($giu_phongs as $index => $giu_phong) {
                $meta_item = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                if (!is_array($meta_item)) $meta_item = [];

                $nights = $meta_item['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                
                // Calculate guests for THIS room
                $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                $guestsInRoom = $adultsInRoom + $childrenInRoom;
                
                // Get room and its base price
                $phong = $giu_phong->phong_id ? \App\Models\Phong::find($giu_phong->phong_id) : null;
                $capacity = $phong ? ($phong->suc_chua ?? 2) : 2;
                $basePrice = $phong ? ($phong->gia_cuoi_cung ?? 0) : 0;
                
                // Adults fill capacity FIRST (no surcharge)
                // Children overflow to extra slots (with surcharge)
                $adultsInCapacity = min($adultsInRoom, $capacity);
                $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                
                // CRITICAL FIX: Use different variable names to avoid collision
                $extraAdultsThisRoom = $adultsInRoom - $adultsInCapacity;
                $extraChildrenThisRoom = $childrenInRoom - $childrenInCapacity;
                
                $extraAdultsCharge = $extraAdultsThisRoom * self::ADULT_PRICE;
                $extraChildrenCharge = $extraChildrenThisRoom * self::CHILD_PRICE;
                $extraCharge = $extraAdultsCharge + $extraChildrenCharge;
                
                // Final price = base + surcharge
                $price_per_night = $basePrice + $extraCharge;

                $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                $itemPayload = [
                    'dat_phong_id' => $dat_phong->id,
                    'phong_id' => $giu_phong->phong_id ?? null,
                    'loai_phong_id' => $giu_phong->loai_phong_id,
                    'so_dem' => $nights,
                    'so_luong' => $giu_phong->so_luong ?? 1,
                    'so_nguoi_o' => $guestsInRoom,
                    'number_child' => $extraChildrenThisRoom,   // Extra children with surcharge
                    'number_adult' => $extraAdultsThisRoom,      // Extra adults with surcharge
                    'gia_tren_dem' => $price_per_night,
                    'tong_item' => $price_per_night * $nights * ($giu_phong->so_luong ?? 1),
                    'spec_signature_hash' => $specSignatureHash,
                ];

                Log::debug('Inserting dat_phong_item', ['dat_phong_id' => $dat_phong->id, 'payload' => $itemPayload]);
                \App\Models\DatPhongItem::create($itemPayload);

                if ($giu_phong->phong_id) {
                    $phongIdsToOccupy[] = $giu_phong->phong_id;
                }

                $giu_phong->delete();
            }

            if (!empty($phongIdsToOccupy)) {
                Phong::whereIn('id', array_unique($phongIdsToOccupy))->update(['trang_thai' => 'dang_o']);
            }

            if ($dat_phong->nguoiDung) {
                Mail::to($dat_phong->nguoiDung->email)->queue(new PaymentSuccess($dat_phong, $dat_phong->nguoiDung->name));
            }

            // Send notification
            $totalPaid = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
            $notificationService = new PaymentNotificationService();
            if ($totalPaid >= $dat_phong->tong_tien) {
                $notificationService->sendFullPaymentNotification($dat_phong, $giao_dich);
            } else {
                $notificationService->sendDepositPaymentNotification($dat_phong, $giao_dich);
            }

            return view('payment.success', compact('dat_phong'));
        } else {
            $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Mã lỗi MoMo: ' . $resultCode]);
            if ($dat_phong->trang_thai === 'dang_cho') {
                $dat_phong->update(['trang_thai' => 'da_huy']);
                GiuPhong::where('dat_phong_id', $dat_phong->id)->delete();
            }
            if ($dat_phong->nguoiDung) {
                Mail::to($dat_phong->nguoiDung->email)->queue(new PaymentFail($dat_phong, $resultCode));
            }
            return view('payment.fail', ['code' => $resultCode]);
        }
    });
}

public function handleMoMoIPN(Request $request)
{
    Log::info('MoMo IPN Received', $request->all());

    $momoService = new MoMoPaymentService();

    // Verify signature
    if (!$momoService->verifySignature($request->all())) {
        return response()->json(['resultCode' => 97, 'message' => 'Invalid signature']);
    }

    $orderId = $request->input('orderId');
    $resultCode = $request->input('resultCode');
    $amount = $request->input('amount');
    $transId = $request->input('transId');

    // Extract transaction ID from orderId format: {transaction_id}-{timestamp}-{random}
    $transactionId = explode('-', $orderId)[0];

    Log::info('MoMo IPN - Parsing orderId', [
        'full_orderId' => $orderId,
        'extracted_transaction_id' => $transactionId,
    ]);

    $giao_dich = GiaoDich::find($transactionId);
    if (!$giao_dich) {
        Log::error('MoMo IPN - Transaction not found', [
            'orderId' => $orderId,
            'extracted_transaction_id' => $transactionId,
        ]);
        return response()->json(['resultCode' => 1, 'message' => 'Transaction not found']);
    }

    $dat_phong = $giao_dich->dat_phong;
    if (!$dat_phong) return response()->json(['resultCode' => 2, 'message' => 'Booking not found']);

    $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : json_decode($dat_phong->snapshot_meta, true);
    $roomsCount = $meta['rooms_count'] ?? 1;

    return DB::transaction(function () use ($giao_dich, $dat_phong, $resultCode, $amount, $transId, $roomsCount, $meta) {
        if ($resultCode == 0 && $giao_dich->so_tien == $amount) {
            $giao_dich->update([
                'trang_thai' => 'thanh_cong',
                'provider_txn_ref' => $transId,
            ]);

            // Check if this is full payment (100%) or deposit (50%)
            $depositPercentage = $meta['deposit_percentage'] ?? 50;
            $isFullPayment = ($depositPercentage == 100);

            $dat_phong->update([
                'trang_thai' => 'da_xac_nhan',
                'can_xac_nhan' => true,
                'can_thanh_toan' => !$isFullPayment, // false if 100%, true if 50%
            ]);

            Log::info('MoMo IPN deposit payment successful', [
                'dat_phong_id' => $dat_phong->id,
                'deposit_percentage' => $depositPercentage,
                'is_full_payment' => $isFullPayment,
                'can_thanh_toan' => !$isFullPayment,
            ]);

            $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
            $phongIdsToOccupy = [];

            // AUTO-DISTRIBUTE adults and children separately
            $totalAdults = ($meta['computed_adults'] ?? 0);
            $totalChildren = ($meta['chargeable_children'] ?? 0);
            $baseAdultsPerRoom = floor($totalAdults / $roomsCount);
            $extraAdults = $totalAdults % $roomsCount;
            $baseChildrenPerRoom = floor($totalChildren / $roomsCount);
            $extraChildren = $totalChildren % $roomsCount;
            
            foreach ($giu_phongs as $index => $giu_phong) {
                $meta_item = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                if (!is_array($meta_item)) $meta_item = [];

                $nights = $meta_item['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                
                // Calculate guests for THIS room
                $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                $guestsInRoom = $adultsInRoom + $childrenInRoom;
                
                // Get room and its base price
                $phong = $giu_phong->phong_id ? \App\Models\Phong::find($giu_phong->phong_id) : null;
                $capacity = $phong ? ($phong->suc_chua ?? 2) : 2;
                $basePrice = $phong ? ($phong->gia_cuoi_cung ?? 0) : 0;
                
                // Adults fill capacity FIRST (no surcharge)
                // Children overflow to extra slots (with surcharge)
                $adultsInCapacity = min($adultsInRoom, $capacity);
                $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                
                // CRITICAL FIX: Use different variable names to avoid collision
                $extraAdultsThisRoom = $adultsInRoom - $adultsInCapacity;
                $extraChildrenThisRoom = $childrenInRoom - $childrenInCapacity;
                
                $extraAdultsCharge = $extraAdultsThisRoom * self::ADULT_PRICE;
                $extraChildrenCharge = $extraChildrenThisRoom * self::CHILD_PRICE;
                $extraCharge = $extraAdultsCharge + $extraChildrenCharge;
                
                // Final price = base + surcharge
                $price_per_night = $basePrice + $extraCharge;

                $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                $itemPayload = [
                    'dat_phong_id' => $dat_phong->id,
                    'phong_id' => $giu_phong->phong_id ?? null,
                    'loai_phong_id' => $giu_phong->loai_phong_id,
                    'so_dem' => $nights,
                    'so_luong' => $giu_phong->so_luong ?? 1,
                    'so_nguoi_o' => $guestsInRoom,
                    'number_child' => $extraChildrenThisRoom,   // Extra children with surcharge
                    'number_adult' => $extraAdultsThisRoom,      // Extra adults with surcharge
                    'gia_tren_dem' => $price_per_night,
                    'tong_item' => $price_per_night * $nights * ($giu_phong->so_luong ?? 1),
                    'spec_signature_hash' => $specSignatureHash,
                ];

                Log::debug('Inserting dat_phong_item', ['dat_phong_id' => $dat_phong->id, 'payload' => $itemPayload]);
                \App\Models\DatPhongItem::create($itemPayload);

                if ($giu_phong->phong_id) {
                    $phongIdsToOccupy[] = $giu_phong->phong_id;
                }

                $giu_phong->delete();
            }

            if (!empty($phongIdsToOccupy)) {
                Phong::whereIn('id', array_unique($phongIdsToOccupy))->update(['trang_thai' => 'dang_o']);
            }

            // Send notification
            $totalPaid = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
            $notificationService = new PaymentNotificationService();
            if ($totalPaid >= $dat_phong->tong_tien) {
                $notificationService->sendFullPaymentNotification($dat_phong, $giao_dich);
            } else {
                $notificationService->sendDepositPaymentNotification($dat_phong, $giao_dich);
            }

            return response()->json(['resultCode' => 0, 'message' => 'Confirm Success']);
        }

        $giao_dich->update(['trang_thai' => 'that_bai']);
        if ($dat_phong->trang_thai === 'dang_cho') {
            $dat_phong->update(['trang_thai' => 'da_huy']);
            GiuPhong::where('dat_phong_id', $dat_phong->id)->delete();
        }
        return response()->json(['resultCode' => 99, 'message' => 'Payment failed']);
    });
}

    public function handleVNPayCallback(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Authentication required'], 401);

        Log::info('DEBUG deposit_percentage from request', [
            'deposit_percentage' => $request->input('deposit_percentage'),
            'all_inputs' => $request->except(['_token'])
        ]);
        Log::info('VNPAY Callback Received', $request->all());

        $inputData = collect($request->all())
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))
            ->toArray();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $hashData  = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $localHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY Signature Check', [
            'hashData'   => $hashData,
            'localHash'  => $localHash,
            'remoteHash' => strtoupper($vnp_SecureHash),
            'match'      => ($localHash === strtoupper($vnp_SecureHash)),
        ]);

        if ($localHash !== strtoupper($vnp_SecureHash)) {
            return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
        }

        $rawRef = $inputData['vnp_TxnRef'] ?? '';
        $txnId  = (int) explode('-', $rawRef)[0];

        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount       = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($txnId);
        if (!$giao_dich) {
            return view('payment.fail', ['code' => '01', 'message' => 'Không tìm thấy giao dịch']);
        }

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) {
            return view('payment.fail', ['code' => '02', 'message' => 'Không tìm thấy đơn đặt phòng']);
        }

        // Check if booking has been cancelled by admin/staff
        if ($dat_phong->trang_thai === 'da_huy') {
            $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Đơn đặt phòng đã bị hủy bởi quản trị viên']);
            return view('payment.fail', [
                'code' => '98',
                'message' => 'Đơn đặt phòng đã bị hủy. Vui lòng liên hệ quản trị viên để được hỗ trợ.'
            ]);
        }

        // Check if transaction is already failed
        if ($giao_dich->trang_thai === 'that_bai') {
            return view('payment.fail', [
                'code' => '99',
                'message' => 'Giao dịch đã thất bại trước đó. ' . ($giao_dich->ghi_chu ?? '')
            ]);
        }

        $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : json_decode($dat_phong->snapshot_meta, true);
        $roomsCount = $meta['rooms_count'] ?? 1;

        return DB::transaction(function () use (
            $vnp_ResponseCode,
            $vnp_Amount,
            $inputData,
            $giao_dich,
            $dat_phong,
            $roomsCount,
            $meta
        ) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai'       => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai'    => 'da_xac_nhan',
                    'can_xac_nhan'  => true,
                ]);

                // Ghi nhận sử dụng voucher nếu có
                if (!empty($dat_phong->voucher_id)) {
                    VoucherUsage::firstOrCreate(
                        [
                            'dat_phong_id' => $dat_phong->id,
                            'voucher_id'   => $dat_phong->voucher_id,
                        ],
                        [
                            'nguoi_dung_id'  => $dat_phong->nguoi_dung_id,
                            'amount' => $dat_phong->voucher_discount ?? 0,
                            'used_at'  => now(),
                        ]
                    );
                }

                $giu_phongs       = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                // AUTO-DISTRIBUTE adults and children separately
                $totalAdults = ($meta['computed_adults'] ?? 0);
                $totalChildren = ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / $roomsCount);
                $extraAdults = $totalAdults % $roomsCount;
                $baseChildrenPerRoom = floor($totalChildren / $roomsCount);
                $extraChildren = $totalChildren % $roomsCount;

                foreach ($giu_phongs as $index => $giu_phong) {
                    $meta_item = is_string($giu_phong->meta)
                        ? json_decode($giu_phong->meta, true)
                        : $giu_phong->meta;
                    if (!is_array($meta_item)) {
                        $meta_item = [];
                    }

                    $nights = $meta_item['nights']
                        ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    
                    // Calculate guests for THIS room (fair distribution)
                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;
                    
                    // Get room and its base price
                    $phong = $giu_phong->phong_id ? \App\Models\Phong::find($giu_phong->phong_id) : null;
                    $capacity = $phong ? ($phong->suc_chua ?? 2) : 2;
                    $basePrice = $phong ? ($phong->gia_cuoi_cung ?? 0) : 0;
                    
                    // Adults fill capacity FIRST (no surcharge)
                    // Children overflow to extra slots (with surcharge)
                    $adultsInCapacity = min($adultsInRoom, $capacity);
                    $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                    
                    // CRITICAL FIX: Use different variable names to avoid collision
                    // with $extraAdults and $extraChildren from distribution logic (line 1152-1154)
                    $extraAdultsThisRoom = $adultsInRoom - $adultsInCapacity;
                    $extraChildrenThisRoom = $childrenInRoom - $childrenInCapacity;
                    
                    $extraAdultsCharge = $extraAdultsThisRoom * self::ADULT_PRICE;
                    $extraChildrenCharge = $extraChildrenThisRoom * self::CHILD_PRICE;
                    $extraCharge = $extraAdultsCharge + $extraChildrenCharge;
                    
                    // Final price = base + surcharge
                    $price_per_night = $basePrice + $extraCharge;

                    $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id'       => $dat_phong->id,
                        'phong_id'           => $giu_phong->phong_id ?? null,
                        'loai_phong_id'      => $giu_phong->loai_phong_id,
                        'so_dem'             => $nights,
                        'so_luong'           => $giu_phong->so_luong ?? 1,
                        'so_nguoi_o'         => $guestsInRoom,
                        'number_child'       => $extraChildrenThisRoom,   // Extra children with surcharge
                        'number_adult'       => $extraAdultsThisRoom,      // Extra adults with surcharge
                        'gia_tren_dem'       => $price_per_night,
                        'tong_item'          => $price_per_night * $nights * ($giu_phong->so_luong ?? 1),
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload'      => $itemPayload,
                    ]);
                    \App\Models\DatPhongItem::create($itemPayload);

                    if ($giu_phong->phong_id) {
                        $phongIdsToOccupy[] = $giu_phong->phong_id;
                    }

                    $giu_phong->delete();
                }

                if (!empty($phongIdsToOccupy)) {
                    Phong::whereIn('id', array_unique($phongIdsToOccupy))
                        ->update(['trang_thai' => 'dang_o']);
                }

                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)
                        ->queue(new PaymentSuccess($dat_phong, $dat_phong->nguoiDung->name));
                }

                // Gửi thông báo thanh toán cọc hoặc thanh toán toàn bộ
                $totalPaid = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
                $notificationService = new PaymentNotificationService();
                if ($totalPaid >= $dat_phong->tong_tien) {
                    // Thanh toán toàn bộ
                    $notificationService->sendFullPaymentNotification($dat_phong, $giao_dich);
                } else {
                    // Thanh toán cọc
                    $notificationService->sendDepositPaymentNotification($dat_phong, $giao_dich);
                }

                return view('payment.success', compact('dat_phong'));
            }

            $giao_dich->update([
                'trang_thai' => 'that_bai',
                'ghi_chu'    => 'Mã lỗi: ' . $vnp_ResponseCode,
            ]);
            if ($dat_phong->trang_thai === 'dang_cho') {
                $dat_phong->update(['trang_thai' => 'da_huy']);
                GiuPhong::where('dat_phong_id', $dat_phong->id)->delete();
            }
            if ($dat_phong->nguoiDung) {
                Mail::to($dat_phong->nguoiDung->email)
                    ->queue(new PaymentFail($dat_phong, $vnp_ResponseCode));
            }
            return view('payment.fail', ['code' => $vnp_ResponseCode]);
        });
    }

    public function handleIpn(Request $request)
    {
        Log::info('VNPAY IPN Received', $request->all());

        $inputData          = collect($request->all())->toArray();
        $receivedSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData       = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $calculatedHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY IPN Signature Check', [
            'hashData'       => $hashData,
            'calculatedHash' => $calculatedHash,
            'receivedHash'   => strtoupper($receivedSecureHash),
            'match'          => ($calculatedHash === strtoupper($receivedSecureHash)),
        ]);

        if ($calculatedHash !== strtoupper($receivedSecureHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $rawRef = $inputData['vnp_TxnRef'] ?? '';
        $txnId  = (int) explode('-', $rawRef)[0];

        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount       = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($txnId);
        if (!$giao_dich) {
            return response()->json(['RspCode' => '01', 'Message' => 'Transaction not found']);
        }

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) {
            return response()->json(['RspCode' => '02', 'Message' => 'Booking not found']);
        }

        $meta       = is_array($dat_phong->snapshot_meta)
            ? $dat_phong->snapshot_meta
            : json_decode($dat_phong->snapshot_meta, true);
        $roomsCount = $meta['rooms_count'] ?? 1;

        return DB::transaction(function () use (
            $giao_dich,
            $dat_phong,
            $vnp_ResponseCode,
            $vnp_Amount,
            $inputData,
            $roomsCount
        ) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai'       => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai'   => 'da_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

                // Ghi nhận sử dụng voucher nếu có
                if (!empty($dat_phong->voucher_id)) {
                    VoucherUsage::firstOrCreate(
                        [
                            'dat_phong_id' => $dat_phong->id,
                            'voucher_id'   => $dat_phong->voucher_id,
                        ],
                        [
                            'user_id'  => $dat_phong->nguoi_dung_id,
                            'discount' => $dat_phong->voucher_discount ?? 0,
                            'used_at'  => now(),
                        ]
                    );
                }

                $giu_phongs       = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                foreach ($giu_phongs as $giu_phong) {
                    $meta = is_string($giu_phong->meta)
                        ? json_decode($giu_phong->meta, true)
                        : $giu_phong->meta;
                    if (!is_array($meta)) {
                        $meta = [];
                    }

                    $nights = $meta['nights']
                        ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    $price_per_night = $meta['final_per_night']
                        ?? ($dat_phong->tong_tien / max(1, $nights * $roomsCount));

                    $specSignatureHash = $meta['spec_signature_hash'] ?? $meta['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id'       => $dat_phong->id,
                        'phong_id'           => $giu_phong->phong_id ?? null,
                        'loai_phong_id'      => $giu_phong->loai_phong_id,
                        'so_dem'             => $nights,
                        'so_luong'           => $giu_phong->so_luong ?? 1,
                        'gia_tren_dem'       => $price_per_night,
                        'tong_item'          => $price_per_night * $nights * ($giu_phong->so_luong ?? 1),
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload'      => $itemPayload,
                    ]);
                    \App\Models\DatPhongItem::create($itemPayload);

                    if ($giu_phong->phong_id) {
                        $phongIdsToOccupy[] = $giu_phong->phong_id;
                    }

                    $giu_phong->delete();
                }

                if (!empty($phongIdsToOccupy)) {
                    Phong::whereIn('id', array_unique($phongIdsToOccupy))
                        ->update(['trang_thai' => 'dang_o']);
                }

                // Gửi thông báo thanh toán cọc hoặc thanh toán toàn bộ
                $totalPaid = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
                $notificationService = new PaymentNotificationService();
                if ($totalPaid >= $dat_phong->tong_tien) {
                    // Thanh toán toàn bộ
                    $notificationService->sendFullPaymentNotification($dat_phong, $giao_dich);
                } else {
                    // Thanh toán cọc
                    $notificationService->sendDepositPaymentNotification($dat_phong, $giao_dich);
                }

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

            $giao_dich->update(['trang_thai' => 'that_bai']);
            if ($dat_phong->trang_thai === 'dang_cho') {
                $dat_phong->update(['trang_thai' => 'da_huy']);
                GiuPhong::where('dat_phong_id', $dat_phong->id)->delete();
            }
            return response()->json(['RspCode' => '99', 'Message' => 'Payment failed']);
        });
    }

    public function pendingPayments()
    {
        $pendingPayments = DatPhong::with(['nguoiDung', 'giaoDichs'])
            ->whereIn('trang_thai', ['dang_cho_xac_nhan', 'dang_cho'])
            ->where(function ($q) {
                $q->where('can_xac_nhan', true)
                    ->orWhere('can_thanh_toan', true);
            })
            ->whereHas('giaoDichs', fn($q) => $q->whereIn('trang_thai', ['thanh_cong', 'dang_cho']))
            ->orderByDesc('updated_at')
            ->get();

        return view('payment.pending_payments', compact('pendingPayments'));
    }

    public function simulateCallback()
    {
        $testData = [
            "vnp_Amount"       => 200000000,
            "vnp_BankCode"     => "NCB",
            "vnp_Command"      => "pay",
            "vnp_CreateDate"   => now()->format('YmdHis'),
            "vnp_CurrCode"     => "VND",
            "vnp_IpAddr"       => request()->ip(),
            "vnp_Locale"       => "vn",
            "vnp_OrderInfo"    => "Thanh toan don hang test",
            "vnp_OrderType"    => "billpayment",
            "vnp_TmnCode"      => env('VNPAY_TMN_CODE'),
            "vnp_TxnRef"       => "TESTSIMULATE001",
            "vnp_ResponseCode" => "00",
            "vnp_TransactionNo" => "999999",
            "vnp_PayDate"      => now()->format('YmdHis'),
        ];

        ksort($testData);
        $hashData                  = http_build_query($testData, '', '&', PHP_QUERY_RFC1738);
        $testData["vnp_SecureHash"] = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        return redirect()->route('payment.callback', $testData);
    }

    public function createPayment(Request $request)
    {
        $dat_phong_id = $request->input('dat_phong_id');
        $dat_phong    = DatPhong::findOrFail($dat_phong_id);

        if ($dat_phong->nguoi_dung_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền thanh toán đơn này.');
        }
        if ($dat_phong->trang_thai !== 'dang_cho') {
            abort(400, 'Đơn này không ở trạng thái chờ thanh toán.');
        }

        return DB::transaction(function () use ($dat_phong, $request) {
            $giao_dich = GiaoDich::create([
                'dat_phong_id' => $dat_phong->id,
                'nha_cung_cap' => 'vnpay',
                'so_tien'      => $dat_phong->tong_tien,
                'don_vi'       => $dat_phong->don_vi_tien ?? 'VND',
                'trang_thai'   => 'dang_cho',
                'ghi_chu'      => 'Thanh toán đặt cọc phòng:' . $dat_phong->id,
            ]);

            $vnp_TmnCode    = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $vnp_Url        = env('VNPAY_URL');
            $vnp_ReturnUrl  = env('VNPAY_RETURN_URL');

            $vnp_TxnRef    = $giao_dich->id . '-' . time();
            $vnp_OrderInfo = 'Thanh toán đơn đặt phòng #' . $dat_phong->id;
            $vnp_OrderType = 'billpayment';
            $vnp_Amount    = $dat_phong->tong_tien * 100;
            $vnp_Locale    = 'vn';
            $vnp_IpAddr    = $request->ip();

            $inputData = [
                "vnp_Version"   => "2.1.0",
                "vnp_TmnCode"   => $vnp_TmnCode,
                "vnp_Amount"    => $vnp_Amount,
                "vnp_Command"   => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode"  => "VND",
                "vnp_IpAddr"    => $vnp_IpAddr,
                "vnp_Locale"    => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_ReturnUrl,
                "vnp_TxnRef"    => $vnp_TxnRef,
                "vnp_BankCode"  => 'NCB',
            ];

            ksort($inputData);
            $hashData      = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
            $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

            $vnp_Url .= '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;

            return redirect()->away($vnp_Url);
        });
    }

    public function initiateRemainingPayment(Request $request, $dat_phong_id)
    {
        $request->validate(['nha_cung_cap' => 'required|in:tien_mat,vnpay,momo']);

        $booking = DatPhong::with(['giaoDichs', 'nguoiDung'])
            ->lockForUpdate()
            ->findOrFail($dat_phong_id);

        if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
            return back()->with('error', 'Booking không hợp lệ để thanh toán phần còn lại.');
        }

        // Kiểm tra CCCD trước khi thanh toán
        $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true) ?? [];
        $cccdList = $meta['checkin_cccd_list'] ?? [];
        $hasCCCD = !empty($cccdList) && is_array($cccdList) && count($cccdList) > 0;

        // Backward compatibility
        if (!$hasCCCD) {
            $hasOldCCCD = !empty($meta['checkin_cccd_front']) || !empty($meta['checkin_cccd_back']) || !empty($meta['checkin_cccd']);
            if (!$hasOldCCCD) {
                return back()->with('error', 'Vui lòng upload ảnh CCCD/CMND (mặt trước và mặt sau) trước khi thanh toán.');
            }
        }

        $paid      = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
        $remaining = $booking->tong_tien - $paid;

        if ($remaining <= 0) {
            return back()->with('error', 'Đã thanh toán đủ, không cần thanh toán thêm.');
        }

        $transaction = DB::transaction(function () use ($booking, $remaining, $request) {
            $nhaCungCap = $request->nha_cung_cap;
            $trangThai  = $nhaCungCap === 'tien_mat' ? 'thanh_cong' : 'dang_cho';

            $giaoDich = GiaoDich::create([
                'dat_phong_id' => $booking->id,
                'nha_cung_cap' => $nhaCungCap,
                'so_tien'      => $remaining,
                'don_vi'       => 'VND',
                'trang_thai'   => $trangThai,
                'provider_txn_ref' => null,
                'ghi_chu'      => "Thanh toán phần còn lại booking: {$booking->ma_tham_chieu}",
            ]);

            Log::info('Created remaining payment transaction', [
                'giao_dich_id' => $giaoDich->id,
                'nha_cung_cap' => $giaoDich->nha_cung_cap,
                'so_tien'      => $giaoDich->so_tien,
                'trang_thai'   => $giaoDich->trang_thai,
            ]);

            if ($nhaCungCap === 'tien_mat') {
                $booking->update([
                    'trang_thai' => 'dang_su_dung',
                    'checked_in_at' => now(),
                    'checked_in_by' => Auth::id(),
                ]);

                // Gửi thông báo thanh toán toàn bộ hoặc phần còn lại
                $totalPaid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
                $notificationService = new PaymentNotificationService();
                if ($totalPaid >= $booking->tong_tien) {
                    $notificationService->sendFullPaymentNotification($booking, $giaoDich);
                } else {
                    $notificationService->sendRoomPaymentNotification($booking, $giaoDich);
                }
            }

            return $giaoDich;
        });

        if ($request->nha_cung_cap === 'vnpay') {
            return $this->redirectToVNPay($transaction, $remaining);
        }

        if ($request->nha_cung_cap === 'momo') {
            return $this->redirectToMoMo($transaction, $remaining);
        }

        return redirect()->route('staff.checkin')->with('success', 'Thanh toán tiền mặt thành công. Phòng đã được đưa vào sử dụng.');
    }

    public function handleRemainingCallback(Request $request)
    {
        Log::info('VNPAY Remaining Payment Callback', $request->all());

        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = collect($request->all())
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))
            ->toArray();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData  = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $localHash = strtoupper(hash_hmac('sha512', $hashData, $vnp_HashSecret));

        Log::info('VNPAY Signature Check', [
            'hashData'   => $hashData,
            'localHash'  => $localHash,
            'remoteHash' => strtoupper($vnp_SecureHash),
            'match'      => ($localHash === strtoupper($vnp_SecureHash)),
        ]);

        if ($localHash !== strtoupper($vnp_SecureHash)) {
            Log::error('VNPAY signature mismatch');
            return redirect()->route('staff.checkin')->with('error', 'Chữ ký không hợp lệ.');
        }

        $rawRef = $inputData['vnp_TxnRef'] ?? '';
        $txnId  = (int) explode('-', $rawRef)[0];

        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount       = ($inputData['vnp_Amount'] ?? 0) / 100;

        Log::info('Looking for transaction', [
            'vnp_TxnRef' => $rawRef,
            'parsed_id'  => $txnId,
        ]);

        $transaction = GiaoDich::find($txnId);
        if (!$transaction) {
            Log::error('Transaction not found', ['vnp_TxnRef' => $rawRef]);
            return redirect()->route('staff.checkin')->with('error', 'Không tìm thấy giao dịch hợp lệ.');
        }

        if ($transaction->nha_cung_cap !== 'vnpay') {
            Log::error('Invalid payment provider', [
                'nha_cung_cap'   => $transaction->nha_cung_cap,
                'transaction_id' => $transaction->id,
            ]);
            return redirect()->route('staff.checkin')->with('error', 'Nhà cung cấp thanh toán không hợp lệ.');
        }

        if ($transaction->trang_thai === 'thanh_cong') {
            return redirect()->route('staff.checkin')
                ->with('success', 'Thanh toán đã được xử lý trước đó.');
        }

        if ($transaction->trang_thai !== 'dang_cho') {
            Log::warning('Transaction not pending', ['status' => $transaction->trang_thai]);
            return redirect()->route('staff.checkin')
                ->with('error', 'Giao dịch không ở trạng thái chờ xử lý.');
        }

        if ($vnp_ResponseCode !== '00') {
            $transaction->update([
                'trang_thai' => 'that_bai',
                'ghi_chu'    => 'VNPay lỗi: ' . $vnp_ResponseCode,
            ]);
            Log::warning('Payment failed', ['response_code' => $vnp_ResponseCode]);
            return redirect()->route('staff.checkin')
                ->with('error', 'Thanh toán thất bại. Mã lỗi: ' . $vnp_ResponseCode);
        }

        if (abs($transaction->so_tien - $vnp_Amount) > 1) {
            Log::error('Amount mismatch', [
                'expected' => $transaction->so_tien,
                'received' => $vnp_Amount,
            ]);
            return redirect()->route('staff.checkin')->with('error', 'Số tiền không khớp.');
        }

        return DB::transaction(function () use ($transaction, $inputData) {
            $transaction->update([
                'trang_thai'       => 'thanh_cong',
                'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? null,
                'ghi_chu'          => 'Thanh toán phần còn lại thành công qua VNPAY',
            ]);

            Log::info('Transaction updated to success', [
                'transaction_id' => $transaction->id,
                'provider_txn_ref' => $transaction->provider_txn_ref,
            ]);

            $booking = $transaction->datPhong;
            if (!$booking) {
                Log::error('Booking not found for transaction', [
                    'transaction_id' => $transaction->id,
                ]);
                return redirect()->route('staff.checkin')
                    ->with('success', 'Thanh toán đặt phòng thành công.');
            }

            Log::info('Current booking status BEFORE update', [
                'booking_id'    => $booking->id,
                'current_status' => $booking->trang_thai,
                'ma_tham_chieu' => $booking->ma_tham_chieu,
            ]);

            $totalPaid = $booking->giaoDichs()
                ->where('trang_thai', 'thanh_cong')
                ->sum('so_tien');

            Log::info('Payment calculation', [
                'booking_id'     => $booking->id,
                'total_paid'     => $totalPaid,
                'total_required' => $booking->tong_tien,
                'fully_paid'     => ($totalPaid >= $booking->tong_tien),
                'remaining'      => $booking->tong_tien - $totalPaid,
            ]);

            if ($totalPaid >= $booking->tong_tien) {
                $oldStatus              = $booking->trang_thai;
                $booking->trang_thai    = 'dang_su_dung';
                $booking->checked_in_at = now();
                $booking->checked_in_by = Auth::id();
                $booking->save();

                Log::info('Booking status updated AFTER save', [
                    'booking_id'    => $booking->id,
                    'old_status'    => $oldStatus,
                    'new_status'    => $booking->trang_thai,
                    'checked_in_at' => $booking->checked_in_at,
                ]);

                $phongIds = $booking->datPhongItems()
                    ->pluck('phong_id')
                    ->filter()
                    ->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_o']);
                    Log::info('Room status updated', [
                        'phong_ids'  => $phongIds,
                        'new_status' => 'dang_o',
                    ]);
                }

                // Gửi thông báo thanh toán toàn bộ
                $notificationService = new PaymentNotificationService();
                $notificationService->sendFullPaymentNotification($booking, $transaction);

                return redirect()->route('staff.checkin')->with('success', 'Thanh toán thành công! Phòng đã được chuyển sang trạng thái đang sử dụng.');
            }

            Log::warning('Payment not complete yet', [
                'booking_id' => $booking->id,
                'paid'       => $totalPaid,
                'required'   => $booking->tong_tien,
            ]);
            return redirect()->route('staff.checkin')
                ->with('success', 'Thanh toán thành công! Còn thiếu ' .
                    number_format($booking->tong_tien - $totalPaid) . ' VND.');
        });
    }

    private function redirectToVNPay(GiaoDich $transaction, float $amount)
    {
        $vnp_TmnCode    = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url        = env('VNPAY_URL');
        $vnp_ReturnUrl  = route('payment.remaining.callback');

        $vnp_TxnRef    = $transaction->id . '-' . time();
        $vnp_OrderInfo = 'Thanh toán phần còn lại booking #' . $transaction->dat_phong_id;
        $vnp_Amount    = $amount * 100;

        $inputData = [
            "vnp_Version"   => "2.1.0",
            "vnp_TmnCode"   => $vnp_TmnCode,
            "vnp_Amount"    => $vnp_Amount,
            "vnp_Command"   => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode"  => "VND",
            "vnp_IpAddr"    => request()->ip(),
            "vnp_Locale"    => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef"    => $vnp_TxnRef,
        ];

        ksort($inputData);
        $hashData      = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;
        return redirect()->away($paymentUrl);
    }

    private function redirectToMoMo(GiaoDich $transaction, float $amount)
    {
        $momoService = new MoMoPaymentService();

        try {
            $paymentData = $momoService->createPaymentUrl([
                'orderId' => $transaction->id,
                'amount' => $amount,
                'orderInfo' => 'Thanh toán phần còn lại booking #' . $transaction->dat_phong_id,
                'returnUrl' => route('payment.momo.remaining.callback'),
                'notifyUrl' => config('services.momo.notify_url'),
                'extraData' => '',
            ]);

            return redirect()->away($paymentData['payUrl']);
        } catch (\Exception $e) {
            Log::error('MoMo redirect error: ' . $e->getMessage());
            return redirect()->route('staff.checkin')->with('error', 'Lỗi kết nối MoMo: ' . $e->getMessage());
        }
    }

    private function calculateNights($ngayNhanPhong, $ngayTraPhong)
    {
        $from = new \DateTime($ngayNhanPhong);
        $to   = new \DateTime($ngayTraPhong);
        return max(1, $from->diff($to)->days);
    }

    /**
     * Đếm số đêm rơi vào cuối tuần (Thứ 6, 7, CN) trong khoảng [from, to)
     */
    private function countWeekendNights(Carbon $fromDate, Carbon $toDate): int
    {
        $cursor = $fromDate->copy()->startOfDay();
        $end    = $toDate->copy()->startOfDay();
        $count  = 0;

        while ($cursor < $end) {
            // ISO day: 5 = Friday, 6 = Saturday, 7 = Sunday
            if (in_array($cursor->dayOfWeekIso, [5, 6, 7], true)) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    private function generateSpecSignatureHash($data, $phong)
    {
        $baseTienNghi = method_exists($phong, 'effectiveTienNghiIds')
            ? $phong->effectiveTienNghiIds()
            : [];
        $selectedAddonIdsArr = $data['addons'] ?? [];
        $mergedTienNghi      = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
        sort($mergedTienNghi, SORT_NUMERIC);
        $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

        $specArray = [
            'loai_phong_id' => $phong->loai_phong_id,
            'tien_nghi'     => $mergedTienNghi,
            'beds'          => $bedSpec,
        ];
        ksort($specArray);
        return md5(json_encode($specArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function computeAvailableRoomsCount(
        int $loaiPhongId,
        Carbon $fromDate,
        Carbon $toDate,
        ?string $requiredSignature = null
    ): int {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd   = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr    = $requestedStart->toDateTimeString();
        $reqEndStr      = $requestedEnd->toDateTimeString();

        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->pluck('id')
            ->toArray();

        if (empty($matchingRoomIds)) {
            return 0;
        }

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('dat_phong_item.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.phong_id')
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('giu_phong.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'meta')) {
            $holdsWithMeta = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.meta')
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) {
                    continue;
                }
                $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int) $pid;
                    }
                }
            }
        }

        $occupiedSpecificIds    = array_unique(array_merge($bookedRoomIds, $heldRoomIds));
        $matchingAvailableIds   = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $matchingAvailableCount = count($matchingAvailableIds);

        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->whereNull('dat_phong_item.phong_id');

            $aggregateBooked = Schema::hasColumn('dat_phong_item', 'so_luong')
                ? (int) $q->sum('dat_phong_item.so_luong')
                : (int) $q->count();
        }

        $aggregateHoldsForSignature = 0;
        if (Schema::hasTable('giu_phong')) {
            $qg = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                );

            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                $qg = $qg->where('giu_phong.spec_signature_hash', $requiredSignature);
                $aggregateHoldsForSignature = Schema::hasColumn('giu_phong', 'so_luong')
                    ? (int) $qg->sum('giu_phong.so_luong')
                    : (int) $qg->count();
            } else {
                $holdsMeta = $qg->whereNotNull('giu_phong.meta')->pluck('giu_phong.meta');
                foreach ($holdsMeta as $metaRaw) {
                    if (!$metaRaw) {
                        continue;
                    }
                    $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                    if (!is_array($decoded)) {
                        continue;
                    }
                    if (
                        isset($decoded['spec_signature_hash'])
                        && $decoded['spec_signature_hash'] === $requiredSignature
                    ) {
                        $aggregateHoldsForSignature += $decoded['rooms_count'] ?? 1;
                    }
                }
            }
        }

        $totalRoomsOfType = 0;
        if (Schema::hasTable('loai_phong') && Schema::hasColumn('loai_phong', 'so_luong_thuc_te')) {
            $totalRoomsOfType = (int) DB::table('loai_phong')
                ->where('id', $loaiPhongId)
                ->value('so_luong_thuc_te');
        }
        if ($totalRoomsOfType <= 0) {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)->count();
        }

        $remainingAcrossType = max(
            0,
            $totalRoomsOfType - $aggregateBooked - $aggregateHoldsForSignature
        );
        $availableForSignature = max(
            0,
            min($matchingAvailableCount, $remainingAcrossType)
        );

        return (int) $availableForSignature;
    }

    private function computeAvailableRoomIds(
        int $loaiPhongId,
        Carbon $fromDate,
        Carbon $toDate,
        int $limit = 1,
        ?string $requiredSignature = null
    ): array {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd   = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr    = $requestedStart->toDateTimeString();
        $reqEndStr      = $requestedEnd->toDateTimeString();

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('dat_phong_item.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.phong_id')
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('giu_phong.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'meta')) {
            $holdsWithMeta = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.meta')
                ->whereRaw(
                    "CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?
                     AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?",
                    [$reqEndStr, $reqStartStr]
                )
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) {
                    continue;
                }
                $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int) $pid;
                    }
                }
            }
        }

        $excluded = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        $query = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->when(!empty($excluded), fn($q) => $q->whereNotIn('id', $excluded))
            ->lockForUpdate()
            ->limit((int) $limit);

        $rows = $query->get(['id']);
        return $rows->pluck('id')->toArray();
    }

    /**
     * Handle MoMo callback for remaining payment
     */
    public function handleMoMoRemainingCallback(Request $request)
    {
        Log::info('MoMo Remaining Payment Callback Received', $request->all());

        $momoService = new MoMoPaymentService();

        // Verify signature
        if (!$momoService->verifySignature($request->all())) {
            return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
        }

        $orderId = $request->input('orderId');
        $resultCode = $request->input('resultCode');
        $amount = $request->input('amount');

        // Find transaction
        $giao_dich = GiaoDich::find($orderId);
        if (!$giao_dich) {
            return view('payment.fail', ['code' => '98', 'message' => 'Không tìm thấy giao dịch']);
        }

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) {
            return view('payment.fail', ['code' => '99', 'message' => 'Không tìm thấy đặt phòng']);
        }


        return DB::transaction(function () use ($resultCode, $amount, $request, $giao_dich, $dat_phong) {
            // Debug log
            Log::info('MoMo remaining payment processing', [
                'resultCode' => $resultCode,
                'amount_from_momo' => $amount,
                'amount_in_db' => $giao_dich->so_tien,
                'amounts_match' => ((float)$giao_dich->so_tien == (float)$amount),
            ]);

            if ($resultCode == 0 && (float)$giao_dich->so_tien == (float)$amount) {
                // Payment successful
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $request->input('transId', ''),
                ]);

                // Update booking - mark as fully paid
                $dat_phong->update([
                    'can_thanh_toan' => false,
                ]);

                Log::info('MoMo remaining payment successful', [
                    'dat_phong_id' => $dat_phong->id,
                    'transaction_id' => $giao_dich->id,
                ]);

                return redirect()->route('staff.checkin')->with('success', 'Thanh toán thành công! Khách hàng đã thanh toán đủ.');
            } else {
                // Payment failed
                $giao_dich->update([
                    'trang_thai' => 'that_bai',
                    'ghi_chu' => 'Mã lỗi: ' . $resultCode,
                ]);

                Log::warning('MoMo remaining payment failed', [
                    'dat_phong_id' => $dat_phong->id,
                    'error_code' => $resultCode,
                ]);

                return view('payment.fail', ['code' => $resultCode, 'message' => $request->input('message', 'Thanh toán thất bại')]);
            }
        });
    }
}
