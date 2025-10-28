<?php

namespace App\Http\Controllers\Client;

use App\Events\BookingCreated;
use App\Http\Controllers\Controller;
use App\Models\DatPhong;
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
            ->with(['datPhongItems.phong', 'datPhongItems.loaiPhong'])
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

    // Giá tiền với mỗi người quá số người mặc định của mỗi phòng
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

        $availableRoomsDefault = $this->computeAvailableRoomsCount(
            $phong->loai_phong_id,
            $fromDefault,
            $toDefault,
            $phong->spec_signature_hash ?? $phong->specSignatureHash()
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
                $requiredSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
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
        // Build requested interval (start inclusive at 14:00, end exclusive at 12:00)
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->where('trang_thai', 'trong')->first();
            if (!$sample) return 0;
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        // All candidate rooms of this type+signature and in usable state
        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('trang_thai', 'trong')
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
        $matchingAvailableIds = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $matchingAvailableCount = count($matchingAvailableIds);

        // 4) Aggregate booked from dat_phong_item (rows without phong_id) overlapping the interval
        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->whereNull('dat_phong_item.phong_id');

            if (Schema::hasColumn('dat_phong_item', 'so_luong')) {
                $aggregateBooked = (int) $q->sum('dat_phong_item.so_luong');
            } else {
                $aggregateBooked = (int) $q->count();
            }
        }

        // 5) Aggregate holds (giu_phong rows without phong_id) that overlap the same dat_phong interval and match signature when available
        $aggregateHoldsForSignature = 0;
        if (Schema::hasTable('giu_phong')) {
            $qg = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id') // use dat_phong to determine the interval
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr]);

            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                $qg = $qg->where('giu_phong.spec_signature_hash', $requiredSignature);
                if (Schema::hasColumn('giu_phong', 'so_luong')) {
                    $aggregateHoldsForSignature = (int) $qg->sum('giu_phong.so_luong');
                } else {
                    // fallback: count rows (shouldn't typically happen)
                    $aggregateHoldsForSignature = (int) $qg->count();
                }
            } else {
                // no spec_signature_hash column: check meta for matching signature and sum meta.rooms_count (or default 1)
                $holdsMeta = $qg->whereNotNull('giu_phong.meta')->pluck('giu_phong.meta');
                foreach ($holdsMeta as $metaRaw) {
                    if (!$metaRaw) continue;
                    $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                    if (!is_array($decoded)) continue;
                    if (isset($decoded['spec_signature_hash']) && $decoded['spec_signature_hash'] === $requiredSignature) {
                        $aggregateHoldsForSignature += (isset($decoded['rooms_count']) ? (int)$decoded['rooms_count'] : 1);
                    }
                }
                // also include any rows that have spec_signature_hash implicitly and so_luong column? (rare)
                if (Schema::hasColumn('giu_phong', 'so_luong')) {
                    // include any rows we didn't count via meta when spec_signature_hash not a column
                    // (we keep it conservative and do not sum unconditional so_luong here)
                }
            }
        }

        // total rooms of the type
        $totalRoomsOfType = 0;
        if (Schema::hasTable('loai_phong') && Schema::hasColumn('loai_phong', 'so_luong_thuc_te')) {
            $totalRoomsOfType = (int) DB::table('loai_phong')->where('id', $loaiPhongId)->value('so_luong_thuc_te');
        }
        if ($totalRoomsOfType <= 0) {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)
                ->where('trang_thai', 'trong')
                ->count();
        }

        $remainingAcrossType = max(0, $totalRoomsOfType - $aggregateBooked - $aggregateHoldsForSignature);
        $availableForSignature = max(0, min($matchingAvailableCount, $remainingAcrossType));

        return (int) $availableForSignature;
    }


    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $limit = 1, ?string $requiredSignature = null): array
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        if ($requiredSignature === null) {
            $sample = Phong::where('loai_phong_id', $loaiPhongId)->where('trang_thai', 'trong')->first();
            if (!$sample) return [];
            $requiredSignature = $sample->spec_signature_hash ?? $sample->specSignatureHash();
        }

        // 1) specific booked room ids (dat_phong_item with phong_id) overlapping
        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')->filter()->unique()->toArray();
        }

        // 2) specific holds targeting rooms (giu_phong.phong_id) where dat_phong overlaps
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

        // 3) meta-based selected_phong_ids for holds whose dat_phong overlaps
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
                if (!is_array($decoded)) continue;
                if (!empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
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

        $roomsCount = max(1, (int)$request->input('rooms_count', 1));

        $selectedAddonIds = $request->input('addons', []);
        $selectedAddons = collect();
        if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
            $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
        }
        $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
        $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;

        $childrenMaxAllowed = 2 * $roomsCount;
        if ($childrenInput > $childrenMaxAllowed) {
            return back()->withInput()->withErrors(['children' => "Maximum {$childrenMaxAllowed} children allowed for {$roomsCount} room(s)."]);
        }

        $totalRoomCapacity = $roomCapacity * $roomsCount;
        $countedPersons = $computedAdults + $chargeableChildren;
        $totalMaxAllowed = $totalRoomCapacity + (2 * $roomsCount);
        if ($countedPersons > $totalMaxAllowed) {
            return back()->withInput()->withErrors(['error' => "Maximum allowed guests for {$roomsCount} room(s) is {$totalMaxAllowed}. You provided {$countedPersons}."]);
        }

        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);
        $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
        $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
        $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
        $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
        $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);

        $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
        $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;

        $finalPerNightServer = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
        $snapshotTotalServer = $finalPerNightServer * $nights;

        $maThamChieu = 'BK' . Str::upper(Str::random(8));

        $payload = [
            'ma_tham_chieu' => $maThamChieu,
            'nguoi_dung_id' => $user->id,
            'created_by' => $user->id,
            'ngay_nhan_phong' => $from->toDateString(),
            'ngay_tra_phong' => $to->toDateString(),
            'so_khach' => ($adultsInput + $childrenInput),
            'trang_thai' => 'dang_cho',
            'tong_tien' => $snapshotTotalServer,
            'snapshot_total' => $snapshotTotalServer,
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
            ]),
        ];

        try {
            $datPhongId = null;
            DB::transaction(function () use ($phong, $from, $to, $roomsCount, &$datPhongId, $payload, $selectedAddons, $finalPerNightServer, $snapshotTotalServer, $nights) {
                if (Schema::hasTable('loai_phong')) {
                    DB::table('loai_phong')->where('id', $phong->loai_phong_id)->lockForUpdate()->first();
                }

                $requiredSignature = $phong->specSignatureHash();
                $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $requiredSignature);

                if ($roomsCount > $availableNow) {
                    throw new \Exception("Only {$availableNow} room(s) available.");
                }

                $allowedPayload = [];
                foreach ($payload as $k => $v) {
                    if (Schema::hasColumn('dat_phong', $k)) $allowedPayload[$k] = $v;
                }
                $datPhongId = DB::table('dat_phong')->insertGetId($allowedPayload);

                // Dispatch booking created event
                $booking = DatPhong::find($datPhongId);
                if ($booking) {
                    Log::info("Dispatching BookingCreated event", [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->ma_dat_phong
                    ]);
                    event(new BookingCreated($booking));
                }

                if (Schema::hasTable('giu_phong')) {
                    $holdBase = [
                        'dat_phong_id' => $datPhongId,
                        'loai_phong_id' => $phong->loai_phong_id,
                        'so_luong' => $roomsCount,
                        'het_han_luc' => now()->addMinutes(15),
                        'released' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
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

                        $remaining = $roomsCount - $reservedCount;
                        if ($remaining > 0) {
                            $aggRow = $holdBase;
                            $aggRow['so_luong'] = $remaining;
                            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                $aggRow['spec_signature_hash'] = $baseSignature;
                            }
                            $aggRow['meta'] = json_encode(array_merge($meta, ['reserved_count' => $reservedCount]), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($aggRow);
                            Log::debug('Booking: giu_phong inserted aggregate for remaining', ['remaining' => $remaining, 'dat_phong_id' => $datPhongId]);
                        }
                    } else {
                        $aggRow = $holdBase;
                        $aggRow['spec_signature_hash'] = $requestedSpecSignature;
                        $aggRow['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
                        DB::table('giu_phong')->insert($aggRow);
                        Log::debug('Booking: giu_phong inserted (no phong_id column)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                    }
                }
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
}
