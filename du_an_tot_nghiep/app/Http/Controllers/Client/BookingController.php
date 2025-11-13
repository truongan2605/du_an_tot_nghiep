<?php

namespace App\Http\Controllers\Client;

use Carbon\Carbon;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiuPhong;
use App\Models\PhongDaDat;
use Illuminate\Support\Str;
use App\Models\DatPhongItem;
use Illuminate\Http\Request;
use App\Models\DatPhongAddon;
use App\Events\BookingCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BookingController extends Controller
{
    // Giá tiền với mỗi người quá số người mặc định của mỗi phòng
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $upcoming = DatPhong::where('nguoi_dung_id', $user->id)
            ->whereIn('trang_thai', ['dang_cho', 'da_xac_nhan'])
            ->with(['datPhongItems.phong', 'datPhongItems.loaiPhong'])
            ->orderBy('ngay_nhan_phong', 'asc')
            ->get();

        $cancelled = DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'da_huy')
            ->with(['datPhongItems.phong', 'datPhongItems.loaiPhong'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $completed = DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'hoan_thanh')
            ->with(['datPhongItems.phong.tang', 'datPhongItems.loaiPhong'])
            ->orderBy('ngay_nhan_phong', 'desc')
            ->get();

        return view('account.bookings', compact('upcoming', 'cancelled', 'completed', 'user'));
    }

    public function show(DatPhong $dat_phong, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($dat_phong->nguoi_dung_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $dat_phong->load(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'datPhongAddons', 'voucherUsages']);

        $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : (json_decode($dat_phong->snapshot_meta, true) ?: []);

        return view('account.booking_show', [
            'booking' => $dat_phong,
            'meta' => $meta,
            'user' => $user,
        ]);
    }

    public function create(Phong $phong)
    {
        $phong->load(['loaiPhong', 'tienNghis', 'images', 'bedTypes', 'activeOverrides']);
        $user = Auth::user();

        $typeAmenityIds = $phong->loaiPhong ? $phong->loaiPhong->tienNghis->pluck('id')->toArray() : [];
        $roomAmenityIds = $phong->tienNghis ? $phong->tienNghis->pluck('id')->toArray() : [];
        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        $availableAddons = \App\Models\TienNghi::where('active', true)
            ->when(!empty($allAmenityIds), function ($q) use ($allAmenityIds) {
                $q->whereNotIn('id', $allAmenityIds);
            })->orderBy('ten')->get();

        $fromDefault = Carbon::today();
        $toDefault = Carbon::tomorrow();

        $availableRoomsDefault = $this->computeAvailableRoomsCount(
            $phong->loai_phong_id,
            $fromDefault,
            $toDefault,
            $phong->spec_signature_hash ?? $phong->specSignatureHash()
        );

        return view('account.booking.create', compact('phong', 'user', 'availableAddons', 'availableRoomsDefault', 'fromDefault', 'toDefault'));
    }

    public function availability(Request $request)
    {
        $request->validate([
            'loai_phong_id' => 'required|integer|exists:loai_phong,id',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
            'phong_id' => 'nullable|integer|exists:phong,id',
        ]);

        $loaiId = (int) $request->input('loai_phong_id');
        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->startOfDay();

        $requiredSignature = null;
        if ($request->filled('phong_id')) {
            $phong = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])->find($request->input('phong_id'));
            if ($phong) {
                $requiredSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
            }
        }

        $available = $this->computeAvailableRoomsCount($loaiId, $from, $to, $requiredSignature);

        if ($request->boolean('debug')) {
            $candidates = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])
                ->where('loai_phong_id', $loaiId)
                // ->where('trang_thai', 'trong')
                ->get();

            $roomSignatures = $candidates->mapWithKeys(function ($r) {
                return [$r->id => $r->specSignatureHash()];
            });

            return response()->json([
                'available' => (int)$available,
                'required_signature' => $requiredSignature,
                'room_signatures' => $roomSignatures,
            ]);
        }

        return response()->json(['available' => (int)$available]);
    }

    private function computeAvailableRoomsCount(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, ?string $requiredSignature = null): int
    {
        // Build requested interval (start inclusive at 14:00, end exclusive at 12:00)
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();
        // ->where('trang_thai', 'trong')
        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->first();
            if (!$sample) return 0;
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        // All candidate rooms of this type+signature and in usable state
        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            // ->where('trang_thai', 'trong')
            ->where('spec_signature_hash', $requiredSignature)
            ->pluck('id')->toArray();

        if (empty($matchingRoomIds)) {
            return 0;
        }

        // 1) Specific booked rooms (dat_phong_item with phong_id) that overlap interval
        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                // overlap: existingStart < requestedEnd AND existingEnd > requestedStart
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        // 2) Holds that explicitly target rooms (giu_phong.phong_id) where the underlying dat_phong overlaps
        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            // join to dat_phong using dat_phong_id to check the booking dates that the hold was created for
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

        // 3) If holds include selected_phong_ids in their meta, but only consider those holds whose dat_phong (if present) overlaps
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
                $decoded = null;
                if (is_string($metaRaw)) {
                    $decoded = json_decode($metaRaw, true);
                } elseif (is_array($metaRaw)) {
                    $decoded = $metaRaw;
                }
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
        }

        $occupiedSpecificIds = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        // 4) Aggregate holds that don't target specific rooms (giu_phong without phong_id) but reserve counts of type
        $aggregateHolds = 0;
        if (Schema::hasTable('giu_phong')) {
            $aggregateHolds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->sum('giu_phong.so_luong');
        }

        $totalCandidates = count($matchingRoomIds);
        $specificOccupied = count($occupiedSpecificIds);
        $available = max(0, $totalCandidates - $specificOccupied - (int)$aggregateHolds);

        return (int)$available;
    }

    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $maxCount, ?string $requiredSignature = null): array
    {
        if ($maxCount <= 0) return [];

        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->first();
            if (!$sample) return [];
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        // Candidates: rooms of type+signature, usable, sorted by some pref if needed
        $candidates = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
            ->where('spec_signature_hash', $requiredSignature)
            ->orderBy('id') // or by floor, price etc
            ->pluck('id')
            ->toArray();

        if (empty($candidates)) return [];

        // Exclude specifically booked
        $bookedIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        // Exclude specifically held
        $heldIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldIds = DB::table('giu_phong')
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
                $decoded = json_decode($metaRaw, true) ?? $metaRaw;
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    $heldIds = array_merge($heldIds, array_map('intval', $decoded['selected_phong_ids']));
                }
            }
            $heldIds = array_unique($heldIds);
        }

        $excludedIds = array_unique(array_merge($bookedIds, $heldIds));

        $availableIds = array_diff($candidates, $excludedIds);

        // Respect aggregate holds: if aggregates reserve N, reduce available by N but since we're picking specific, just limit the slice
        $aggregateHolds = 0;
        if (Schema::hasTable('giu_phong')) {
            $aggregateHolds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->sum('giu_phong.so_luong');
        }

        $maxAvailable = max(0, count($availableIds) - (int)$aggregateHolds);
        $selectedCount = min($maxCount, $maxAvailable);

        return array_slice(array_values($availableIds), 0, $selectedCount);
    }

    public function store(Request $request, Phong $phong)
    {
        Log::info('🔹 Booking.store request:', $request->all());

        try {
            $validated = $request->validate([
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
                'adults' => 'required|integer|min:1',
                'children' => 'nullable|integer|min:0',
                'children_ages' => 'nullable|array',
                'children_ages.*' => 'integer|min:0|max:12',
                'addons' => 'nullable|array',
                'addons.*' => 'integer|exists:tien_nghi,id',
                'rooms_count' => 'required|integer|min:1',
                'name' => 'required|string|max:255|min:2',
                'address' => 'required|string|max:500|min:5',
                'phone' => 'required|string|regex:/^0[3-9]\d{8}$/|unique:dat_phong,contact_phone,NULL,id,nguoi_dung_id,' . Auth::id(),
                'ghi_chu' => 'nullable|string|max:500',
                'spec_signature_hash' => 'nullable|string|size:32',
                'voucher_code' => 'nullable|string|max:50',
                'discount_amount' => 'nullable|numeric|min:0',
            ]);

            $phong->load(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides']);

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
                    $qty = (int) ($bt->pivot->quantity ?? 0);
                    $cap = (int) ($bt->capacity ?? 1);
                    $roomCapacity += $qty * $cap;
                }
            }
            if ($roomCapacity <= 0) $roomCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));
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
            $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);
            $selectedAddonIds = $validated['addons'] ?? [];
            $selectedAddons = collect();
            if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
                $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
            }
            $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
            $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;
            $finalPerNightServer = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
            $snapshotTotalServer = $finalPerNightServer * $nights;

            $discountAmount = 0;
            $voucherId = null;

            if (!empty($validated['voucher_code'])) {
                $voucherCode = strtoupper(trim($validated['voucher_code']));

                $voucher = Voucher::where('code', $voucherCode)
                    ->where('active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->where('qty', '>', 0)
                    ->first();

                if (!$voucher) {
                    throw new \Exception('Mã voucher không hợp lệ hoặc đã hết hạn.');
                }

                // Giới hạn số lần sử dụng / user (nếu có)
                $userId = Auth::id();
                if ($voucher->usage_limit_per_user) {
                    $usageCount = VoucherUsage::where('voucher_id', $voucher->id)
                        ->where('user_id', $userId)
                        ->count();

                    if ($usageCount >= $voucher->usage_limit_per_user) {
                        throw new \Exception('Bạn đã sử dụng hết lượt cho voucher này.');
                    }
                }

                // Tổng trước khi giảm
                $totalBeforeDiscount = $snapshotTotalServer;

                // Check đơn tối thiểu (nếu có)
                if (!empty($voucher->min_order_amount) && $totalBeforeDiscount < $voucher->min_order_amount) {
                    throw new \Exception('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher.');
                }

                // Tính giảm theo type: 'percent' hoặc 'pixed'
                $type = strtolower(trim($voucher->type));
                $value = (float) $voucher->value;

                if ($type === 'percent') {
                    if ($value <= 0) {
                        throw new \Exception('Giá trị phần trăm giảm giá không hợp lệ.');
                    }

                    // value = % giảm, ví dụ 10 => 10%
                    $discountAmount = round($totalBeforeDiscount * ($value / 100));
                } elseif ($type === 'pixed') {
                    if ($value <= 0) {
                        throw new \Exception('Giá trị giảm giá không hợp lệ.');
                    }

                    // value = số tiền giảm cố định
                    $discountAmount = (int) $value;
                } else {
                    throw new \Exception('Loại voucher không hợp lệ.');
                }

                // Không cho giảm vượt quá tổng
                if ($discountAmount > $totalBeforeDiscount) {
                    $discountAmount = $totalBeforeDiscount;
                }

                $voucherId = $voucher->id;

                // Giảm số lượng voucher khả dụng
                $voucher->decrement('qty');
            }

            // Áp dụng giảm giá vào tổng sau khi đã tính xong
            $snapshotTotalServer -= $discountAmount;


            $maThamChieu = 'DP' . strtoupper(Str::random(8));

            $snapshotMeta = [
                'phong_id' => $phong->id,
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
                'addons' => $selectedAddons->map(function ($a) {
                    return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                })->toArray(),
                'final_per_night' => $finalPerNightServer,
                'nights' => $nights,
                'rooms_count' => $roomsCount,
                'tong_tien' => $snapshotTotalServer,
                'contact_name' => $validated['name'],
                'contact_address' => $validated['address'],
                'contact_phone' => $validated['phone'],
                'ghi_chu' => $validated['ghi_chu'] ?? '',
                'discount_amount' => $discountAmount,
                'voucher_code' => $validated['voucher_code'] ?? null,
            ];

            $datPhongId = DB::transaction(function () use ($validated, $maThamChieu, $snapshotMeta, $phong, $snapshotTotalServer, $roomsCount, $from, $to, $finalPerNightServer, $nights, $selectedAddons, $voucherId, $discountAmount) {
                $dat_phong = DatPhong::create([
                    'ma_tham_chieu' => $maThamChieu,
                    'nguoi_dung_id' => Auth::id(),
                    'phong_id' => $phong->id,
                    'ngay_nhan_phong' => $validated['ngay_nhan_phong'],
                    'ngay_tra_phong' => $validated['ngay_tra_phong'],
                    'tong_tien' => $snapshotTotalServer,
                    'so_khach' => $validated['adults'] + ($validated['children'] ?? 0),
                    'trang_thai' => 'dang_cho',
                    'can_thanh_toan' => false,
                    'can_xac_nhan' => true,
                    'created_by' => Auth::id(),
                    'snapshot_meta' => json_encode($snapshotMeta, JSON_UNESCAPED_UNICODE),
                    'contact_name' => $validated['name'],
                    'contact_address' => $validated['address'],
                    'contact_phone' => $validated['phone'],
                    'ghi_chu' => $validated['ghi_chu'] ?? null,
                    'ma_voucher' => $voucherId,
                    'voucher_code' => $validated['voucher_code'] ?? null,
                    'discount_amount' => $discountAmount,
                ]);

                $datPhongId = $dat_phong->id;

                if ($voucherId) {
                    VoucherUsage::create([
                        'voucher_id' => $voucherId,
                        'dat_phong_id' => $datPhongId,
                        'user_id' => Auth::id(),
                        'used_at' => now(),
                    ]);
                }

                Log::info('Booking created', [
                    'dat_phong_id' => $datPhongId,
                    'ma_tham_chieu' => $maThamChieu,
                    'contact_name' => $validated['name'],
                    'contact_phone' => $validated['phone'],
                ]);

                if (Schema::hasTable('loai_phong')) {
                    DB::table('loai_phong')->where('id', $phong->loai_phong_id)->lockForUpdate()->first();
                }

                $baseSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
                $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $baseSignature);
                if ($roomsCount > $availableNow) {
                    throw new \Exception("Only {$availableNow} room(s) available.");
                }

                $holdBase = [
                    'dat_phong_id' => $datPhongId,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'so_luong' => $roomsCount,
                    'het_han_luc' => now()->addMinutes(15),
                    'released' => false,
                    'meta' => null,
                ];

                if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                    $holdBase['spec_signature_hash'] = $baseSignature;
                }

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

                Log::debug('Booking: signatures', [
                    'phong_id' => $phong->id,
                    'phong_db_signature' => $phong->spec_signature_hash ?? null,
                    'requestedSpecSignature' => $requestedSpecSignature,
                    'specArray' => $specArray,
                ]);

                $meta = [
                    'final_per_night' => (float)$finalPerNightServer,
                    'snapshot_total' => (float)$snapshotTotalServer,
                    'nights' => $nights,
                    'rooms_count' => $roomsCount,
                    'addons' => $selectedAddons->map(function ($a) {
                        return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                    })->toArray(),
                    'spec_signature_hash' => $requestedSpecSignature,
                    'requested_spec_signature' => $requestedSpecSignature,
                    'base_spec_signature' => $baseSignature,
                    'discount_amount' => $discountAmount,
                ];

                $requestedPhongId = $phong->id ?? null;
                $requestedReserved = 0;

                if ($requestedPhongId && Schema::hasColumn('giu_phong', 'phong_id')) {
                    $dbRoomSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();

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
                        $locked = Phong::where('id', $requestedPhongId)
                            ->where('trang_thai', 'trong')
                            ->lockForUpdate()
                            ->first();

                        if ($locked) {
                            $row = $holdBase;
                            $row['so_luong'] = 1;
                            $row['phong_id'] = $requestedPhongId;

                            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                $row['spec_signature_hash'] = $dbRoomSignature;
                            }

                            $row['meta'] = json_encode(array_merge($meta, ['selected_phong_id' => $requestedPhongId, 'selected_phong_ids' => [$requestedPhongId]]), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($row);

                            $requestedReserved = 1;
                            Log::debug('Booking: giu_phong inserted per-phong (requested room reserved)', ['phong_id' => $requestedPhongId, 'dat_phong_id' => $datPhongId]);
                        } else {
                            Log::debug('Booking: requested room could not be locked', ['phong_id' => $requestedPhongId]);
                        }
                    } else {
                        Log::debug('Booking: requested room not available to reserve', ['phong_id' => $requestedPhongId, 'isBooked' => $isBooked, 'isHeld' => $isHeld]);
                    }
                }

                if (Schema::hasColumn('giu_phong', 'phong_id')) {
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
                        $locked = Phong::whereIn('id', $selectedIds)
                            ->where('trang_thai', 'trong')
                            ->lockForUpdate()
                            ->get(['id'])
                            ->pluck('id')
                            ->toArray();

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
                            $row['meta'] = json_encode(array_merge($meta, ['selected_phong_id' => $pid, 'selected_phong_ids' => $selectedIds]), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($row);
                            $reservedCount++;
                            Log::debug('Booking: giu_phong inserted per-phong', ['phong_id' => $pid, 'dat_phong_id' => $datPhongId]);
                        }
                    }

                    if ($roomsCount - $reservedCount > 0) {
                        $aggRow = $holdBase;
                        $aggRow['so_luong'] = $roomsCount - $reservedCount;
                        if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                            $aggRow['spec_signature_hash'] = $baseSignature;
                        }
                        $aggRow['meta'] = json_encode(array_merge($meta, ['reserved_count' => $reservedCount]), JSON_UNESCAPED_UNICODE);
                        DB::table('giu_phong')->insert($aggRow);
                        Log::debug('Booking: giu_phong inserted aggregate for remaining', ['remaining' => $roomsCount - $reservedCount, 'dat_phong_id' => $datPhongId]);
                    }
                } else {
                    $aggRow = $holdBase;
                    $aggRow['spec_signature_hash'] = $requestedSpecSignature;
                    $aggRow['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
                    DB::table('giu_phong')->insert($aggRow);
                    Log::debug('Booking: giu_phong inserted (no phong_id column)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                }

                return $datPhongId;
            });

            return redirect()->route('account.booking.create', $phong->id)
                ->with('success', 'Room(s) held for 15 minutes. Please proceed to payment to confirm the booking.')
                ->with('dat_phong_id', $datPhongId);
        } catch (\Throwable $e) {
            Log::error('Booking.store exception: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->withErrors(['error' => 'Could not create booking: ' . $e->getMessage()]);
        }
    }

    public function validateVoucher(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50',
            'phong_id' => 'required|integer|exists:phong,id',
            'ngay_nhan_phong' => 'required|date',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'integer|min:0|max:12',
            'addons' => 'nullable|array',
            'rooms_count' => 'required|integer|min:1',
        ]);

        $code = strtoupper(trim($request->code));
        $voucher = Voucher::where('code', $code)
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('qty', '>', 0)
            ->first();

        if (!$voucher) {
            return response()->json(['error' => 'Mã voucher không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // Kiểm tra usage limit per user
        $userId = Auth::id();
        $usageCount = VoucherUsage::where('voucher_id', $voucher->id)
            ->where('user_id', $userId)
            ->count();
        if ($voucher->usage_limit_per_user && $usageCount >= $voucher->usage_limit_per_user) {
            return response()->json(['error' => 'Bạn đã sử dụng hết lượt cho voucher này.'], 400);
        }

        // Kiểm tra min_order_amount
        $phong = Phong::findOrFail($request->phong_id);
        $nights = $this->calculateNights($request->ngay_nhan_phong, $request->ngay_tra_phong);
        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);
        $roomsCount = $request->rooms_count;
        $adultsInput = $request->adults;
        $childrenInput = $request->children ?? 0;
        $childrenAges = $request->children_ages ?? [];
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
                $qty = (int) ($bt->pivot->quantity ?? 0);
                $cap = (int) ($bt->capacity ?? 1);
                $roomCapacity += $qty * $cap;
            }
        }
        if ($roomCapacity <= 0) $roomCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));
        $totalRoomCapacity = $roomCapacity * $roomsCount;
        $countedPersons = $computedAdults + $chargeableChildren;
        $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
        $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
        $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
        $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
        $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);
        $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
        $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;
        $selectedAddonIds = $request->addons ?? [];
        $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
        $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
        $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;
        $finalPerNight = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
        $totalBeforeDiscount = $finalPerNight * $nights;

        if ($voucher->min_order_amount && $totalBeforeDiscount < $voucher->min_order_amount) {
            return response()->json(['error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher.'], 400);
        }

        // Tính discount_amount
        $discountAmount = $voucher->type === 'phan_tram'
            ? ($totalBeforeDiscount * $voucher->value / 100)
            : $voucher->value;
        $discountAmount = min($discountAmount, $totalBeforeDiscount); // Không giảm quá total

        return response()->json([
            'success' => true,
            'discount_amount' => $discountAmount,
            'voucher_id' => $voucher->id,
            'message' => 'Voucher áp dụng thành công! Giảm ' . number_format($discountAmount) . ' VND.',
        ]);
    }

    private function calculateNights($from, $to)
    {
        return Carbon::parse($from)->diffInDays(Carbon::parse($to));
    }
public function applyVoucher(Request $request)
{
    try {
        $code = strtoupper(trim($request->input('code')));
        $totalRaw = (string) $request->input('total', '0');
        $total = (int) preg_replace('/\D/', '', $totalRaw);

        if ($total <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Giá trị đơn hàng không hợp lệ.',
            ]);
        }

        $voucher = Voucher::where('code', $code)->first();
        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá không tồn tại.',
            ]);
        }

        $today = Carbon::today()->toDateString();
        $start = $voucher->start_date ? Carbon::parse($voucher->start_date)->toDateString() : null;
        $end   = $voucher->end_date ? Carbon::parse($voucher->end_date)->toDateString() : null;

        if (
            !$voucher->active ||
            ($start && $start > $today) ||
            ($end && $end < $today) ||
            ($voucher->qty !== null && $voucher->qty <= 0)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Mã giảm giá đã hết hạn, chưa có hiệu lực hoặc đã hết lượt.',
            ]);
        }

        // ===== Giới hạn lượt dùng / user (nếu cấu trúc bảng cho phép) =====
        $userId = Auth::id();
        if (!empty($voucher->usage_limit_per_user) && $userId) {
            if (class_exists(VoucherUsage::class)) {
                $usageModel = new VoucherUsage();
                $table = $usageModel->getTable();

                if (Schema::hasTable($table)) {
                    // Tự tìm cột user: ưu tiên user_id, fallback nguoi_dung_id
                    $userCol = null;
                    if (Schema::hasColumn($table, 'user_id')) {
                        $userCol = 'user_id';
                    } elseif (Schema::hasColumn($table, 'nguoi_dung_id')) {
                        $userCol = 'nguoi_dung_id';
                    }

                    if ($userCol) {
                        $usageCount = VoucherUsage::where('voucher_id', $voucher->id)
                            ->where($userCol, $userId)
                            ->count();

                        if ($usageCount >= $voucher->usage_limit_per_user) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Bạn đã sử dụng hết lượt cho mã giảm giá này.',
                            ]);
                        }
                    }
                    // Nếu không có cột user -> bỏ qua check per-user, không được thì sau này bổ sung schema.
                }
            }
        }

        // ===== Đơn tối thiểu =====
        if (!empty($voucher->min_order_amount) && $total < $voucher->min_order_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã này.',
            ]);
        }

        // ===== Tính giảm giá: percent / pixed =====
        $type = strtolower(trim($voucher->type));
        $value = (float) $voucher->value;
        $discount = 0;

        if ($type === 'percent') {
            if ($value <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giá trị phần trăm giảm giá không hợp lệ.',
                ]);
            }
            $discount = (int) round($total * ($value / 100));
        } elseif ($type === 'fixed') {
            if ($value <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giá trị giảm giá không hợp lệ.',
                ]);
            }
            $discount = (int) $value;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Loại mã giảm giá không hợp lệ (chỉ hỗ trợ percent hoặc fixed).',
            ]);
        }

        if ($discount > $total) {
            $discount = $total;
        }

        $finalTotal = $total - $discount;
        $deposit = (int) round($finalTotal * 0.2);

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng mã giảm giá thành công.',
            'voucher_name' => $voucher->name,
            'type' => $type,
            'value' => $value,
            'discount' => $discount,
            'final_total' => $finalTotal,
            'deposit' => $deposit,
            'discount_display' => number_format($discount, 0, ',', '.'),
            'final_total_display' => number_format($finalTotal, 0, ',', '.'),
            'deposit_display' => number_format($deposit, 0, ',', '.'),
        ]);
    } catch (\Throwable $e) {
        Log::error('applyVoucher error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Có lỗi nội bộ khi áp dụng mã giảm giá.',
        ], 500);
    }
}


}
