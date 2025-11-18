<?php

namespace App\Http\Controllers\Client;

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

class PaymentController extends Controller
{
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

    public function initiateVNPay(Request $request)
    {
        Log::info('initiateVNPay request:', $request->all());

        try {
            $validated = $request->validate([
                'phong_id' => 'required|exists:phong,id',
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                'amount' => 'required|numeric|min:1',
                'total_amount' => 'required|numeric|min:1|gte:amount',
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
                'phone' => 'required|string|regex:/^0[3-9]\d{8}$/|unique:dat_phong,contact_phone,NULL,id,nguoi_dung_id,' 
            ]);

            $expectedDeposit = $validated['total_amount'] * 0.2;
            if (abs($validated['amount'] - $expectedDeposit) > 1000) {
                return response()->json(['error' => 'Deposit không hợp lệ (phải khoảng 20% tổng)'], 400);
            }

            $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides'])->findOrFail($validated['phong_id']);
            $maThamChieu = 'DP' . strtoupper(Str::random(8));

            $from = Carbon::parse($validated['ngay_nhan_phong']);
            $to = Carbon::parse($validated['ngay_tra_phong']);
            $nights = $this->calculateNights($validated['ngay_nhan_phong'], $validated['ngay_tra_phong']);

            $adultsInput = $validated['adults'];
            $childrenInput = $validated['children'] ?? 0;
            $childrenAges = $validated['children_ages'] ?? [];

            $computedAdults = $adultsInput;
            $chargeableChildren = 0;
            foreach ($childrenAges as $age) {
                $age = (int)$age;
                if ($age >= 13) $computedAdults++;
                elseif ($age >= 7) $chargeableChildren++;
            }

            $roomCapacity = 0;
            if ($phong->bedTypes && $phong->bedTypes->count()) {
                foreach ($phong->bedTypes as $bt) {
                    $qty = (int)($bt->pivot->quantity ?? 0);
                    $cap = (int)($bt->capacity ?? 1);
                    $roomCapacity += $qty * $cap;
                }
            }
            if ($roomCapacity <= 0) $roomCapacity = (int)($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));

            $roomsCount = $validated['rooms_count'];
            $totalRoomCapacity = $roomCapacity * $roomsCount;
            $countedPersons = $computedAdults + $chargeableChildren;
            $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
            $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
            $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
            $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
            $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);

            $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
            $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;
            $basePerNight = (float)($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);

            $selectedAddonIds = $validated['addons'] ?? [];
            $selectedAddons = collect();
            if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
                $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
            }

            $addonsPerNightPerRoom = (float)($selectedAddons->sum('gia') ?? 0.0);
            $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;
            $finalPerNightServer = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
            $snapshotTotalServer = $finalPerNightServer * $nights;

            $snapshotMeta = [
                'phong_id' => $validated['phong_id'],
                'loai_phong_id' => $phong->loai_phong_id,
                'adults' => $adultsInput,
                'children' => $childrenInput,
                'children_ages' => $childrenAges,
                'computed_adults' => $computedAdults,
                'chargeable_children' => $chargeableChildren,
                'room_capacity_single' => $roomCapacity,
                'total_room_capacity' => $totalRoomCapacity,
                'counted_persons' => $countedPersons,
                'extra_count_total' => $extraCountTotal,
                'adult_extra_total' => $adultExtraTotal,
                'children_extra_total' => $childrenExtraTotal,
                'room_base_per_night' => $basePerNight,
                'adults_charge_per_night' => $adultsChargePerNight,
                'children_charge_per_night' => $childrenChargePerNight,
                'addons_per_night' => $addonsPerNight,
                'addons' => $selectedAddons->map(fn($a) => ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia])->toArray(),
                'final_per_night' => $finalPerNightServer,
                'nights' => $nights,
                'rooms_count' => $roomsCount,
                'tong_tien' => $snapshotTotalServer,
                'phuong_thuc' => $validated['phuong_thuc'],
                'contact_name' => $validated['name'],
                'contact_address' => $validated['address'],
                'contact_phone' => $validated['phone'],
            ];

            return DB::transaction(function () use (
                $validated, $maThamChieu, $snapshotMeta, $phong, $request, $from, $to, $nights,
                $roomsCount, $finalPerNightServer, $snapshotTotalServer, $selectedAddons
            ) {
                $dat_phong = DatPhong::create([
                    'ma_tham_chieu' => $maThamChieu,
                    'nguoi_dung_id' => Auth::id(),
                    'phong_id' => $validated['phong_id'],
                    'ngay_nhan_phong' => $validated['ngay_nhan_phong'],
                    'ngay_tra_phong' => $validated['ngay_tra_phong'],
                    'tong_tien' => $snapshotTotalServer,
                    'deposit_amount' => $validated['amount'],
                    'so_khach' => $validated['so_khach'] ?? ($validated['adults'] + ($validated['children'] ?? 0)),
                    'trang_thai' => 'dang_cho',
                    'can_thanh_toan' => true,
                    'can_xac_nhan' => false,
                    'created_by' => Auth::id(),
                    'snapshot_meta' => json_encode($snapshotMeta),
                    'phuong_thuc' => $validated['phuong_thuc'],
                    'contact_name' => $validated['name'],
                    'contact_address' => $validated['address'],
                    'contact_phone' => $validated['phone'],
                ]);

                Log::info('Payment booking created with contact', [
                    'dat_phong_id' => $dat_phong->id,
                    'phuong_thuc' => $dat_phong->phuong_thuc,
                    'contact_name' => $dat_phong->contact_name,
                    'contact_phone' => $dat_phong->contact_phone,
                    'validated_data' => $validated,
                ]);

                if (Schema::hasTable('loai_phong')) {
                    DB::table('loai_phong')->where('id', $phong->loai_phong_id)->lockForUpdate()->first();
                }

                $requiredSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
                $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $requiredSignature);
                if ($roomsCount > $availableNow) {
                    throw new \Exception("Only {$availableNow} room(s) available.");
                }

                $holdBase = [
                    'dat_phong_id' => $dat_phong->id,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'het_han_luc' => now()->addMinutes(15),
                    'released' => false,
                    'meta' => json_encode([
                        'final_per_night' => $finalPerNightServer / $roomsCount,
                        'snapshot_total' => $snapshotTotalServer,
                        'nights' => $nights,
                        'rooms_count' => $roomsCount,
                        'addons' => $selectedAddons->map(fn($a) => ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia])->toArray(),
                        'spec_signature_hash' => $this->generateSpecSignatureHash($validated, $phong),
                        'requested_spec_signature' => $requiredSignature,
                    ], JSON_UNESCAPED_UNICODE),
                ];

                $baseSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
                $baseTienNghi = method_exists($phong, 'effectiveTienNghiIds') ? $phong->effectiveTienNghiIds() : [];
                $selectedAddonIdsArr = $selectedAddons->pluck('id')->map('intval')->toArray();
                $mergedTienNghi = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
                sort($mergedTienNghi, SORT_NUMERIC);
                $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

                $specArray = [
                    'loai_phong_id' => (int)$phong->loai_phong_id,
                    'tien_nghi' => $mergedTienNghi,
                    'beds' => $bedSpec,
                ];
                ksort($specArray);
                $requestedSpecSignature = md5(json_encode($specArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                $requestedPhongId = $phong->id;
                $requestedReserved = 0;

                if (Schema::hasColumn('giu_phong', 'phong_id')) {
                    $isBooked = false;
                    if (Schema::hasTable('dat_phong_item')) {
                        $fromStartStr = $from->copy()->setTime(14, 0)->toDateTimeString();
                        $toEndStr = $to->copy()->setTime(12, 0)->toDateTimeString();
                        $isBooked = DB::table('dat_phong_item')
                            ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                            ->where('dat_phong_item.phong_id', $requestedPhongId)
                            ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                            ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$toEndStr, $fromStartStr])
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
                            $row = $holdBase;
                            $row['so_luong'] = 1;
                            $row['phong_id'] = $requestedPhongId;
                            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                $row['spec_signature_hash'] = $baseSignature;
                            }
                            $row['meta'] = json_encode(array_merge(json_decode($row['meta'], true), ['selected_phong_id' => $requestedPhongId, 'selected_phong_ids' => [$requestedPhongId]]), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($row);
                            $requestedReserved = 1;
                            Log::debug('Payment: giu_phong inserted per-phong (requested)', ['phong_id' => $requestedPhongId, 'dat_phong_id' => $dat_phong->id]);
                        }
                    }
                }

                $stillNeeded = max(0, $roomsCount - $requestedReserved);
                $selectedIds = [];
                if ($stillNeeded > 0) {
                    $selectedIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $stillNeeded, $requestedSpecSignature);
                    if (empty($selectedIds) || count($selectedIds) < $stillNeeded) {
                        $need = $stillNeeded - count($selectedIds);
                        $fallbackIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $need, null);
                        $selectedIds = array_values(array_unique(array_merge($selectedIds, $fallbackIds)));
                    }
                    if ($requestedReserved && !empty($selectedIds)) {
                        $selectedIds = array_values(array_diff($selectedIds, [$requestedPhongId]));
                    }
                }

                if (!empty($selectedIds)) {
                    $locked = Phong::whereIn('id', $selectedIds)->lockForUpdate()->get(['id'])->pluck('id')->toArray();
                    $selectedIds = array_values(array_intersect($selectedIds, $locked));
                }

                $reservedCount = $requestedReserved;
                if (!empty($selectedIds)) {
                    foreach ($selectedIds as $pid) {
                        if ($reservedCount >= $roomsCount) break;
                        $row = $holdBase;
                        $row['so_luong'] = 1;
                        $row['phong_id'] = $pid;
                        if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                            $row['spec_signature_hash'] = $baseSignature;
                        }
                        $row['meta'] = json_encode(array_merge(json_decode($row['meta'], true), ['selected_phong_id' => $pid, 'selected_phong_ids' => $selectedIds]), JSON_UNESCAPED_UNICODE);
                        DB::table('giu_phong')->insert($row);
                        $reservedCount++;
                        Log::debug('Payment: giu_phong inserted per-phong', ['phong_id' => $pid, 'dat_phong_id' => $dat_phong->id]);
                    }
                }

                if ($roomsCount - $reservedCount > 0 && Schema::hasColumn('giu_phong', 'phong_id')) {
                    $aggRow = $holdBase;
                    $aggRow['so_luong'] = $roomsCount - $reservedCount;
                    if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                        $aggRow['spec_signature_hash'] = $baseSignature;
                    }
                    $aggRow['meta'] = json_encode(array_merge(json_decode($aggRow['meta'], true), ['reserved_count' => $reservedCount]), JSON_UNESCAPED_UNICODE);
                    DB::table('giu_phong')->insert($aggRow);
                    Log::debug('Payment: giu_phong inserted aggregate remaining', ['remaining' => $roomsCount - $reservedCount, 'dat_phong_id' => $dat_phong->id]);
                } elseif (!Schema::hasColumn('giu_phong', 'phong_id')) {
                    $holdBase['so_luong'] = $roomsCount;
                    $holdBase['spec_signature_hash'] = $requestedSpecSignature;
                    DB::table('giu_phong')->insert($holdBase);
                }

                $giao_dich = GiaoDich::create([
                    'dat_phong_id' => $dat_phong->id,
                    'nha_cung_cap' => 'vnpay',
                    'so_tien' => $validated['amount'],
                    'don_vi' => 'VND',
                    'trang_thai' => 'dang_cho',
                    'ghi_chu' => "Thanh toán đặt cọc phòng:{$dat_phong->ma_tham_chieu}",
                ]);

                $vnp_Url = env('VNPAY_URL');
                $vnp_TmnCode = env('VNPAY_TMN_CODE');
                $vnp_HashSecret = env('VNPAY_HASH_SECRET');
                $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

                $inputData = [
                    "vnp_Version" => "2.1.0",
                    "vnp_TmnCode" => $vnp_TmnCode,
                    "vnp_Amount" => $validated['amount'] * 100,
                    "vnp_Command" => "pay",
                    "vnp_CreateDate" => date('YmdHis'),
                    "vnp_CurrCode" => "VND",
                    "vnp_IpAddr" => $request->ip(),
                    "vnp_Locale" => "vn",
                    "vnp_OrderInfo" => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    "vnp_OrderType" => "billpayment",
                    "vnp_ReturnUrl" => $vnp_ReturnUrl,
                    "vnp_TxnRef" => (string)$giao_dich->id,
                ];

                ksort($inputData);
                $query = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
                $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
                $redirectUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

                return response()->json(['redirect_url' => $redirectUrl, 'dat_phong_id' => $dat_phong->id]);
            });
        } catch (\Throwable $e) {
            Log::error('VNPay initiate error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    public function handleVNPayCallback(Request $request)
    {
        Log::info('VNPAY Callback Received', $request->all());

        $inputData = collect($request->all())
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))
            ->toArray();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
        ksort($inputData);

        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $localHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY Signature Check', [
            'hashData' => $hashData,
            'localHash' => $localHash,
            'remoteHash' => strtoupper($vnp_SecureHash),
            'match' => ($localHash === strtoupper($vnp_SecureHash)),
        ]);

        if ($localHash !== strtoupper($vnp_SecureHash)) {
            return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) return view('payment.fail', ['code' => '01', 'message' => 'Không tìm thấy giao dịch']);

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) return view('payment.fail', ['code' => '02', 'message' => 'Không tìm thấy đơn đặt phòng']);

        $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : json_decode($dat_phong->snapshot_meta, true);
        $roomsCount = $meta['rooms_count'] ?? 1;

        return DB::transaction(function () use ($vnp_ResponseCode, $vnp_Amount, $inputData, $giao_dich, $dat_phong, $roomsCount) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'da_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

                $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                foreach ($giu_phongs as $giu_phong) {
                    $meta = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                    if (!is_array($meta)) $meta = [];

                    $nights = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    $price_per_night = $meta['final_per_night'] ?? ($dat_phong->tong_tien / max(1, $nights * $roomsCount));

                    $specSignatureHash = $meta['spec_signature_hash'] ?? $meta['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $giu_phong->phong_id ?? null,
                        'loai_phong_id' => $giu_phong->loai_phong_id,
                        'so_dem' => $nights,
                        'so_luong' => $giu_phong->so_luong ?? 1,
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

                return view('payment.success', compact('dat_phong'));
            } else {
                $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Mã lỗi: ' . $vnp_ResponseCode]);
                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)->queue(new PaymentFail($dat_phong, $vnp_ResponseCode));
                }
                return view('payment.fail', ['code' => $vnp_ResponseCode]);
            }
        });
    }

    public function handleIpn(Request $request)
    {
        Log::info('VNPAY IPN Received', $request->all());

        $inputData = collect($request->all())->toArray();
        $receivedSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $calculatedHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY IPN Signature Check', [
            'hashData' => $hashData,
            'calculatedHash' => $calculatedHash,
            'receivedHash' => strtoupper($receivedSecureHash),
            'match' => ($calculatedHash === strtoupper($receivedSecureHash)),
        ]);

        if ($calculatedHash !== strtoupper($receivedSecureHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) return response()->json(['RspCode' => '01', 'Message' => 'Transaction not found']);

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) return response()->json(['RspCode' => '02', 'Message' => 'Booking not found']);

        $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : json_decode($dat_phong->snapshot_meta, true);
        $roomsCount = $meta['rooms_count'] ?? 1;

        return DB::transaction(function () use ($giao_dich, $dat_phong, $vnp_ResponseCode, $vnp_Amount, $inputData, $roomsCount) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'da_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

                $giu_phongs = GiuPhong::where('dat_phong_id', $dat_phong->id)->get();
                $phongIdsToOccupy = [];

                foreach ($giu_phongs as $giu_phong) {
                    $meta = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                    if (!is_array($meta)) $meta = [];

                    $nights = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    $price_per_night = $meta['final_per_night'] ?? ($dat_phong->tong_tien / max(1, $nights * $roomsCount));

                    $specSignatureHash = $meta['spec_signature_hash'] ?? $meta['requested_spec_signature'] ?? null;

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $giu_phong->phong_id ?? null,
                        'loai_phong_id' => $giu_phong->loai_phong_id,
                        'so_dem' => $nights,
                        'so_luong' => $giu_phong->so_luong ?? 1,
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

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

            $giao_dich->update(['trang_thai' => 'that_bai']);
            return response()->json(['RspCode' => '99', 'Message' => 'Payment failed']);
        });
    }

    public function pendingPayments()
    {
        $pendingPayments = DatPhong::with(['nguoiDung', 'giaoDichs'])
            ->whereIn('trang_thai', ['dang_cho_xac_nhan', 'dang_cho'])
            ->where(function ($q) {
                $q->where('can_xac_nhan', true)->orWhere('can_thanh_toan', true);
            })
            ->whereHas('giaoDichs', fn($q) => $q->whereIn('trang_thai', ['thanh_cong', 'dang_cho']))
            ->orderByDesc('updated_at')
            ->get();

        return view('payment.pending_payments', compact('pendingPayments'));
    }

    public function simulateCallback()
    {
        $testData = [
            "vnp_Amount" => 200000000,
            "vnp_BankCode" => "NCB",
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toan don hang test",
            "vnp_OrderType" => "billpayment",
            "vnp_TmnCode" => env('VNPAY_TMN_CODE'),
            "vnp_TxnRef" => "TESTSIMULATE001",
            "vnp_ResponseCode" => "00",
            "vnp_TransactionNo" => "999999",
            "vnp_PayDate" => now()->format('YmdHis'),
        ];

        ksort($testData);
        $hashData = http_build_query($testData, '', '&', PHP_QUERY_RFC1738);
        $testData["vnp_SecureHash"] = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        return redirect()->route('payment.callback', $testData);
    }

    public function createPayment(Request $request)
    {
        $dat_phong_id = $request->input('dat_phong_id');
        $dat_phong = DatPhong::findOrFail($dat_phong_id);

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
                'so_tien' => $dat_phong->tong_tien,
                'don_vi' => $dat_phong->don_vi_tien ?? 'VND',
                'trang_thai' => 'dang_cho',
                'ghi_chu' => 'Thanh toán đặt cọc phòng:' . $dat_phong->id,
            ]);

            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $vnp_Url = env('VNPAY_URL');
            $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

            $vnp_TxnRef = (string)$giao_dich->id;
            $vnp_OrderInfo = 'Thanh toán đơn đặt phòng #' . $dat_phong->id;
            $vnp_OrderType = 'billpayment';
            $vnp_Amount = $dat_phong->tong_tien * 100;
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $request->ip();

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_ReturnUrl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_BankCode" => 'NCB',
            ];

            ksort($inputData);
            $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
            $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

            $vnp_Url .= '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;

            return redirect()->away($vnp_Url);
        });
    }

    public function initiateRemainingPayment(Request $request, $dat_phong_id)
    {
        $request->validate(['nha_cung_cap' => 'required|in:tien_mat,vnpay']);

        $booking = DatPhong::with(['giaoDichs', 'nguoiDung'])->lockForUpdate()->findOrFail($dat_phong_id);

        if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
            return back()->with('error', 'Booking không hợp lệ để thanh toán phần còn lại.');
        }

        // Kiểm tra CCCD trước khi thanh toán
        $meta = is_array($booking->snapshot_meta) ? $booking->snapshot_meta : json_decode($booking->snapshot_meta, true) ?? [];
        if (empty($meta['checkin_cccd'])) {
            return back()->with('error', 'Vui lòng nhập số CCCD/CMND trước khi thanh toán.');
        }

        $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
        $remaining = $booking->tong_tien - $paid;

        if ($remaining <= 0) {
            return back()->with('error', 'Đã thanh toán đủ, không cần thanh toán thêm.');
        }

        $transaction = DB::transaction(function () use ($booking, $remaining, $request) {
            $nhaCungCap = $request->nha_cung_cap;
            $trangThai = $nhaCungCap === 'tien_mat' ? 'thanh_cong' : 'dang_cho';

            $giaoDich = GiaoDich::create([
                'dat_phong_id' => $booking->id,
                'nha_cung_cap' => $nhaCungCap,
                'so_tien' => $remaining,
                'don_vi' => 'VND',
                'trang_thai' => $trangThai,
                'provider_txn_ref' => null,
                'ghi_chu' => "Thanh toán phần còn lại booking: {$booking->ma_tham_chieu}",
            ]);

            Log::info('Created remaining payment transaction', [
                'giao_dich_id' => $giaoDich->id,
                'nha_cung_cap' => $giaoDich->nha_cung_cap,
                'so_tien' => $giaoDich->so_tien,
                'trang_thai' => $giaoDich->trang_thai,
            ]);

            if ($nhaCungCap === 'tien_mat') {
                $booking->update(['trang_thai' => 'dang_su_dung', 'checked_in_at' => now()]);
            }

            return $giaoDich;
        });

        if ($request->nha_cung_cap === 'vnpay') {
            return $this->redirectToVNPay($transaction, $remaining);
        }

        return redirect()->route('staff.checkin')->with('success', 'Thanh toán tiền mặt thành công. Phòng đã được đưa vào sử dụng.');
    }

    public function handleRemainingCallback(Request $request)
    {
        Log::info('VNPAY Remaining Payment Callback', $request->all());

        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = collect($request->all())->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))->toArray();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $localHash = strtoupper(hash_hmac('sha512', $hashData, $vnp_HashSecret));

        Log::info('VNPAY Signature Check', [
            'hashData' => $hashData,
            'localHash' => $localHash,
            'remoteHash' => strtoupper($vnp_SecureHash),
            'match' => ($localHash === strtoupper($vnp_SecureHash)),
        ]);

        if ($localHash !== strtoupper($vnp_SecureHash)) {
            Log::error('VNPAY signature mismatch');
            return redirect()->route('staff.checkin')->with('error', 'Chữ ký không hợp lệ.');
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        Log::info('Looking for transaction', ['vnp_TxnRef' => $vnp_TxnRef]);

        $transaction = GiaoDich::find($vnp_TxnRef);
        if (!$transaction) {
            Log::error('Transaction not found', ['vnp_TxnRef' => $vnp_TxnRef]);
            return redirect()->route('staff.checkin')->with('error', 'Không tìm thấy giao dịch hợp lệ.');
        }

        if ($transaction->nha_cung_cap !== 'vnpay') {
            Log::error('Invalid payment provider', ['nha_cung_cap' => $transaction->nha_cung_cap, 'transaction_id' => $transaction->id]);
            return redirect()->route('staff.checkin')->with('error', 'Nhà cung cấp thanh toán không hợp lệ.');
        }

        if ($transaction->trang_thai === 'thanh_cong') {
            return redirect()->route('staff.checkin')->with('success', 'Thanh toán đã được xử lý trước đó.');
        }

        if ($transaction->trang_thai !== 'dang_cho') {
            Log::warning('Transaction not pending', ['status' => $transaction->trang_thai]);
            return redirect()->route('staff.checkin')->with('error', 'Giao dịch không ở trạng thái chờ xử lý.');
        }

        if ($vnp_ResponseCode !== '00') {
            $transaction->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'VNPay lỗi: ' . $vnp_ResponseCode]);
            Log::warning('Payment failed', ['response_code' => $vnp_ResponseCode]);
            return redirect()->route('staff.checkin')->with('error', 'Thanh toán thất bại. Mã lỗi: ' . $vnp_ResponseCode);
        }

        if (abs($transaction->so_tien - $vnp_Amount) > 1) {
            Log::error('Amount mismatch', ['expected' => $transaction->so_tien, 'received' => $vnp_Amount]);
            return redirect()->route('staff.checkin')->with('error', 'Số tiền không khớp.');
        }

        return DB::transaction(function () use ($transaction, $inputData) {
            $transaction->update([
                'trang_thai' => 'thanh_cong',
                'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? null,
                'ghi_chu' => 'Thanh toán phần còn lại thành công qua VNPAY',
            ]);

            Log::info('Transaction updated to success', ['transaction_id' => $transaction->id, 'provider_txn_ref' => $transaction->provider_txn_ref]);

            $booking = $transaction->datPhong;
            if (!$booking) {
                Log::error('Booking not found for transaction', ['transaction_id' => $transaction->id]);
                return redirect()->route('staff.checkin')->with('success', 'Thanh toán đặt phòng thành công.');
            }

            Log::info('Current booking status BEFORE update', [
                'booking_id' => $booking->id,
                'current_status' => $booking->trang_thai,
                'ma_tham_chieu' => $booking->ma_tham_chieu,
            ]);

            $totalPaid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');

            Log::info('Payment calculation', [
                'booking_id' => $booking->id,
                'total_paid' => $totalPaid,
                'total_required' => $booking->tong_tien,
                'fully_paid' => ($totalPaid >= $booking->tong_tien),
                'remaining' => $booking->tong_tien - $totalPaid,
            ]);

            if ($totalPaid >= $booking->tong_tien) {
                $oldStatus = $booking->trang_thai;
                $booking->trang_thai = 'dang_su_dung';
                $booking->checked_in_at = now();
                $booking->save();

                Log::info('Booking status updated AFTER save', [
                    'booking_id' => $booking->id,
                    'old_status' => $oldStatus,
                    'new_status' => $booking->trang_thai,
                    'checked_in_at' => $booking->checked_in_at,
                ]);

                $phongIds = $booking->datPhongItems()->pluck('phong_id')->filter()->toArray();
                if (!empty($phongIds)) {
                    Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_o']);
                    Log::info('Room status updated', ['phong_ids' => $phongIds, 'new_status' => 'dang_o']);
                }

                return redirect()->route('staff.checkin')->with('success', 'Thanh toán thành công! Phòng đã được chuyển sang trạng thái đang sử dụng.');
            }

            Log::warning('Payment not complete yet', ['booking_id' => $booking->id, 'paid' => $totalPaid, 'required' => $booking->tong_tien]);
            return redirect()->route('staff.checkin')->with('success', 'Thanh toán thành công! Còn thiếu ' . number_format($booking->tong_tien - $totalPaid) . ' VND.');
        });
    }

    private function redirectToVNPay(GiaoDich $transaction, float $amount)
    {
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_ReturnUrl = route('payment.remaining.callback');

        $vnp_TxnRef = (string)$transaction->id;
        $vnp_OrderInfo = 'Thanh toán phần còn lại booking #' . $transaction->dat_phong_id;
        $vnp_Amount = $amount * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => request()->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);
        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;
        return redirect()->away($paymentUrl);
    }

    private function calculateNights($ngayNhanPhong, $ngayTraPhong)
    {
        $from = new \DateTime($ngayNhanPhong);
        $to = new \DateTime($ngayTraPhong);
        return max(1, $from->diff($to)->days);
    }

    private function generateSpecSignatureHash($data, $phong)
    {
        $baseTienNghi = method_exists($phong, 'effectiveTienNghiIds') ? $phong->effectiveTienNghiIds() : [];
        $selectedAddonIdsArr = $data['addons'] ?? [];
        $mergedTienNghi = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
        sort($mergedTienNghi, SORT_NUMERIC);
        $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

        $specArray = [
            'loai_phong_id' => $phong->loai_phong_id,
            'tien_nghi' => $mergedTienNghi,
            'beds' => $bedSpec,
        ];
        ksort($specArray);
        return md5(json_encode($specArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

   private function computeAvailableRoomsCount(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, ?string $requiredSignature = null): int
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->pluck('id')->toArray();

        if (empty($matchingRoomIds)) return 0;

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
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
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
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
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) continue;
                $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
        }

        $occupiedSpecificIds = array_unique(array_merge($bookedRoomIds, $heldRoomIds));
        $matchingAvailableIds = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $matchingAvailableCount = count($matchingAvailableIds);

        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->whereNull('dat_phong_item.phong_id');

            $aggregateBooked = Schema::hasColumn('dat_phong_item', 'so_luong')
                ? (int)$q->sum('dat_phong_item.so_luong')
                : (int)$q->count();
        }

        $aggregateHoldsForSignature = 0;
        if (Schema::hasTable('giu_phong')) {
            $qg = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr]);

            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                $qg = $qg->where('giu_phong.spec_signature_hash', $requiredSignature);
                $aggregateHoldsForSignature = Schema::hasColumn('giu_phong', 'so_luong')
                    ? (int)$qg->sum('giu_phong.so_luong')
                    : (int)$qg->count();
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
            $totalRoomsOfType = (int)DB::table('loai_phong')->where('id', $loaiPhongId)->value('so_luong_thuc_te');
        }
        if ($totalRoomsOfType <= 0) {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)->count();
        }

        $remainingAcrossType = max(0, $totalRoomsOfType - $aggregateBooked - $aggregateHoldsForSignature);
        $availableForSignature = max(0, min($matchingAvailableCount, $remainingAcrossType));

        return (int)$availableForSignature;
    }

    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $limit = 1, ?string $requiredSignature = null): array
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')->filter()->unique()->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
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
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) continue;
                $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
        }

        $excluded = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        $query = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->when(!empty($excluded), fn($q) => $q->whereNotIn('id', $excluded))
            ->lockForUpdate()
            ->limit((int)$limit);

        $rows = $query->get(['id']);
        return $rows->pluck('id')->toArray();
    }
}