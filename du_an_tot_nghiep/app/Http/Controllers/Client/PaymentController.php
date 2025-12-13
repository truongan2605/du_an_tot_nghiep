<?php

namespace App\Http\Controllers\Client;

use App\Models\VoucherUsage;
use Carbon\Carbon;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\GiuPhong;
use App\Models\DatPhongItem;
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
            // --- If client passes rooms[] (multi-type), handle separate validation ---
            $roomsInput = $request->input('rooms');
            if (is_array($roomsInput) && count($roomsInput) > 0) {
                // Validate common fields required for any payment
                $baseRules = [
                    'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                    'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                    'amount' => 'required|numeric|min:1',
                    'total_amount' => 'required|numeric|min:1',
                    'deposit_percentage' => 'nullable|in:50,100',
                    'phuong_thuc' => 'required|in:vnpay',
                    'name' => 'required|string|max:255|min:2',
                    'address' => 'required|string|max:500|min:5',
                    'phone' => [
                        'required',
                        'string',
                        'regex:/^0[1-9]\d{8,9}$/',
                    ],
                    'voucher_id' => 'nullable|integer',
                    'voucher_discount' => 'nullable|numeric|min:0',
                    'ma_voucher' => 'nullable|string|max:50',
                    'deposit_amount' => 'nullable|numeric|min:0',
                ];

                // Validate rooms structure (loai_phong_id and rooms_count required)
                $rooms = $roomsInput;
                $roomRules = [];
                foreach ($rooms as $i => $room) {
                    $roomRules["rooms.{$i}.loai_phong_id"] = 'required|integer|exists:loai_phong,id';
                    $roomRules["rooms.{$i}.rooms_count"] = 'required|integer|min:1';
                    $roomRules["rooms.{$i}.phong_id"] = 'nullable|integer|exists:phong,id';
                    $roomRules["rooms.{$i}.adults"] = 'nullable|integer|min:0';
                    $roomRules["rooms.{$i}.children"] = 'nullable|integer|min:0';
                    $roomRules["rooms.{$i}.children_ages"] = 'nullable|array';
                    $roomRules["rooms.{$i}.children_ages.*"] = 'integer|min:0|max:12';
                    $roomRules["rooms.{$i}.addons"] = 'nullable|array';
                    $roomRules["rooms.{$i}.addons.*"] = 'integer|exists:tien_nghi,id';
                }

                $validator = \Illuminate\Support\Facades\Validator::make(
                    array_merge($request->all(), ['rooms' => $rooms]),
                    array_merge($baseRules, $roomRules)
                );

                if ($validator->fails()) {
                    Log::warning('VNPay initiate rooms validation failed', ['errors' => $validator->errors()->all()]);
                    return response()->json(['error' => 'Dữ liệu không hợp lệ: ' . implode('; ', $validator->errors()->all())], 422);
                }

                // Validate deposit vs total_amount same as legacy logic
                $depositPercentage = $request->filled('deposit_percentage') ? (int)$request->input('deposit_percentage') : 50;
                if ($depositPercentage < 100) {
                    $expectedDepositRaw = $request->input('total_amount') * ($depositPercentage / 100);
                    $expectedDepositRounded = ceil($expectedDepositRaw / 1000) * 1000;
                    $tolerance = 2000;
                    if (abs($request->input('amount') - $expectedDepositRounded) > $tolerance) {
                        return response()->json(['error' => "Deposit không hợp lệ (phải là {$depositPercentage}% tổng tiền)."], 400);
                    }
                } else {
                    $expectedTotalRounded = ceil($request->input('total_amount') / 1000) * 1000;
                    $tolerance = 2000;
                    if (abs($request->input('amount') - $expectedTotalRounded) > $tolerance) {
                        return response()->json(['error' => "Số tiền thanh toán không hợp lệ."], 400);
                    }
                }

                try {
                    $groups = $request->input('rooms', []);
                    if (!is_array($groups) || empty($groups)) {
                        throw new \Exception('rooms payload empty or invalid');
                    }

                    $from = Carbon::parse($request->input('ngay_nhan_phong'));
                    $to   = Carbon::parse($request->input('ngay_tra_phong'));
                    $nights = $this->calculateNights($request->input('ngay_nhan_phong'), $request->input('ngay_tra_phong'));

                    // total rooms count across groups (fallback to rooms_count if front-end uses it per-group)
                    $totalRoomsCount = 0;
                    foreach ($groups as $g) {
                        $totalRoomsCount += (int) ($g['rooms_count'] ?? $g['so_luong'] ?? 0);
                    }
                    if ($totalRoomsCount <= 0) {
                        $totalRoomsCount = (int) $request->input('rooms_count', 1);
                    }

                    // build snapshotMeta minimal for downstream flows
                    $totalAmount = (float) $request->input('total_amount', 0);
                    $finalPerNight = $nights > 0 ? ($totalAmount / max(1, $nights)) : 0;

                    // --- Nếu client gửi top-level phong_id, gắn vào nhóm phù hợp ---
                    $globalPhongId = $request->input('phong_id') ? (int)$request->input('phong_id') : null;
                    if ($globalPhongId) {
                        $p = \App\Models\Phong::find($globalPhongId);
                        if ($p) {
                            $matched = false;
                            foreach ($groups as $idx => $g) {
                                $gLoai = (int) ($g['loai_phong_id'] ?? ($g['loai_phong'] ?? 0));
                                if ($gLoai === (int)$p->loai_phong_id) {
                                    if (empty($groups[$idx]['selected_phong_ids'])) $groups[$idx]['selected_phong_ids'] = [];
                                    if (!in_array($globalPhongId, $groups[$idx]['selected_phong_ids'], true)) {
                                        $groups[$idx]['selected_phong_ids'][] = $globalPhongId;
                                        if (empty($groups[$idx]['rooms_count']) || $groups[$idx]['rooms_count'] < 1) {
                                            $groups[$idx]['rooms_count'] = 1;
                                        }
                                    }
                                    $matched = true;
                                    break;
                                }
                            }
                            if (!$matched) {
                                // tạo nhóm mới để chứa phòng đã chọn (1 phòng)
                                $groups[] = [
                                    'loai_phong_id' => $p->loai_phong_id,
                                    'rooms_count' => 1,
                                    'selected_phong_ids' => [$globalPhongId],
                                ];
                            }
                        }
                    }


                    $snapshotMeta = [
                        'rooms_count' => $totalRoomsCount,
                        'nights' => $nights,
                        'final_per_night' => $finalPerNight,
                        'tong_tien_truoc_voucher' => $totalAmount,
                        'voucher_id' => $request->input('voucher_id') ?? null,
                        'voucher_discount' => $request->input('voucher_discount') ?? 0,
                        'phuong_thuc' => $request->input('phuong_thuc'),
                        'contact_name' => $request->input('name'),
                        'contact_address' => $request->input('address'),
                        'contact_phone' => $request->input('phone'),
                        'groups' => $groups, // keep original groups for helper meta parsing
                    ];

                    // build datPhongData for DatPhong::create()
                    $maThamChieu = 'DP' . strtoupper(Str::random(8));
                    $datPhongData = [
                        'ma_tham_chieu' => $maThamChieu,
                        'nguoi_dung_id' => Auth::id(),
                        // no single phong_id in multi-type booking
                        'phong_id' => null,
                        'ngay_nhan_phong' => $request->input('ngay_nhan_phong'),
                        'ngay_tra_phong'  => $request->input('ngay_tra_phong'),
                        'tong_tien'       => $totalAmount,
                        'deposit_amount'  => $request->input('amount'),
                        'so_khach'        => $request->input('so_khach') ?? null,
                        'trang_thai'      => 'dang_cho',
                        'can_thanh_toan'  => ((int)($request->input('deposit_percentage', 50)) < 100),
                        'can_xac_nhan'    => false,
                        'created_by'      => Auth::id(),
                        'snapshot_meta'   => $snapshotMeta, // helper will merge/overwrite as needed
                        'phuong_thuc'     => $request->input('phuong_thuc'),
                        'contact_name'    => $request->input('name'),
                        'contact_address' => $request->input('address'),
                        'contact_phone'   => $request->input('phone'),
                        'voucher_id'      => $request->input('voucher_id') ?? null,
                        'voucher_discount' => $request->input('voucher_discount') ?? 0,
                        'ma_voucher'      => $request->input('ma_voucher') ?? null,
                    ];

                    // finally call helper with correct args
                    $dat_phong = $this->createBookingFromRooms(
                        $groups,
                        $datPhongData,
                        $snapshotMeta,
                        $from,
                        $to,
                        (int)$nights
                    );
                } catch (\Exception $ex) {
                    Log::error('createBookingFromRooms failed: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
                    return response()->json(['error' => 'Không thể tạo booking từ rooms: ' . $ex->getMessage()], 400);
                }


                // Create transaction and VNPay URL (same as legacy flow)
                $giao_dich = GiaoDich::create([
                    'dat_phong_id' => $dat_phong->id,
                    'nha_cung_cap' => 'vnpay',
                    'so_tien'      => $request->input('amount'),
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
                    "vnp_Amount"    => $request->input('amount') * 100,
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
            } // end rooms[] branch

            // --- Legacy / single-room flow (phong_id) ---
            // Validate input data (original rules)
            $validated = $request->validate([
                'phong_id' => 'required|exists:phong,id',
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                'amount' => 'required|numeric|min:1',
                'total_amount' => 'required|numeric|min:1',
                'deposit_percentage' => 'nullable|in:50,100',
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
                    'regex:/^0[1-9]\d{8,9}$/',
                ],
                'voucher_id'        => 'nullable|integer',
                'voucher_discount'  => 'nullable|numeric|min:0',
                'ma_voucher'        => 'nullable|string|max:50',
            ]);

            // Default to 50% if not provided (radio button not submitted)
            $depositPercentage = isset($validated['deposit_percentage'])
                ? (int) $validated['deposit_percentage']
                : 50;

            // Validate amount vs total_amount (client gửi total_amount sau voucher)
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
                $validated,
                $maThamChieu,
                $snapshotMeta,
                $phong,
                $request,
                $from,
                $to,
                $nights,
                $roomsCount,
                $finalPerNightServer,
                $snapshotTotalServer,
                $selectedAddons,
                $depositPercentage,
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
            // return response()->json(['error' => 'Invalid payment flow. Provide either rooms[] or phong_id.'], 400);
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
            // If an existing booking id provided -> use existing booking flow (unchanged)
            $existingBookingId = $request->input('dat_phong_id');

            if ($existingBookingId) {
                $dat_phong = DatPhong::findOrFail($existingBookingId);

                if ($dat_phong->nguoi_dung_id !== Auth::id()) {
                    return response()->json(['error' => 'Unauthorized'], 403);
                }

                Log::info('Using existing booking for MoMo', ['dat_phong_id' => $dat_phong->id]);

                // prevent duplicate pending momo transaction
                $existingTransaction = GiaoDich::where('dat_phong_id', $dat_phong->id)
                    ->where('nha_cung_cap', 'momo')
                    ->where('trang_thai', 'dang_cho')
                    ->first();

                if ($existingTransaction) {
                    Log::info('Found existing pending MoMo transaction - marking as failed and creating new', [
                        'old_transaction_id' => $existingTransaction->id
                    ]);
                    $existingTransaction->update([
                        'trang_thai' => 'that_bai',
                        'ghi_chu' => 'Replaced by new payment attempt',
                    ]);
                }

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

                $momoService = new MoMoPaymentService();
                $paymentData = $momoService->createPaymentUrl([
                    'orderId' => (string)$giao_dich->id,
                    'amount' => (int)$request->input('amount'),
                    'orderInfo' => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    'returnUrl' => config('services.momo.return_url'),
                    'notifyUrl' => config('services.momo.notify_url'),
                    'extraData' => '',
                ]);

                $metadata = json_decode($giao_dich->metadata, true) ?? [];
                $metadata['momo_order_id'] = $paymentData['orderId'] ?? null;
                $giao_dich->update(['metadata' => json_encode($metadata)]);

                Log::info('MoMo payment initiated', [
                    'transaction_id' => $giao_dich->id,
                    'momo_order_id' => $paymentData['orderId'] ?? null,
                    'booking_id' => $dat_phong->id,
                ]);

                return response()->json([
                    'redirect_url' => $paymentData['payUrl'] ?? null,
                    'dat_phong_id' => $dat_phong->id
                ]);
            }

            // --- Multi-type (rooms[]) flow ---
            if ($request->filled('rooms') && is_array($request->input('rooms'))) {
                // Basic common validation for payment + contact
                $baseRules = [
                    'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                    'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                    'amount' => 'required|numeric|min:1',
                    'total_amount' => 'required|numeric|min:1',
                    'deposit_percentage' => 'nullable|in:50,100',
                    'phuong_thuc' => 'required|in:momo',
                    'name' => 'required|string|max:255|min:2',
                    'address' => 'required|string|max:500|min:5',
                    'phone' => [
                        'required',
                        'string',
                        'regex:/^0[1-9]\d{8,9}$/',
                    ],
                    'voucher_id' => 'nullable|integer',
                    'voucher_discount' => 'nullable|numeric|min:0',
                    'ma_voucher' => 'nullable|string|max:50',
                ];

                $rooms = $request->input('rooms');
                $roomRules = [];
                foreach ($rooms as $i => $room) {
                    $roomRules["rooms.{$i}.loai_phong_id"] = 'required|integer|exists:loai_phong,id';
                    $roomRules["rooms.{$i}.rooms_count"] = 'required|integer|min:1';
                    $roomRules["rooms.{$i}.phong_id"] = 'nullable|integer|exists:phong,id';
                    $roomRules["rooms.{$i}.adults"] = 'nullable|integer|min:0';
                    $roomRules["rooms.{$i}.children"] = 'nullable|integer|min:0';
                    $roomRules["rooms.{$i}.children_ages"] = 'nullable|array';
                    $roomRules["rooms.{$i}.children_ages.*"] = 'integer|min:0|max:12';
                    $roomRules["rooms.{$i}.addons"] = 'nullable|array';
                    $roomRules["rooms.{$i}.addons.*"] = 'integer|exists:tien_nghi,id';
                }

                $validator = \Illuminate\Support\Facades\Validator::make(
                    array_merge($request->all(), ['rooms' => $rooms]),
                    array_merge($baseRules, $roomRules)
                );

                if ($validator->fails()) {
                    Log::warning('MoMo initiate rooms validation failed', ['errors' => $validator->errors()->all()]);
                    return response()->json(['error' => 'Dữ liệu không hợp lệ: ' . implode('; ', $validator->errors()->all())], 422);
                }

                // deposit validation (same rules as elsewhere)
                $depositPercentage = $request->filled('deposit_percentage') ? (int)$request->input('deposit_percentage') : 50;
                if ($depositPercentage < 100) {
                    $expectedDepositRaw = $request->input('total_amount') * ($depositPercentage / 100);
                    $expectedDepositRounded = ceil($expectedDepositRaw / 1000) * 1000;
                    $tolerance = 2000;
                    if (abs($request->input('amount') - $expectedDepositRounded) > $tolerance) {
                        return response()->json(['error' => "Deposit không hợp lệ (phải là {$depositPercentage}% tổng tiền)."], 400);
                    }
                } else {
                    $expectedTotalRounded = ceil($request->input('total_amount') / 1000) * 1000;
                    $tolerance = 2000;
                    if (abs($request->input('amount') - $expectedTotalRounded) > $tolerance) {
                        return response()->json(['error' => "Số tiền thanh toán không hợp lệ."], 400);
                    }
                }

                try {
                    $groups = $request->input('rooms', []);
                    if (!is_array($groups) || empty($groups)) {
                        throw new \Exception('rooms payload empty or invalid');
                    }

                    $from = Carbon::parse($request->input('ngay_nhan_phong'));
                    $to   = Carbon::parse($request->input('ngay_tra_phong'));
                    $nights = $this->calculateNights($request->input('ngay_nhan_phong'), $request->input('ngay_tra_phong'));

                    // total rooms count across groups (fallback to rooms_count if front-end uses it per-group)
                    $totalRoomsCount = 0;
                    foreach ($groups as $g) {
                        $totalRoomsCount += (int) ($g['rooms_count'] ?? $g['so_luong'] ?? 0);
                    }
                    if ($totalRoomsCount <= 0) {
                        $totalRoomsCount = (int) $request->input('rooms_count', 1);
                    }

                    // build snapshotMeta minimal for downstream flows
                    $totalAmount = (float) $request->input('total_amount', 0);
                    $finalPerNight = $nights > 0 ? ($totalAmount / max(1, $nights)) : 0;

                    $snapshotMeta = [
                        'rooms_count' => $totalRoomsCount,
                        'nights' => $nights,
                        'final_per_night' => $finalPerNight,
                        'tong_tien_truoc_voucher' => $totalAmount,
                        'voucher_id' => $request->input('voucher_id') ?? null,
                        'voucher_discount' => $request->input('voucher_discount') ?? 0,
                        'phuong_thuc' => $request->input('phuong_thuc'),
                        'contact_name' => $request->input('name'),
                        'contact_address' => $request->input('address'),
                        'contact_phone' => $request->input('phone'),
                        'groups' => $groups, // keep original groups for helper meta parsing
                    ];

                    $maThamChieu = 'DP' . strtoupper(Str::random(8));
                    $datPhongData = [
                        'ma_tham_chieu' => $maThamChieu,
                        'nguoi_dung_id' => Auth::id(),
                        'phong_id' => null,
                        'ngay_nhan_phong' => $request->input('ngay_nhan_phong'),
                        'ngay_tra_phong'  => $request->input('ngay_tra_phong'),
                        'tong_tien'       => $totalAmount,
                        'deposit_amount'  => $request->input('amount'),
                        'so_khach'        => $request->input('so_khach') ?? null,
                        'trang_thai'      => 'dang_cho',
                        'can_thanh_toan'  => ((int)($request->input('deposit_percentage', 50)) < 100),
                        'can_xac_nhan'    => false,
                        'created_by'      => Auth::id(),
                        'snapshot_meta'   => $snapshotMeta,
                        'phuong_thuc'     => $request->input('phuong_thuc'),
                        'contact_name'    => $request->input('name'),
                        'contact_address' => $request->input('address'),
                        'contact_phone'   => $request->input('phone'),
                        'voucher_id'      => $request->input('voucher_id') ?? null,
                        'voucher_discount' => $request->input('voucher_discount') ?? 0,
                        'ma_voucher'      => $request->input('ma_voucher') ?? null,
                    ];

                    // finally call helper with correct args
                    $dat_phong = $this->createBookingFromRooms(
                        $groups,
                        $datPhongData,
                        $snapshotMeta,
                        $from,
                        $to,
                        (int)$nights
                    );
                } catch (\Exception $ex) {
                    Log::error('createBookingFromRooms failed: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
                    return response()->json(['error' => 'Không thể tạo booking từ rooms: ' . $ex->getMessage()], 400);
                }


                // Create transaction and MoMo payment
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

                $momoService = new MoMoPaymentService();
                $paymentData = $momoService->createPaymentUrl([
                    'orderId' => (string)$giao_dich->id,
                    'amount' => (int)$request->input('amount'),
                    'orderInfo' => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    'returnUrl' => config('services.momo.return_url'),
                    'notifyUrl' => config('services.momo.notify_url'),
                    'extraData' => '',
                ]);

                $metadata = json_decode($giao_dich->metadata, true) ?? [];
                $metadata['momo_order_id'] = $paymentData['orderId'] ?? null;
                $giao_dich->update(['metadata' => json_encode($metadata)]);

                Log::info('MoMo payment initiated (rooms flow)', [
                    'transaction_id' => $giao_dich->id,
                    'momo_order_id' => $paymentData['orderId'] ?? null,
                    'booking_id' => $dat_phong->id,
                ]);

                return response()->json([
                    'redirect_url' => $paymentData['payUrl'] ?? null,
                    'dat_phong_id' => $dat_phong->id,
                ]);
            }

            // If neither dat_phong_id nor rooms[] -> legacy direct creation not supported
            return response()->json(['error' => 'Direct booking creation not supported in this flow. Provide dat_phong_id or rooms[].'], 400);
        } catch (\Throwable $e) {
            Log::error('MoMo initiate error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

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

        $snapshot = $dat_phong->snapshot_meta;
        $meta = is_array($snapshot) ? $snapshot : (is_string($snapshot) && !empty($snapshot) ? json_decode($snapshot, true) : []);
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
                    'can_thanh_toan' => !$isFullPayment,
                ]);

                Log::info('MoMo deposit payment successful', [
                    'dat_phong_id' => $dat_phong->id,
                    'deposit_percentage' => $depositPercentage,
                    'is_full_payment' => $isFullPayment,
                    'can_thanh_toan' => !$isFullPayment,
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

                // Load giu_phong holds
                $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();

                $roomSlots = [];
                foreach ($giu_phongs as $gp) {
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    if (!is_array($meta_item)) $meta_item = [];

                    $count = (int) ($gp->so_luong ?? 1);
                    // Prefer explicit selected_phong_ids in meta
                    $selected = [];
                    if (!empty($meta_item['selected_phong_ids']) && is_array($meta_item['selected_phong_ids'])) {
                        $selected = array_map('intval', $meta_item['selected_phong_ids']);
                    } elseif (!empty($meta_item['selected_phong_id'])) {
                        $selected = [(int)$meta_item['selected_phong_id']];
                    } elseif (!empty($gp->phong_id)) {
                        $selected = [(int)$gp->phong_id];
                    }

                    // Use selected ids first
                    foreach ($selected as $i => $pid) {
                        if (count($roomSlots) >= $roomsCount && $i >= $count) break;
                        // Add one slot per selected id (up to $count)
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => $pid,
                            'loai_phong_id' => $gp->loai_phong_id,
                            'so_luong' => 1,
                        ];
                    }

                    // If selected < count, add remaining slots with phong_id = null
                    $selectedCount = count($selected);
                    for ($i = 0; $i < max(0, $count - $selectedCount); $i++) {
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => null,
                            'loai_phong_id' => $gp->loai_phong_id,
                            'so_luong' => 1,
                        ];
                    }
                }

                // Ensure we only process exactly roomsCount slots (safety)
                if (count($roomSlots) > $roomsCount) {
                    $roomSlots = array_slice($roomSlots, 0, $roomsCount);
                }

                // Distribute adults/children across slots fairly
                $totalAdults = ($meta['computed_adults'] ?? 0);
                $totalChildren = ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / max(1, $roomsCount));
                $extraAdults = $totalAdults % max(1, $roomsCount);
                $baseChildrenPerRoom = floor($totalChildren / max(1, $roomsCount));
                $extraChildren = $totalChildren % max(1, $roomsCount);

                $phongIdsToOccupy = [];
                // Compute fallback price per night if a slot doesn't provide final_per_night
                $nightsGlobal = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                $fallbackPricePerNight = ($meta['final_per_night'] ?? null) ?: ($dat_phong->tong_tien / max(1, $nightsGlobal * $roomsCount));

                // Iterate slots and insert dat_phong_item for each slot (so_luong = 1)
                foreach ($roomSlots as $index => $slot) {
                    $meta_item = is_array($slot['meta']) ? $slot['meta'] : [];
                    $nights = $meta_item['nights'] ?? $nightsGlobal;

                    // price_per_night prefer slot meta final_per_night else fallback
                    $price_per_night = $meta_item['final_per_night'] ?? $fallbackPricePerNight;

                    // guest distribution for this slot index
                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;

                    // Get room capacity if specific phong_id exists
                    $ph = $slot['phong_id'] ? \App\Models\Phong::find($slot['phong_id']) : null;
                    $capacity = $ph ? ($ph->suc_chua ?? 2) : ($meta_item['room_capacity_single'] ?? 2);

                    $adultsInCapacity = min($adultsInRoom, $capacity);
                    $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                    $extraAdultsThisRoom = max(0, $adultsInRoom - $adultsInCapacity);
                    $extraChildrenThisRoom = max(0, $childrenInRoom - $childrenInCapacity);

                    $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                    // Build item payload
                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $slot['phong_id'] ?? null,
                        'loai_phong_id' => $slot['loai_phong_id'],
                        'so_dem' => $nights,
                        'so_luong' => 1,
                        'so_nguoi_o' => $guestsInRoom,
                        'number_child' => $extraChildrenThisRoom,
                        'number_adult' => $extraAdultsThisRoom,
                        'gia_tren_dem' => $price_per_night,
                        'tong_item' => $price_per_night * $nights * 1,
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item (slot)', ['dat_phong_id' => $dat_phong->id, 'payload' => $itemPayload]);
                    DatPhongItem::create($itemPayload);

                    if (!empty($slot['phong_id'])) {
                        $phongIdsToOccupy[] = $slot['phong_id'];
                    }
                }

                // Delete all giu_phong entries after expanding (we already recorded slot info)
                foreach ($giu_phongs as $gp) {
                    try {
                        $gp->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete giu_phong after processing', ['id' => $gp->id, 'error' => $e->getMessage()]);
                    }
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

        $snapshot = $dat_phong->snapshot_meta;
        $meta = is_array($snapshot) ? $snapshot : (is_string($snapshot) && !empty($snapshot) ? json_decode($snapshot, true) : []);
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

                // Lấy tất cả giu_phong liên quan
                $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                // Flatten giu_phong thành các slot (mỗi slot đại diện 1 phòng)
                $roomSlots = []; // mỗi phần tử: ['giu_phong' => $gp, 'meta' => array, 'phong_id' => int|null, 'loai_phong_id' => int]

                foreach ($giu_phongs as $gp) {
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    if (!is_array($meta_item)) $meta_item = [];

                    $count = (int) ($gp->so_luong ?? 1);

                    // Lấy danh sách phòng cụ thể nếu có trong meta
                    $selected = [];
                    if (!empty($meta_item['selected_phong_ids']) && is_array($meta_item['selected_phong_ids'])) {
                        $selected = array_map('intval', $meta_item['selected_phong_ids']);
                    } elseif (!empty($meta_item['selected_phong_id'])) {
                        $selected = [(int)$meta_item['selected_phong_id']];
                    } elseif (!empty($gp->phong_id)) {
                        $selected = [(int)$gp->phong_id];
                    }

                    // Thêm từng selected id như slot trước (ưu tiên)
                    foreach ($selected as $i => $pid) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => $pid,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }

                    // Nếu selected < so_luong thì thêm slot phong_id = null
                    $selectedCount = count($selected);
                    for ($i = 0; $i < max(0, $count - $selectedCount); $i++) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => null,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }
                }

                // Bảo đảm chỉ xử lý đúng roomsCount slot
                if (count($roomSlots) > $roomsCount) {
                    $roomSlots = array_slice($roomSlots, 0, $roomsCount);
                }

                // Phân phối adults/children công bằng theo slots
                $totalAdults = ($meta['computed_adults'] ?? 0);
                $totalChildren = ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / max(1, $roomsCount));
                $extraAdults = $totalAdults % max(1, $roomsCount);
                $baseChildrenPerRoom = floor($totalChildren / max(1, $roomsCount));
                $extraChildren = $totalChildren % max(1, $roomsCount);

                // Fallback giá/đêm nếu slot không có final_per_night
                $nightsGlobal = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                $fallbackPricePerNight = ($meta['final_per_night'] ?? null) ?: ($dat_phong->tong_tien / max(1, $nightsGlobal * $roomsCount));

                // Tạo dat_phong_item cho mỗi slot (so_luong = 1)
                foreach ($roomSlots as $index => $slot) {
                    $meta_item = is_array($slot['meta']) ? $slot['meta'] : [];
                    $nights = $meta_item['nights'] ?? $nightsGlobal;
                    $price_per_night = $meta_item['final_per_night'] ?? $fallbackPricePerNight;

                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;

                    // Lấy capacity nếu có phong_id cụ thể
                    $ph = $slot['phong_id'] ? \App\Models\Phong::find($slot['phong_id']) : null;
                    $capacity = $ph ? ($ph->suc_chua ?? 2) : ($meta_item['room_capacity_single'] ?? 2);

                    $adultsInCapacity = min($adultsInRoom, $capacity);
                    $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                    $extraAdultsThisRoom = max(0, $adultsInRoom - $adultsInCapacity);
                    $extraChildrenThisRoom = max(0, $childrenInRoom - $childrenInCapacity);

                    $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $slot['phong_id'] ?? null,
                        'loai_phong_id' => $slot['loai_phong_id'],
                        'so_dem' => $nights,
                        'so_luong' => 1,
                        'so_nguoi_o' => $guestsInRoom,
                        'number_child' => $extraChildrenThisRoom,
                        'number_adult' => $extraAdultsThisRoom,
                        'gia_tren_dem' => $price_per_night,
                        'tong_item' => $price_per_night * $nights * 1,
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item (IPN slot)', ['dat_phong_id' => $dat_phong->id, 'payload' => $itemPayload]);
                    DatPhongItem::create($itemPayload);

                    if (!empty($slot['phong_id'])) {
                        $phongIdsToOccupy[] = $slot['phong_id'];
                    }
                }

                // Xóa các giu_phong đã xử lý
                foreach ($giu_phongs as $gp) {
                    try {
                        $gp->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete giu_phong after IPN processing', ['id' => $gp->id, 'error' => $e->getMessage()]);
                    }
                }

                if (!empty($phongIdsToOccupy)) {
                    Phong::whereIn('id', array_unique($phongIdsToOccupy))->update(['trang_thai' => 'dang_o']);
                }

                // Gửi notification
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

        $snapshotMeta = $dat_phong->snapshot_meta;
        if (is_array($snapshotMeta)) {
            $meta = $snapshotMeta;
        } elseif (is_string($snapshotMeta) && !empty($snapshotMeta)) {
            $decoded = json_decode($snapshotMeta, true);
            $meta = is_array($decoded) ? $decoded : [];
        } else {
            $meta = [];
        }

        $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();

        // roomsCount prefer snapshot, but fallback to sum so_luong in giu_phong
        $roomsCountFromSnapshot = (int) ($meta['rooms_count'] ?? 0);
        $roomsCountFromHolds = $giu_phongs->sum(function ($gp) {
            return (int) ($gp->so_luong ?? 1);
        });
        $roomsCount = max(1, max($roomsCountFromSnapshot, $roomsCountFromHolds));

        Log::debug('Hold rows before flatten', [
            'dat_phong_id' => $dat_phong->id,
            'roomsCount_snapshot' => $roomsCountFromSnapshot,
            'roomsCount_holds' => $roomsCountFromHolds,
            'roomsCount_used' => $roomsCount,
            'giu_phongs' => $giu_phongs->map(fn($g) => $g->only(['id', 'phong_id', 'loai_phong_id', 'so_luong', 'meta']))->toArray()
        ]);


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
                // If deposit logic needed, respect deposit_percentage in meta
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

                $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                // compute actual total slots from giu_phongs (sum so_luong, default 1)
                $totalSlots = $giu_phongs->reduce(function ($carry, $gp) {
                    return $carry + ((int)($gp->so_luong ?? 1));
                }, 0);
                $totalSlots = max(1, $totalSlots);

                // Use snapshot meta nights or compute fallback
                $nightsGlobal = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);

                // If snapshot final_per_night is provided as total for booking, convert to per-slot:
                // note: in your earlier flows you may store final_per_night as per-booking or per-slot;
                // handle both: if meta['final_per_night'] seems per-booking (>= total/totalslots*nights), divide by totalRoomsCount accordingly
                $metaFinal = $meta['final_per_night'] ?? null;
                $fallbackPricePerNight = null;
                if ($metaFinal) {
                    // if metaFinal likely already per-slot (heuristic): assume if meta.rooms_count exists and > 0 then metaFinal is per-booking? 
                    // Simpler: if meta.rooms_count == $totalSlots assume metaFinal is per-booking total for all rooms -> divide.
                    $metaRoomsCount = (int)($meta['rooms_count'] ?? 0);
                    if ($metaRoomsCount > 0 && $metaRoomsCount !== $totalSlots) {
                        // normalize: treat metaFinal as total-per-night for booking -> divide
                        $fallbackPricePerNight = (float)$metaFinal / max(1, $metaRoomsCount);
                    } else {
                        $fallbackPricePerNight = (float)$metaFinal;
                    }
                }
                if (!$fallbackPricePerNight) {
                    // final fallback: distribute dat_phong->tong_tien across all slots and nights
                    $fallbackPricePerNight = ($dat_phong->tong_tien ?? 0) / max(1, $nightsGlobal * $totalSlots);
                }

                // Build *slots* from giu_phongs: each giu_phong with so_luong produces that many slots.
                // For each giu_phong, if meta contains selected_phong_ids, push them first (respect order), else null slot ids
                $roomSlots = [];
                foreach ($giu_phongs as $gp) {
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    if (!is_array($meta_item)) $meta_item = [];

                    $count = (int) ($gp->so_luong ?? 1);

                    // selected ids priority (from meta)
                    $selected = [];
                    if (!empty($meta_item['selected_phong_ids']) && is_array($meta_item['selected_phong_ids'])) {
                        $selected = array_map('intval', $meta_item['selected_phong_ids']);
                    } elseif (!empty($meta_item['selected_phong_id'])) {
                        $selected = [(int)$meta_item['selected_phong_id']];
                    } elseif (!empty($gp->phong_id)) {
                        $selected = [(int)$gp->phong_id];
                    }

                    // push selected ids as slots (one slot per id)
                    foreach ($selected as $pid) {
                        if (count($roomSlots) >= $totalSlots) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => $pid,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }

                    // fill remaining (count - selectedCount) with null phong_id slots
                    $selectedCount = count($selected);
                    for ($i = 0; $i < max(0, $count - $selectedCount); $i++) {
                        if (count($roomSlots) >= $totalSlots) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => null,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }

                    // if we already reached totalSlots we can break
                    if (count($roomSlots) >= $totalSlots) break;
                }

                // Safety: if roomSlots < totalSlots, try to pad by adding more null slots (shouldn't happen)
                while (count($roomSlots) < $totalSlots) {
                    // take first giu_phong as default
                    $gp = $giu_phongs->first();
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    $roomSlots[] = [
                        'giu_phong' => $gp,
                        'meta' => $meta_item,
                        'phong_id' => null,
                        'loai_phong_id' => $gp->loai_phong_id,
                    ];
                }

                // Now distribute adults/children fairly across $totalSlots
                $totalAdults = (int) ($meta['computed_adults'] ?? 0);
                $totalChildren = (int) ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / max(1, $totalSlots));
                $extraAdults = $totalAdults % max(1, $totalSlots);
                $baseChildrenPerRoom = floor($totalChildren / max(1, $totalSlots));
                $extraChildren = $totalChildren % max(1, $totalSlots);

                // Create dat_phong_item for each slot
                foreach ($roomSlots as $index => $slot) {
                    $meta_item = is_string($slot['meta']) ? json_decode($slot['meta'], true) : ($slot['meta'] ?? []);
                    if (!is_array($meta_item)) $meta_item = [];

                    $nights = $meta_item['nights'] ?? $nightsGlobal;
                    // per-room price should be stored as final_per_night in giu_phong.meta
                    $price_per_night = isset($meta_item['final_per_night']) ? (float) $meta_item['final_per_night'] : $fallbackPricePerNight;

                    // distribute guests
                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;

                    // compute capacity and extras
                    $ph = $slot['phong_id'] ? \App\Models\Phong::find($slot['phong_id']) : null;
                    $capacity = $ph ? ($ph->suc_chua ?? 2) : ($meta_item['room_capacity_single'] ?? 2);

                    $adultsAllowed = min($adultsInRoom, $capacity);
                    $childrenAllowed = min($childrenInRoom, max(0, $capacity - $adultsAllowed));

                    $extraAdultsThisRoom = max(0, $adultsInRoom - $adultsAllowed);
                    $extraChildrenThisRoom = max(0, $childrenInRoom - $childrenAllowed);

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $slot['phong_id'] ?? null,
                        'loai_phong_id' => $slot['loai_phong_id'],
                        'so_dem' => $nights,
                        'so_luong' => 1,
                        'so_nguoi_o' => $guestsInRoom,
                        'number_child' => $extraChildrenThisRoom,
                        'number_adult' => $extraAdultsThisRoom,
                        'gia_tren_dem' => $price_per_night,
                        'tong_item' => ($price_per_night * $nights * 1),
                        'spec_signature_hash' => $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null,
                    ];

                    Log::debug('Inserting dat_phong_item (VNPAY callback slot)', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload'      => $itemPayload,
                    ]);
                    DatPhongItem::create($itemPayload);

                    if (!empty($slot['phong_id'])) {
                        $phongIdsToOccupy[] = $slot['phong_id'];
                    }
                }

                // Sau khi tạo item cho tất cả slots, xóa các giu_phong tương ứng (bảo đảm không xóa nhầm)
                foreach ($giu_phongs as $gp) {
                    try {
                        $gp->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete giu_phong after VNPay callback', ['id' => $gp->id, 'error' => $e->getMessage()]);
                    }
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

                Log::info('VNPay callback meta & giu_phong summary', [
                    'dat_phong_id' => $dat_phong->id,
                    'snapshot_meta_rooms_count' => $meta['rooms_count'] ?? null,
                    'giu_phong_rows' => $giu_phongs->map(fn($g) => [
                        'id' => $g->id,
                        'phong_id' => $g->phong_id,
                        'loai_phong_id' => $g->loai_phong_id,
                        'so_luong' => $g->so_luong,
                        'meta' => is_string($g->meta) ? json_decode($g->meta, true) : $g->meta
                    ]),
                    'computed_total_slots' => $totalSlots,
                    'fallback_price_per_night' => $fallbackPricePerNight
                ]);


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

        $meta = is_array($dat_phong->snapshot_meta)
            ? $dat_phong->snapshot_meta
            : json_decode($dat_phong->snapshot_meta, true);
        $roomsCount = (int) ($meta['rooms_count'] ?? 1);

        return DB::transaction(function () use (
            $giao_dich,
            $dat_phong,
            $vnp_ResponseCode,
            $vnp_Amount,
            $inputData,
            $roomsCount,
            $meta
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

                // Flatten giu_phong => slots (mỗi slot là 1 phòng)
                $roomSlots = []; // mỗi phần tử: ['giu_phong' => $gp, 'meta' => array, 'phong_id' => int|null, 'loai_phong_id' => int]

                foreach ($giu_phongs as $gp) {
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    if (!is_array($meta_item)) $meta_item = [];

                    $count = (int) ($gp->so_luong ?? 1);

                    $selected = [];
                    if (!empty($meta_item['selected_phong_ids']) && is_array($meta_item['selected_phong_ids'])) {
                        $selected = array_map('intval', $meta_item['selected_phong_ids']);
                    } elseif (!empty($meta_item['selected_phong_id'])) {
                        $selected = [(int)$meta_item['selected_phong_id']];
                    } elseif (!empty($gp->phong_id)) {
                        $selected = [(int)$gp->phong_id];
                    }

                    // add selected ids first
                    foreach ($selected as $pid) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => $pid,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }

                    $selectedCount = count($selected);
                    for ($i = 0; $i < max(0, $count - $selectedCount); $i++) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = [
                            'giu_phong' => $gp,
                            'meta' => $meta_item,
                            'phong_id' => null,
                            'loai_phong_id' => $gp->loai_phong_id,
                        ];
                    }
                }

                // ensure only up to roomsCount slots
                if (count($roomSlots) > $roomsCount) {
                    $roomSlots = array_slice($roomSlots, 0, $roomsCount);
                }

                // Distribute adults/children across slots fairly
                $totalAdults = (int) ($meta['computed_adults'] ?? 0);
                $totalChildren = (int) ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / max(1, $roomsCount));
                $extraAdults = $totalAdults % max(1, $roomsCount);
                $baseChildrenPerRoom = floor($totalChildren / max(1, $roomsCount));
                $extraChildren = $totalChildren % max(1, $roomsCount);

                // fallback price per night
                $nightsGlobal = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                $fallbackPricePerNight = ($meta['final_per_night'] ?? null) ?: ($dat_phong->tong_tien / max(1, $nightsGlobal * max(1, $roomsCount)));

                // create dat_phong_item per slot (so_luong = 1)
                foreach ($roomSlots as $index => $slot) {
                    $meta_item = is_array($slot['meta']) ? $slot['meta'] : [];
                    $nights = $meta_item['nights'] ?? $nightsGlobal;
                    $price_per_night = $meta_item['final_per_night'] ?? $fallbackPricePerNight;

                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;

                    $ph = $slot['phong_id'] ? \App\Models\Phong::find($slot['phong_id']) : null;
                    $capacity = $ph ? ($ph->suc_chua ?? 2) : ($meta_item['room_capacity_single'] ?? 2);

                    $adultsInCapacity = min($adultsInRoom, $capacity);
                    $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                    $extraAdultsThisRoom = max(0, $adultsInRoom - $adultsInCapacity);
                    $extraChildrenThisRoom = max(0, $childrenInRoom - $childrenInCapacity);

                    $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id'       => $dat_phong->id,
                        'phong_id'           => $slot['phong_id'] ?? null,
                        'loai_phong_id'      => $slot['loai_phong_id'],
                        'so_dem'             => $nights,
                        'so_luong'           => 1,
                        'so_nguoi_o'         => $guestsInRoom,
                        'number_child'       => $extraChildrenThisRoom,
                        'number_adult'       => $extraAdultsThisRoom,
                        'gia_tren_dem'       => $price_per_night,
                        'tong_item'          => $price_per_night * $nights * 1,
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item (IPN slot)', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload'      => $itemPayload,
                    ]);
                    DatPhongItem::create($itemPayload);

                    if (!empty($slot['phong_id'])) {
                        $phongIdsToOccupy[] = $slot['phong_id'];
                    }
                }

                // delete processed giu_phong rows (best-effort)
                foreach ($giu_phongs as $gp) {
                    try {
                        $gp->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete giu_phong after IPN', ['id' => $gp->id, 'error' => $e->getMessage()]);
                    }
                }

                if (!empty($phongIdsToOccupy)) {
                    Phong::whereIn('id', array_unique($phongIdsToOccupy))
                        ->update(['trang_thai' => 'dang_o']);
                }

                // notifications
                $totalPaid = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
                $notificationService = new PaymentNotificationService();
                if ($totalPaid >= $dat_phong->tong_tien) {
                    $notificationService->sendFullPaymentNotification($dat_phong, $giao_dich);
                } else {
                    $notificationService->sendDepositPaymentNotification($dat_phong, $giao_dich);
                }

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

            // failure path
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
        $snapshotMeta = $booking->snapshot_meta;
        if (is_array($snapshotMeta)) {
            $meta = $snapshotMeta;
        } elseif (is_string($snapshotMeta) && !empty($snapshotMeta)) {
            $decoded = json_decode($snapshotMeta, true);
            $meta = is_array($decoded) ? $decoded : [];
        } else {
            $meta = [];
        }
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

            // --- NEW: nếu còn GiuPhong chưa được chuyển → flatten và tạo DatPhongItem (so_luong=1 mỗi slot) ---
            $meta = is_array($booking->snapshot_meta)
                ? $booking->snapshot_meta
                : (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)
                    ? (json_decode($booking->snapshot_meta, true) ?: [])
                    : []);
            $roomsCount = (int) ($meta['rooms_count'] ?? 1);
            $giu_phongs = GiuPhong::where('dat_phong_id', $booking->id)->get();
            $phongIdsToOccupy = [];

            if ($giu_phongs->isNotEmpty()) {
                // build slots similar to other callbacks
                $roomSlots = [];
                foreach ($giu_phongs as $gp) {
                    $meta_item = is_string($gp->meta) ? json_decode($gp->meta, true) : ($gp->meta ?? []);
                    if (!is_array($meta_item)) $meta_item = [];
                    $count = (int) ($gp->so_luong ?? 1);

                    $selected = [];
                    if (!empty($meta_item['selected_phong_ids']) && is_array($meta_item['selected_phong_ids'])) {
                        $selected = array_map('intval', $meta_item['selected_phong_ids']);
                    } elseif (!empty($meta_item['selected_phong_id'])) {
                        $selected = [(int)$meta_item['selected_phong_id']];
                    } elseif (!empty($gp->phong_id)) {
                        $selected = [(int)$gp->phong_id];
                    }

                    foreach ($selected as $pid) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = ['gp' => $gp, 'meta' => $meta_item, 'phong_id' => $pid, 'loai_phong_id' => $gp->loai_phong_id];
                    }

                    $selectedCount = count($selected);
                    for ($i = 0; $i < max(0, $count - $selectedCount); $i++) {
                        if (count($roomSlots) >= $roomsCount) break;
                        $roomSlots[] = ['gp' => $gp, 'meta' => $meta_item, 'phong_id' => null, 'loai_phong_id' => $gp->loai_phong_id];
                    }
                }

                if (count($roomSlots) > $roomsCount) {
                    $roomSlots = array_slice($roomSlots, 0, $roomsCount);
                }

                // distribute guests
                $totalAdults = (int) ($meta['computed_adults'] ?? 0);
                $totalChildren = (int) ($meta['chargeable_children'] ?? 0);
                $baseAdultsPerRoom = floor($totalAdults / max(1, $roomsCount));
                $extraAdults = $totalAdults % max(1, $roomsCount);
                $baseChildrenPerRoom = floor($totalChildren / max(1, $roomsCount));
                $extraChildren = $totalChildren % max(1, $roomsCount);

                $nightsGlobal = $meta['nights'] ?? $this->calculateNights($booking->ngay_nhan_phong, $booking->ngay_tra_phong);
                $fallbackPricePerNight = ($meta['final_per_night'] ?? null) ?: ($booking->tong_tien / max(1, $nightsGlobal * max(1, $roomsCount)));

                foreach ($roomSlots as $index => $slot) {
                    $meta_item = is_array($slot['meta']) ? $slot['meta'] : [];
                    $nights = $meta_item['nights'] ?? $nightsGlobal;
                    $price_per_night = $meta_item['final_per_night'] ?? $fallbackPricePerNight;

                    $adultsInRoom = $baseAdultsPerRoom + ($index < $extraAdults ? 1 : 0);
                    $childrenInRoom = $baseChildrenPerRoom + ($index < $extraChildren ? 1 : 0);
                    $guestsInRoom = $adultsInRoom + $childrenInRoom;

                    $ph = $slot['phong_id'] ? \App\Models\Phong::find($slot['phong_id']) : null;
                    $capacity = $ph ? ($ph->suc_chua ?? 2) : ($meta_item['room_capacity_single'] ?? 2);

                    $adultsInCapacity = min($adultsInRoom, $capacity);
                    $childrenInCapacity = min($childrenInRoom, max(0, $capacity - $adultsInCapacity));
                    $extraAdultsThisRoom = max(0, $adultsInRoom - $adultsInCapacity);
                    $extraChildrenThisRoom = max(0, $childrenInRoom - $childrenInCapacity);

                    $specSignatureHash = $meta_item['spec_signature_hash'] ?? $meta_item['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id'        => $booking->id,
                        'phong_id'            => $slot['phong_id'] ?? null,
                        'loai_phong_id'       => $slot['loai_phong_id'],
                        'so_dem'              => $nights,
                        'so_luong'            => 1,
                        'so_nguoi_o'          => $guestsInRoom,
                        'number_child'        => $extraChildrenThisRoom,
                        'number_adult'        => $extraAdultsThisRoom,
                        'gia_tren_dem'        => $price_per_night,
                        'tong_item'           => $price_per_night * $nights,
                        'spec_signature_hash' => $specSignatureHash,
                    ];

                    Log::debug('Inserting dat_phong_item (remaining payment)', ['payload' => $itemPayload]);
                    DatPhongItem::create($itemPayload);

                    if (!empty($slot['phong_id'])) {
                        $phongIdsToOccupy[] = $slot['phong_id'];
                    }
                }

                // delete holds
                foreach ($giu_phongs as $gp) {
                    try {
                        $gp->delete();
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete giu_phong', ['id' => $gp->id, 'err' => $e->getMessage()]);
                    }
                }
            }

            // recalc totalPaid and update booking status if fully paid
            $totalPaid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');

            Log::info('Payment calculation', [
                'booking_id'     => $booking->id,
                'total_paid'     => $totalPaid,
                'total_required' => $booking->tong_tien,
                'fully_paid'     => ($totalPaid >= $booking->tong_tien),
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

                if (!empty($phongIdsToOccupy)) {
                    Phong::whereIn('id', array_unique($phongIdsToOccupy))->update(['trang_thai' => 'dang_o']);
                    Log::info('Room status updated', [
                        'phong_ids'  => $phongIdsToOccupy,
                        'new_status' => 'dang_o',
                    ]);
                } else {
                    // fallback: use datPhongItems
                    $phongIds = $booking->datPhongItems()->pluck('phong_id')->filter()->toArray();
                    if (!empty($phongIds)) {
                        Phong::whereIn('id', array_unique($phongIds))->update(['trang_thai' => 'dang_o']);
                        Log::info('Room status updated from dat_phong_items', ['phong_ids' => $phongIds]);
                    }
                }

                // Notification
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
                ->with('success', 'Thanh toán thành công! Còn thiếu ' . number_format($booking->tong_tien - $totalPaid) . ' VND.');
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

    private function generateSpecSignatureHash($data, $phongOrLoai)
    {
        $baseTienNghi = [];
        $bedSpec = [];
        if (is_object($phongOrLoai)) {
            // $phongOrLoai is a Phong or LoaiPhong model
            if (method_exists($phongOrLoai, 'effectiveTienNghiIds')) {
                $baseTienNghi = $phongOrLoai->effectiveTienNghiIds();
            } elseif (isset($phongOrLoai->tien_nghi_ids) && is_array($phongOrLoai->tien_nghi_ids)) {
                $baseTienNghi = $phongOrLoai->tien_nghi_ids;
            }
            if (method_exists($phongOrLoai, 'effectiveBedSpec')) {
                $bedSpec = $phongOrLoai->effectiveBedSpec();
            } elseif (isset($phongOrLoai->bed_spec)) {
                $bedSpec = $phongOrLoai->bed_spec;
            }
        } elseif (is_int($phongOrLoai) || is_string($phongOrLoai)) {
            // loai_phong id passed — try to load a representative Phong
            $rep = \App\Models\Phong::where('loai_phong_id', $phongOrLoai)->first();
            if ($rep) {
                if (method_exists($rep, 'effectiveTienNghiIds')) $baseTienNghi = $rep->effectiveTienNghiIds();
                if (method_exists($rep, 'effectiveBedSpec')) $bedSpec = $rep->effectiveBedSpec();
            }
        } else {
            // fallback empty
            $baseTienNghi = [];
            $bedSpec = [];
        }

        // normalize addons from $data
        $selectedAddonIdsArr = [];
        if (!empty($data['addons'])) {
            if (is_array($data['addons'])) {
                $selectedAddonIdsArr = array_map('intval', $data['addons']);
            } elseif (is_string($data['addons'])) {
                // allow comma separated
                $parts = array_filter(array_map('trim', explode(',', $data['addons'])));
                $selectedAddonIdsArr = array_map('intval', $parts);
            } else {
                $selectedAddonIdsArr = [(int)$data['addons']];
            }
        }

        $mergedTienNghi = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
        sort($mergedTienNghi, SORT_NUMERIC);

        $specArray = [
            'loai_phong_id' => (int) ($phongOrLoai->loai_phong_id ?? ($phongOrLoai)),
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

        $matchingRoomQuery = Phong::where('loai_phong_id', $loaiPhongId);
        if (!is_null($requiredSignature)) {
            $matchingRoomQuery = $matchingRoomQuery->where('spec_signature_hash', $requiredSignature);
        }
        $matchingRoomIds = $matchingRoomQuery->pluck('id')->toArray();

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
                if (!$metaRaw) continue;
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
                if (!is_null($requiredSignature)) {
                    $qg = $qg->where('giu_phong.spec_signature_hash', $requiredSignature);
                }
                $aggregateHoldsForSignature = Schema::hasColumn('giu_phong', 'so_luong')
                    ? (int) $qg->sum('giu_phong.so_luong')
                    : (int) $qg->count();
            } else {
                $holdsMeta = $qg->whereNotNull('giu_phong.meta')->pluck('giu_phong.meta');
                foreach ($holdsMeta as $metaRaw) {
                    if (!$metaRaw) continue;
                    $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                    if (!is_array($decoded)) continue;
                    if (isset($decoded['spec_signature_hash']) && $decoded['spec_signature_hash'] === $requiredSignature) {
                        $aggregateHoldsForSignature += $decoded['rooms_count'] ?? 1;
                    }
                }
            }
        }

        $totalRoomsOfType = 0;
        if (Schema::hasTable('loai_phong') && Schema::hasColumn('loai_phong', 'so_luong_thuc_te')) {
            $totalRoomsOfType = (int) DB::table('loai_phong')->where('id', $loaiPhongId)->value('so_luong_thuc_te');
        }
        if ($totalRoomsOfType <= 0) {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)->count();
        }

        $remainingAcrossType = max(0, $totalRoomsOfType - $aggregateBooked - $aggregateHoldsForSignature);
        $availableForSignature = max(0, min($matchingAvailableCount, $remainingAcrossType));

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
                if (!$metaRaw) continue;
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
            ->when(!is_null($requiredSignature), fn($q) => $q->where('spec_signature_hash', $requiredSignature))
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

    private function createBookingFromRooms(array $groups, array $datPhongData, array $snapshotMeta, Carbon $from, Carbon $to, int $nights)
    {
        return DB::transaction(function () use ($groups, $datPhongData, $snapshotMeta, $from, $to, $nights) {
            $snapshotMeta['groups'] = $groups;

            // create booking first
            $totalRooms = 0;
            foreach ($groups as $g) {
                $totalRooms += (int) ($g['rooms_count'] ?? $g['rooms'] ?? $g['so_luong'] ?? 1);
            }
            $snapshotMeta['rooms_count'] = max(1, $totalRooms);

            // then persist snapshot into datPhongData (you already do), so DatPhong has correct rooms_count
            $datPhongData['snapshot_meta'] = $snapshotMeta;
            $dat_phong = \App\Models\DatPhong::create($datPhongData);

            // derive per-room price fallback (total / nights / total_rooms)
            $totalRoomsSnapshot = (int) ($snapshotMeta['rooms_count'] ?? 0);
            if ($totalRoomsSnapshot <= 0) {
                foreach ($groups as $g) {
                    $totalRoomsSnapshot += (int) ($g['rooms_count'] ?? $g['rooms'] ?? $g['so_luong'] ?? 1);
                }
            }
            $perRoomPerNight = ($dat_phong->tong_tien ?? 0) / max(1, $nights * max(1, $totalRoomsSnapshot));

            // common hold base used for giu_phong rows
            $holdBase = [
                'dat_phong_id'  => $dat_phong->id,
                'het_han_luc'   => now()->addMinutes(15),
                'released'      => false,
                'meta'          => null, // will set per-row
            ];

            foreach ($groups as $groupIdx => $group) {
                // normalize group fields
                $loai_phong_id = isset($group['loai_phong_id']) ? (int)$group['loai_phong_id'] : (int)($group['loai_phong'] ?? 0);
                $requestedCount = isset($group['rooms']) ? (int)$group['rooms'] : (int)($group['so_luong'] ?? ($group['rooms_count'] ?? 1));
                if ($requestedCount <= 0) {
                    $requestedCount = 1;
                }

                // normalize selected ids (preferred list)
                $selectedIds = [];
                if (!empty($group['selected_phong_ids']) && is_array($group['selected_phong_ids'])) {
                    $selectedIds = array_map('intval', $group['selected_phong_ids']);
                } elseif (!empty($group['selected_ids']) && is_array($group['selected_ids'])) {
                    $selectedIds = array_map('intval', $group['selected_ids']);
                } elseif (!empty($group['phong_ids']) && is_array($group['phong_ids'])) {
                    $selectedIds = array_map('intval', $group['phong_ids']);
                } elseif (!empty($group['phong_id'])) {
                    $selectedIds = [(int)$group['phong_id']];
                }

                $repPhong = \App\Models\Phong::where('loai_phong_id', $loai_phong_id)->first();
                $specSignature = null;
                if ($repPhong && !empty($repPhong->spec_signature_hash)) {
                    $specSignature = $repPhong->spec_signature_hash;
                } else {
                    // if frontend provided explicit spec_signature, use it
                    if (!empty($group['spec_signature'])) {
                        $specSignature = $group['spec_signature'];
                    } else {
                        // fallback: generate from addons/loai/beds if we have a sample phong
                        $specSignature = $repPhong ? $this->generateSpecSignatureHash(['addons' => ($group['addons'] ?? [])], $repPhong) : null;
                    }
                }

                // final_per_night for meta (per room)
                $finalPerNight = $snapshotMeta['final_per_night'] ?? null;
                if (is_null($finalPerNight)) {
                    $finalPerNight = ($dat_phong->tong_tien ?? 0) / max(1, $nights * max(1, $snapshotMeta['rooms_count'] ?? $totalRoomsSnapshot));
                } else {
                    $rooms_count_snapshot = $snapshotMeta['rooms_count'] ?? 1;
                    if ($rooms_count_snapshot > 0) {
                        $finalPerNight = $finalPerNight / $rooms_count_snapshot;
                    }
                }

                // --- ensure unique allocated ids and try to refill if duplicates happened ---
                $allocatedIds = array_values(array_unique($allocatedIds));

                // If still lacking (possible because computeAvailableRoomIds returned duplicates or limited), try re-fetch excluding already picked
                if (count($allocatedIds) < $requestedCount) {
                    $needed = $requestedCount - count($allocatedIds);
                    $exclude = $allocatedIds;
                    // try to fetch more, excluding already chosen
                    $more = $this->computeAvailableRoomIds($loai_phong_id, $from, $to, $needed + 3, $specSignature); // request a bit extra
                    if (!empty($more)) {
                        foreach ($more as $mid) {
                            if (count($allocatedIds) >= $requestedCount) break;
                            if (!in_array($mid, $allocatedIds)) $allocatedIds[] = $mid;
                        }
                    }
                    // final uniqueness safeguard
                    $allocatedIds = array_values(array_unique($allocatedIds));
                }

                // final check
                if (count($allocatedIds) < $requestedCount) {
                    throw new \Exception("Không đủ phòng khả dụng cho loại {$loai_phong_id}. Cần {$requestedCount}, có " . count($allocatedIds));
                }

                // --- insert giu_phong per allocated room, ensure meta.final_per_night is per-room (use group['final_per_night'] when provided) ---
                foreach ($allocatedIds as $pid) {
                    $pid = (int) $pid;
                    if ($pid <= 0) {
                        throw new \Exception("Phòng được chọn không hợp lệ cho loại {$loai_phong_id}");
                    }
                    if (Schema::hasTable('giu_phong')) {
                        $row = $holdBase;
                        $row['loai_phong_id'] = $loai_phong_id;
                        $row['phong_id'] = $pid;
                        $row['so_luong'] = 1;

                        // prefer group's provided final_per_night (per-room), else compute fallback
                        $groupFinalPerNight = $group['final_per_night'] ?? null;
                        if ($groupFinalPerNight) {
                            $finalPerNightForMeta = (float) $groupFinalPerNight;
                        } else {
                            // fallback: divide snapshot final_per_night by rooms_count snapshot if that was aggregated
                            $finalPerNightForMeta = (float) ($dat_phong->tong_tien / max(1, $nights * max(1, $snapshotMeta['rooms_count'] ?? 1)));
                        }

                        if (Schema::hasColumn('giu_phong', 'spec_signature_hash') && $specSignature) {
                            $row['spec_signature_hash'] = $specSignature;
                        }

                        // meta per-room: embed the per-room final price
                        $metaDecoded = json_decode($row['meta'], true) ?: [];
                        $metaDecoded = array_merge($metaDecoded, [
                            'selected_phong_id' => $pid,
                            'selected_phong_ids' => $allocatedIds,
                            'final_per_night' => $finalPerNightForMeta,
                            'nights' => $nights,
                            'rooms_count' => $requestedCount,
                            'spec_signature_hash' => $specSignature,
                            'requested_loai_phong_id' => $loai_phong_id,
                        ]);
                        $row['meta'] = json_encode($metaDecoded, JSON_UNESCAPED_UNICODE);

                        DB::table('giu_phong')->insert($row);
                    }
                }


                // if for some reason we still need more rooms (shouldn't happen because allocatedIds === requestedCount),
                // we would insert aggregated hold rows — but ensure those meta also contain spec_signature_hash
                // (Note: allocateIds covers the common happy path)
            } // end groups loop

            // persist enriched snapshot_meta
            $dat_phong->snapshot_meta = $snapshotMeta;
            $dat_phong->save();

            return $dat_phong;
        });
    }
}
