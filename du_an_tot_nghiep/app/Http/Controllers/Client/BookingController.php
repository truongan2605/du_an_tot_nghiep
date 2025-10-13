<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\PhongTienNghiOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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

        $candidates = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])
            ->where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
            ->get();

        if ($candidates->isEmpty()) {
            return 0;
        }

        if ($requiredSignature === null) {
            $requiredSignature = $candidates->first()->specSignatureHash();
        }

        $matchingRooms = $candidates->filter(function ($r) use ($requiredSignature) {
            return $r->specSignatureHash() === $requiredSignature;
        })->values();

        if ($matchingRooms->isEmpty()) {
            return 0;
        }

        $matchingRoomIds = $matchingRooms->pluck('id')->toArray();
        $matchingCount = count($matchingRoomIds);

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_items') && Schema::hasColumn('dat_phong_items', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_items')
                ->join('dat_phong', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?", [$requestedStart, $requestedEnd])
                ->pluck('phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->where('released', false)
                ->where('loai_phong_id', $loaiPhongId)
                ->where('het_han_luc', '>', now())
                ->pluck('phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        $occupiedSpecificIds = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        $matchingAvailableIds = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $available = count($matchingAvailableIds);

        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong, ' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong, ' 14:00:00') < ?", [$requestedStart, $requestedEnd]);

            $aggregateBooked = (int) $q->sum('dat_phong_item.so_luong');
        } elseif (Schema::hasTable('dat_phong_items')) {
            if (Schema::hasColumn('dat_phong_items', 'phong_id')) {
            } else {
                $q = DB::table('dat_phong')
                    ->join('dat_phong_items', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                    ->where('dat_phong_items.loai_phong_id', $loaiPhongId)
                    ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                    ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong, ' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong, ' 14:00:00') < ?", [$requestedStart, $requestedEnd]);

                $aggregateBooked = (int)$q->sum('dat_phong_items.so_luong');
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

        $specificBookedCount = count($bookedRoomIds);
        $specificHeldCount = count($heldRoomIds);

        $aggregateToSubtract = max(0, ($aggregateBooked + $aggregateHolds) - ($specificBookedCount + $specificHeldCount));

        $available = max(0, $available - $aggregateToSubtract);

        return (int) $available;
    }


    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $limit = 1, ?string $requiredSignature = null): array
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0)->toDateTimeString();
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0)->toDateTimeString();

        $candidates = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])
            ->where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
            ->get();

        if ($requiredSignature === null) {
            if ($candidates->isEmpty()) return [];
            $requiredSignature = $candidates->first()->specSignatureHash();
        }

        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_items') && Schema::hasColumn('dat_phong_items', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_items')
                ->join('dat_phong', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("? < CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') AND CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ?", [$requestedStart, $requestedEnd])
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->where('released', false)
                ->where('het_han_luc', '>', now())
                ->where('loai_phong_id', $loaiPhongId)
                ->pluck('phong_id')->filter()->unique()->toArray();
        }

        $results = [];
        foreach ($candidates as $room) {
            if (in_array($room->id, $bookedRoomIds) || in_array($room->id, $heldRoomIds)) {
                continue;
            }
            if ($room->specSignatureHash() === $requiredSignature) {
                $results[] = $room->id;
                if (count($results) >= $limit) break;
            }
        }

        return $results;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to make a booking.');
        }

        $request->validate([
            'phong_id' => 'required|exists:phong,id',
            'ngay_nhan_phong' => 'required|date',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0|max:2',
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

        $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides'])->findOrFail($request->input('phong_id'));

        $from = Carbon::parse($request->input('ngay_nhan_phong'))->startOfDay();
        $to = Carbon::parse($request->input('ngay_tra_phong'))->startOfDay();
        $nights = $from->diffInDays($to);
        if ($nights <= 0) {
            return back()->withInput()->withErrors(['ngay_tra_phong' => 'Check-out date must be after check-in date.']);
        }

        $adultsInput = (int)$request->input('adults', 1);
        $childrenInput = (int)$request->input('children', 0);
        $childrenAges = $request->input('children_ages', []);

        if ($childrenInput > 0) {
            $provided = is_array($childrenAges) ? count($childrenAges) : 0;
            if ($provided !== $childrenInput) {
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
            } else {
                // <7 free and not counted
            }
        }

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

        $maxAllowed = $roomCapacity + 2;
        $countedPersons = $computedAdults + $chargeableChildren;
        if ($countedPersons > $maxAllowed) {
            return back()->withInput()->withErrors(['error' => "Maximum allowed guests for this room is {$maxAllowed} (including up to 2 extra). You provided {$countedPersons}."]);
        }

        $extraCount = max(0, $countedPersons - $roomCapacity);
        $adultBeyondBase = max(0, $computedAdults - $roomCapacity);
        $adultExtra = min($adultBeyondBase, $extraCount);
        $childrenExtra = max(0, $extraCount - $adultExtra);
        $childrenExtra = min($childrenExtra, $chargeableChildren);

        $roomsCount = (int) $request->input('rooms_count', 1);
        if ($roomsCount < 1) $roomsCount = 1;

        $selectedAddonIds = $request->input('addons', []);
        $addonsPerNight = 0.0;
        $selectedAddons = collect();
        if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
            $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
            $addonsPerNight = (float) $selectedAddons->sum('gia');
        }

        $basePerNight = (float) ($phong->gia_cuoi_cung ?? $phong->gia_mac_dinh ?? 0);

        DB::beginTransaction();
        try {
            $requiredSignature = $phong->specSignatureHash();
            $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $requiredSignature);
            if ($roomsCount > $availableNow) {
                DB::rollBack();
                return back()->withInput()->withErrors(['rooms_count' => "Only {$availableNow} room(s) matching required features are available for selected dates (concurrency check)."]);
            }

            $adultsChargePerNightPerRoom = $adultExtra * self::ADULT_PRICE;
            $childrenChargePerNightPerRoom = $childrenExtra * self::CHILD_PRICE;

            $adultsChargePerNight = $adultsChargePerNightPerRoom * $roomsCount;
            $childrenChargePerNight = $childrenChargePerNightPerRoom * $roomsCount;

            $finalPerNight = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
            $snapshotTotal = $finalPerNight * $nights;

            $datPhongId = DB::table('dat_phong')->insertGetId([
                'nguoi_dung_id' => $user->id,
                'ngay_nhan_phong' => $from->toDateString(),
                'ngay_tra_phong' => $to->toDateString(),
                'so_khach' => $adultsInput + $childrenInput,
                'trang_thai' => 'dang_cho',
                'tong_tien' => $snapshotTotal,
                'snapshot_total' => $snapshotTotal,
                'ghi_chu' => $request->input('ghi_chu', null),
                'phuong_thuc' => $request->input('phuong_thuc', null),
                'created_at' => now(),
                'updated_at' => now(),
                'contact_name' => $request->input('name'),
                'contact_address' => $request->input('address'),
                'contact_phone' => $request->input('phone', $user->so_dien_thoai ?? null),
                'snapshot_meta' => json_encode([
                    'rooms_count' => $roomsCount,
                    'adults_input' => $adultsInput,
                    'children_input' => $childrenInput,
                    'children_ages' => $childrenAges,
                    'computed_adults' => $computedAdults,
                    'chargeable_children' => $chargeableChildren,
                    'room_capacity' => $roomCapacity,
                    'max_allowed' => $maxAllowed,
                    'extra_count' => $extraCount,
                    'adult_extra' => $adultExtra,
                    'children_extra' => $childrenExtra,
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
            ]);

            if (Schema::hasTable('dat_phong_item')) {
                DB::table('dat_phong_item')->insert([
                    'dat_phong_id' => $datPhongId,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'so_luong' => $roomsCount,
                    'gia_tren_dem' => $basePerNight,
                    'tong_item' => ($basePerNight * $roomsCount) * $nights,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (Schema::hasTable('dat_phong_items')) {
                if (Schema::hasColumn('dat_phong_items', 'phong_id')) {
                    $selectedIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $roomsCount, $requiredSignature);

                    if (count($selectedIds) < $roomsCount) {
                        DB::rollBack();
                        return back()->withInput()->withErrors(['rooms_count' => "Only " . count($selectedIds) . " room(s) matching required features are available for the selected dates."]);
                    }

                    foreach ($selectedIds as $rid) {
                        DB::table('dat_phong_items')->insert([
                            'dat_phong_id' => $datPhongId,
                            'phong_id' => $rid,
                            'loai_phong_id' => $phong->loai_phong_id,
                            'so_luong' => 1,
                            'gia_tren_dem' => $basePerNight,
                            'tong_item' => $basePerNight * $nights,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    if (!empty($selectedAddons) && $selectedAddons->count() > 0) {
                        $checkoutAt = $to->copy()->setTime(12, 0, 0);
                        foreach ($selectedIds as $rid) {
                            $room = Phong::with(['tienNghis', 'activeOverrides'])->find($rid);
                            $existing = $room ? $room->effectiveTienNghiIds() : [];

                            foreach ($selectedAddons as $addon) {
                                if (!in_array((int)$addon->id, $existing)) {
                                    PhongTienNghiOverride::create([
                                        'phong_id' => $rid,
                                        'tien_nghi_id' => $addon->id,
                                        'applies_to_dat_phong_id' => $datPhongId,
                                        'expires_at' => $checkoutAt,
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    DB::table('dat_phong_items')->insert([
                        'dat_phong_id' => $datPhongId,
                        'loai_phong_id' => $phong->loai_phong_id,
                        'so_luong' => $roomsCount,
                        'gia_tren_dem' => $basePerNight,
                        'tong_item' => ($basePerNight * $roomsCount) * $nights,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($selectedAddons->isNotEmpty()) {
                if (Schema::hasTable('dat_phong_addon')) {
                    foreach ($selectedAddons as $a) {
                        DB::table('dat_phong_addon')->insert([
                            'dat_phong_id' => $datPhongId,
                            'tien_nghi_id' => $a->id,
                            'gia' => $a->gia,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } elseif (Schema::hasTable('dat_phong_addons')) {
                    foreach ($selectedAddons as $a) {
                        DB::table('dat_phong_addons')->insert([
                            'dat_phong_id' => $datPhongId,
                            'tien_nghi_id' => $a->id,
                            'gia' => $a->gia,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            if (Schema::hasTable('giu_phong')) {
                if (Schema::hasColumn('giu_phong', 'so_luong')) {
                    DB::table('giu_phong')->insert([
                        'dat_phong_id' => $datPhongId,
                        'loai_phong_id' => $phong->loai_phong_id,
                        'so_luong' => $roomsCount,
                        'het_han_luc' => now()->addMinutes(15),
                        'released' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('giu_phong')->insert([
                        'dat_phong_id' => $datPhongId,
                        'loai_phong_id' => $phong->loai_phong_id,
                        'het_han_luc' => now()->addMinutes(15),
                        'released' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('account.booking.create', $phong->id)
                ->with('success', 'Room(s) held for 15 minutes. Please proceed to payment to confirm the booking.')
                ->with('dat_phong_id', $datPhongId);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Could not create booking: ' . $e->getMessage()]);
        }
    }
}
