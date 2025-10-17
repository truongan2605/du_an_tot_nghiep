<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

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

        // Quan trọng: Logic tính phòng giống loại phòng
        $availableRoomsDefault = $this->computeAvailableRoomsCount(
            $phong->loai_phong_id,
            $fromDefault,
            $toDefault,
            $phong->specSignatureHash()
        );

        return view('account.booking.create', compact('phong', 'user', 'availableAddons', 'availableRoomsDefault'));
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
                $requiredSignature = $phong->specSignatureHash();
            }
        }

        $available = $this->computeAvailableRoomsCount($loaiId, $from, $to, $requiredSignature);

        if ($request->boolean('debug')) {
            $candidates = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])
                ->where('loai_phong_id', $loaiId)
                ->where('trang_thai', 'trong')
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
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0)->toDateTimeString();
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0)->toDateTimeString();

        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->where('trang_thai', 'trong')->first();
            if (!$sample) return 0;
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        // Các phòng thuộc loại này & có cùng signature (và đang 'trong')
        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
            ->where('spec_signature_hash', $requiredSignature)
            ->pluck('id')->toArray();

        if (empty($matchingRoomIds)) return 0;

        // -------------------------------
        // 1) Specific occupied (phong_id) — booked & held for this signature
        // -------------------------------
        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?", [$requestedStart, $requestedEnd])
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->where('released', false)
                ->where('loai_phong_id', $loaiPhongId)
                ->where('het_han_luc', '>', now())
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        // Among matchingRoomIds, which are free (not specifically booked/held)
        $occupiedSpecificIds = array_unique(array_merge($bookedRoomIds, $heldRoomIds));
        $matchingAvailableIds = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $matchingAvailableCount = count($matchingAvailableIds);

        // -------------------------------
        // 2) Compute aggregate totals for the whole loai_phong
        // -------------------------------
        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong, ' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong, ' 14:00:00') < ?", [$requestedStart, $requestedEnd]);

            if (Schema::hasColumn('dat_phong_item', 'so_luong')) {
                $aggregateBooked = (int) $q->sum('dat_phong_item.so_luong');
            } else {
                $aggregateBooked = (int) $q->count();
            }
        }

        $aggregateHolds = 0;
        if (Schema::hasTable('giu_phong')) {
            $qg = DB::table('giu_phong')
                ->where('released', false)
                ->where('loai_phong_id', $loaiPhongId)
                ->where('het_han_luc', '>', now());

            if (Schema::hasColumn('giu_phong', 'so_luong')) {
                $aggregateHolds = (int) $qg->sum('so_luong');
            } elseif (!Schema::hasColumn('giu_phong', 'phong_id')) {
                $aggregateHolds = (int) $qg->count();
            }
        }

        // -------------------------------
        // 3) Determine total actual rooms of this type available to be used
        //    Use so_luong_thuc_te if present (LoaiPhong), fall back to counting Phong rows with trang_thai='trong'
        // -------------------------------
        $totalRoomsOfType = 0;
        if (Schema::hasTable('loai_phong') && Schema::hasColumn('loai_phong', 'so_luong_thuc_te')) {
            $totalRoomsOfType = (int) DB::table('loai_phong')->where('id', $loaiPhongId)->value('so_luong_thuc_te');
        }
        if ($totalRoomsOfType <= 0) {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)
                ->where('trang_thai', 'trong')
                ->count();
        }

        // Remaining capacity across the whole type
        $remainingAcrossType = max(0, $totalRoomsOfType - $aggregateBooked - $aggregateHolds);

        // The available for this signature is min(rooms that are physically matching & not specifically occupied, 
        // and remaining capacity across the whole type)
        $availableForSignature = max(0, min($matchingAvailableCount, $remainingAcrossType));

        return (int) $availableForSignature;
    }


    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $limit = 1, ?string $requiredSignature = null): array
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0)->toDateTimeString();
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0)->toDateTimeString();

        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->where('trang_thai', 'trong')->first();
            if (!$sample) return [];
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?", [$requestedStart, $requestedEnd])
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->where('released', false)
                ->where('loai_phong_id', $loaiPhongId)
                ->where('het_han_luc', '>', now())
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        $excluded = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        $query = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
            ->where('spec_signature_hash', $requiredSignature)
            ->when(!empty($excluded), function ($q) use ($excluded) {
                $q->whereNotIn('id', $excluded);
            })
            ->lockForUpdate()
            ->limit((int)$limit);

        $rows = $query->get(['id']);

        return $rows->pluck('id')->toArray();
    }

    public function store(Request $request)
    {
        Log::debug('Booking.store called', [
            'url' => url()->current(),
            'session_id' => session()->getId(),
            'cookies' => request()->cookies->all(),
            'input_keys' => array_keys($request->all()),
            'raw_input' => $request->all()
        ]);

        $user = $request->user();
        if (!$user) {
            Log::debug('Booking.store: no authenticated user');
            return redirect()->route('login')->with('error', 'You must be logged in to make a booking.');
        }

        $request->validate([
            'phong_id' => 'required|exists:phong,id',
            'ngay_nhan_phong' => 'required|date',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'nullable|integer|min:0|max:12',
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:tien_nghi,id',
            'ghi_chu' => 'nullable|string|max:1000',
            'phuong_thuc' => 'nullable|string|max:100',
            'rooms_count' => 'nullable|integer|min:1',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:50',
        ]);

        Log::debug('Booking: validation passed');

        $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides'])->findOrFail($request->input('phong_id'));
        Log::debug('Booking: loaded phong', ['phong_id' => $phong->id]);

        $from = Carbon::parse($request->input('ngay_nhan_phong'))->startOfDay();
        $to = Carbon::parse($request->input('ngay_tra_phong'))->startOfDay();
        $nights = $from->diffInDays($to);
        Log::debug('Booking: parsed dates', ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'nights' => $nights]);

        if ($nights <= 0) {
            Log::warning('Booking: invalid nights <= 0', ['nights' => $nights]);
            return back()->withInput()->withErrors(['ngay_tra_phong' => 'Check-out date must be after check-in date.']);
        }

        $adultsInput = (int)$request->input('adults', 1);
        $childrenInput = (int)$request->input('children', 0);
        $childrenAges = $request->input('children_ages', []);

        Log::debug('Booking: guest inputs', ['adults' => $adultsInput, 'children' => $childrenInput, 'children_ages' => $childrenAges]);

        if ($childrenInput > 0) {
            $provided = is_array($childrenAges) ? count($childrenAges) : 0;
            if ($provided !== $childrenInput) {
                Log::warning('Booking: children ages count mismatch', ['expected' => $childrenInput, 'provided' => $provided]);
                return back()->withInput()->withErrors(['children_ages' => 'Please provide ages for each child.']);
            }
        }

        $computedAdults = $adultsInput;
        $chargeableChildren = 0;
        foreach ($childrenAges as $age) {
            $age = (int)$age;
            if ($age >= 13) {
                $computedAdults++;
            } elseif ($age >= 7) {
                $chargeableChildren++;
            }
        }
        Log::debug('Booking: computedAdults/chargeableChildren', ['computedAdults' => $computedAdults, 'chargeableChildren' => $chargeableChildren]);

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
        Log::debug('Booking: roomCapacity computed', ['roomCapacity' => $roomCapacity]);

        $roomsCount = (int) $request->input('rooms_count', 1);
        if ($roomsCount < 1) $roomsCount = 1;
        Log::debug('Booking: roomsCount', ['roomsCount' => $roomsCount]);

        $selectedAddonIds = $request->input('addons', []);
        $selectedAddons = collect();
        if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
            $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
        }
        $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
        $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;
        Log::debug('Booking: addons', ['selectedAddonIds' => $selectedAddonIds, 'addonsPerNight' => $addonsPerNight]);

        $childrenMaxAllowed = 2 * $roomsCount;
        if ($childrenInput > $childrenMaxAllowed) {
            Log::warning('Booking: too many children', ['childrenInput' => $childrenInput, 'childrenMaxAllowed' => $childrenMaxAllowed]);
            return back()->withInput()->withErrors(['children' => "Maximum {$childrenMaxAllowed} children allowed for {$roomsCount} room(s)."]);
        }

        $totalRoomCapacity = $roomCapacity * $roomsCount;
        $countedPersons = $computedAdults + $chargeableChildren;
        $totalMaxAllowed = $totalRoomCapacity + (2 * $roomsCount);
        if ($countedPersons > $totalMaxAllowed) {
            Log::warning('Booking: too many guests', ['countedPersons' => $countedPersons, 'totalMaxAllowed' => $totalMaxAllowed]);
            return back()->withInput()->withErrors(['error' => "Maximum allowed guests for {$roomsCount} room(s) is {$totalMaxAllowed}. You provided {$countedPersons}."]);
        }

        $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
        $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
        $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
        $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
        $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);

        $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
        $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;

        $basePerNight = (float) ($phong->gia_cuoi_cung ?? $phong->gia_mac_dinh ?? 0);

        Log::debug('Booking: pricing summary', [
            'basePerNight' => $basePerNight,
            'adultsChargePerNight' => $adultsChargePerNight,
            'childrenChargePerNight' => $childrenChargePerNight,
            'addonsPerNight' => $addonsPerNight
        ]);

        DB::beginTransaction();
        try {
            Log::debug('Booking: before concurrency check');

            $requiredSignature = $phong->specSignatureHash();

            $hasItemsTable = Schema::hasTable('dat_phong_item');
            $itemsHasPhongId = $hasItemsTable && Schema::hasColumn('dat_phong_item', 'phong_id');

            if ($hasItemsTable && !$itemsHasPhongId) {
                DB::table('loai_phong')->where('id', $phong->loai_phong_id)->lockForUpdate()->first();
                Log::debug('Booking: locked loai_phong for aggregate booking', ['loai_phong_id' => $phong->loai_phong_id]);
            }

            $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $requiredSignature);
            Log::debug('Booking: concurrency check result', ['availableNow' => $availableNow]);

            if ($roomsCount > $availableNow) {
                DB::rollBack();
                Log::warning('Booking: not enough rooms available', ['roomsCount' => $roomsCount, 'availableNow' => $availableNow]);
                return back()->withInput()->withErrors(['rooms_count' => "Only {$availableNow} room(s) matching required features are available for selected dates (concurrency check)."]);
            }

            $finalPerNight = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
            $snapshotTotal = $finalPerNight * $nights;

            Log::debug('Booking: about to insert dat_phong', [
                'finalPerNight' => $finalPerNight,
                'snapshotTotal' => $snapshotTotal,
                'nights' => $nights
            ]);

            $maThamChieu = 'BK' . Str::upper(Str::random(8));

            $payload = [
                'ma_tham_chieu' => $maThamChieu,
                'nguoi_dung_id' => $user->id,
                'created_by' => $user->id,
                'ngay_nhan_phong' => $from->toDateString(),
                'ngay_tra_phong' => $to->toDateString(),
                'so_khach' => ($adultsInput + $childrenInput),
                'trang_thai' => 'dang_cho',
                'tong_tien' => $snapshotTotal,
                'snapshot_total' => $snapshotTotal,
                'ghi_chu' => $request->input('ghi_chu', null),
                'phuong_thuc' => $request->input('phuong_thuc'),
                'created_at' => now(),
                'updated_at' => now(),
                'contact_name'    => $request->input('name'),
                'contact_address' => $request->input('address'),
                'contact_phone'   => $request->input('phone', $user->so_dien_thoai ?? null),
                'snapshot_meta' => json_encode([
                    'rooms_count' => $roomsCount,
                    'adults_input' => $adultsInput,
                    'children_input' => $childrenInput,
                    'children_ages' => $childrenAges,
                    'computed_adults' => $computedAdults,
                    'chargeable_children' => $chargeableChildren,
                    'room_capacity_single' => $roomCapacity,
                    'total_room_capacity' => $totalRoomCapacity ?? ($roomCapacity * $roomsCount),
                    'counted_persons' => $countedPersons,
                    'extra_count_total' => $extraCountTotal ?? 0,
                    'adult_extra_total' => $adultExtraTotal ?? 0,
                    'children_extra_total' => $childrenExtraTotal ?? 0,
                    'room_base_per_night' => $basePerNight,
                    'adults_charge_per_night' => $adultsChargePerNight,
                    'children_charge_per_night' => $childrenChargePerNight,
                    'addons_per_night' => $addonsPerNight,
                    'addons' => $selectedAddons->map(function ($a) {
                        return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                    })->toArray(),
                    'final_per_night' => $finalPerNight,
                    'nights' => $nights,
                ]),
            ];

            $allowedPayload = [];
            foreach ($payload as $k => $v) {
                if (Schema::hasColumn('dat_phong', $k)) {
                    $allowedPayload[$k] = $v;
                } else {
                    Log::debug('Booking: dat_phong column missing, skipping', ['column' => $k]);
                }
            }

            Log::debug('Booking: inserting dat_phong payload keys', ['keys' => array_keys($allowedPayload)]);

            $datPhongId = DB::table('dat_phong')->insertGetId($allowedPayload);

            Log::debug('Booking: dat_phong inserted', ['dat_phong_id' => $datPhongId, 'ma_tham_chieu' => $maThamChieu]);


            if (Schema::hasTable('giu_phong')) {
                $holdPayload = [
                    'dat_phong_id' => $datPhongId,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'so_luong' => $roomsCount,
                    'het_han_luc' => now()->addMinutes(15),
                    'released' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $selectedAddonIdsArr = $selectedAddons->pluck('id')->map('intval')->toArray();
                $baseTienNghi = method_exists($phong, 'effectiveTienNghiIds') ? $phong->effectiveTienNghiIds() : [];
                $mergedTienNghi = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
                sort($mergedTienNghi, SORT_NUMERIC);
                $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

                $specArray = [
                    'loai_phong_id' => (int)$phong->loai_phong_id,
                    'tien_nghi' => $mergedTienNghi,
                    'beds' => $bedSpec,
                ];
                $spec_signature_hash = md5(json_encode($specArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $holdPayload['spec_signature_hash'] = $spec_signature_hash;

                $holdMeta = [
                    'final_per_night' => (float)$finalPerNight,
                    'snapshot_total' => (float)$snapshotTotal,
                    'nights' => $nights,
                    'rooms_count' => $roomsCount,
                    'addons' => $selectedAddons->map(function ($a) {
                        return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                    })->toArray()
                ];

                // if DB has phong_id column, attempt to pick specific IDs (for rooms_count == 1 prefer specific phong_id)
                if (Schema::hasColumn('giu_phong', 'phong_id')) {
                    if ($roomsCount === 1) {
                        $selectedIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, 1, $requiredSignature);
                        if (count($selectedIds) >= 1) {
                            $holdPayload['phong_id'] = $selectedIds[0];
                            // so_luong already set to 1 above
                            $holdPayload['meta'] = json_encode($holdMeta, JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($holdPayload);
                            Log::debug('Booking: giu_phong inserted with phong_id', ['giu_phong_phong_id' => $selectedIds[0], 'dat_phong_id' => $datPhongId]);
                        } else {
                            // fallback to aggregate hold (so_luong)
                            $holdPayload['meta'] = json_encode($holdMeta, JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($holdPayload);
                            Log::debug('Booking: giu_phong inserted with so_luong fallback (no specific phong_id available)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                        }
                    } else {
                        // multiple rooms requested: try to reserve specific ids for all rooms
                        $selectedIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $roomsCount, $requiredSignature);
                        if (count($selectedIds) === $roomsCount) {
                            // store selected ids in meta so we know which specific rooms were held for this dat_phong
                            $holdMeta['selected_phong_ids'] = $selectedIds;
                            $holdPayload['meta'] = json_encode($holdMeta, JSON_UNESCAPED_UNICODE);
                            // keep phong_id NULL (we have list in meta)
                            DB::table('giu_phong')->insert($holdPayload);
                            Log::debug('Booking: giu_phong inserted with multiple specific phong_ids (stored in meta)', ['selected_phong_ids' => $selectedIds, 'dat_phong_id' => $datPhongId]);
                        } else {
                            // fallback to aggregate
                            $holdPayload['meta'] = json_encode($holdMeta, JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($holdPayload);
                            Log::debug('Booking: giu_phong inserted aggregate (could not get all specific phong_ids)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                        }
                    }
                } else {
                    // giu_phong table doesn't have phong_id column: insert aggregate hold
                    $holdPayload['meta'] = json_encode($holdMeta, JSON_UNESCAPED_UNICODE);
                    DB::table('giu_phong')->insert($holdPayload);
                    Log::debug('Booking: giu_phong inserted (no phong_id column in giu_phong table)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                }
            } else {
                Log::debug('Booking: giu_phong table not present, skipping hold creation');
            }

            DB::commit();
            Log::debug('Booking: transaction committed', ['dat_phong_id' => $datPhongId]);

            return redirect()->route('account.booking.create', $phong->id)
                ->with('success', 'Room(s) held for 15 minutes. Please proceed to payment to confirm the booking.')
                ->with('dat_phong_id', $datPhongId);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Booking.store exception: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->withErrors(['error' => 'Could not create booking: ' . $e->getMessage()]);
        }
    }
}
