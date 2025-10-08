<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

    public function create(Phong $phong)
    {
        $phong->load(['loaiPhong','tienNghis','images','bedTypes']);
        $user = Auth::user();

        return view('account.booking.create', compact('phong', 'user'));
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
            'children' => 'nullable|integer|min:0|max:2', // enforce max 2 children
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'nullable|integer|min:0|max:120',
            'extra_beds' => 'nullable|array',
            'extra_beds.*' => 'nullable|integer|min:0',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:50',
        ]);

        $phong = Phong::with(['loaiPhong','tienNghis','bedTypes'])->findOrFail($request->input('phong_id'));

        $from = Carbon::parse($request->input('ngay_nhan_phong'))->startOfDay();
        $to = Carbon::parse($request->input('ngay_tra_phong'))->startOfDay();
        $nights = $from->diffInDays($to);
        if ($nights <= 0) {
            return back()->withInput()->withErrors(['ngay_tra_phong' => 'Check-out date must be after check-in date.']);
        }

        $adults = (int)$request->input('adults', 1);
        $children = (int)$request->input('children', 0);
        $childrenAges = $request->input('children_ages', []);

        // convert children>=13 => adult for charging + capacity calculation
        $computedAdults = $adults;
        $chargeableChildren = 0; // counts children between 7 and 12 (inclusive)
        foreach ($childrenAges as $age) {
            $age = (int) $age;
            if ($age >= 13) {
                $computedAdults++;
            } elseif ($age >= 7) {
                $chargeableChildren++;
            } else {
                // < 7 free; doesn't affect charge nor capacity
            }
        }

        // === compute room capacity from bedTypes pivot ===
        $roomCapacity = 0;
        foreach ($phong->bedTypes as $bt) {
            $qty = (int) ($bt->pivot->quantity ?? 0);
            $cap = (int) ($bt->capacity ?? 1);
            $roomCapacity += $qty * $cap;
        }

        // Enforce adult capacity: computedAdults (adults + children>=13) must be <= roomCapacity
        if ($computedAdults > $roomCapacity) {
            return back()->withInput()->withErrors(['adults' => 'Number of adults (including children aged 13+) exceeds room capacity of '.$roomCapacity.'.']);
        }

        // Children limit already validated via request rule max:2, but extra safeguard:
        if ($children > 2) {
            return back()->withInput()->withErrors(['children' => 'Maximum 2 children allowed per room.']);
        }

        // Validate selected extra beds (if you keep extra bed selection on form)
        $selectedBeds = $request->input('extra_beds', []);
        $bedTypesMap = [];
        foreach ($phong->bedTypes as $bt) {
            $bedTypesMap[$bt->id] = [
                'model' => $bt,
                'available' => (int)($bt->pivot->quantity ?? 0),
                'capacity' => (int)($bt->capacity ?? 1),
                'price' => (float)($bt->price ?? 0),
            ];
        }

        $selectedTotalExtraCapacity = 0;
        $selectedBedPricePerNight = 0.0;
        $selectedBedsSnapshot = [];

        foreach ($selectedBeds as $bedTypeId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) continue;
            if (!isset($bedTypesMap[$bedTypeId])) {
                return back()->withInput()->withErrors(['extra_beds' => 'Invalid bed selection.']);
            }
            // IMPORTANT: pivot.quantity currently treated as "number of that bed in the room".
            // If you want separate "extra-available" pool, you will need a separate table/field.
            if ($qty > $bedTypesMap[$bedTypeId]['available']) {
                return back()->withInput()->withErrors(['extra_beds' => 'Selected bed quantity exceeds available count for bed type: '.$bedTypesMap[$bedTypeId]['model']->name]);
            }

            $cap = $bedTypesMap[$bedTypeId]['capacity'];
            $price = $bedTypesMap[$bedTypeId]['price'];

            $selectedTotalExtraCapacity += $qty * $cap;
            $selectedBedPricePerNight += $qty * $price;

            $selectedBedsSnapshot[] = [
                'bed_type_id' => $bedTypeId,
                'name' => $bedTypesMap[$bedTypeId]['model']->name,
                'qty' => $qty,
                'capacity' => $cap,
                'price' => $price,
            ];
        }

        // Effective capacity (base beds + selected extra capacity)
        $effectiveCapacity = $roomCapacity + $selectedTotalExtraCapacity;

        // we DO NOT count children toward capacity, only adults (including children>=13)
        if ($computedAdults > $effectiveCapacity) {
            return back()->withInput()->withErrors(['error' => 'Adults (including children 13+) exceed effective room capacity (including extra beds).']);
        }

        // Basic availability checks (holds/assigned bookings)
        $existingHold = DB::table('giu_phong')
            ->where('phong_id', $phong->id)
            ->where('released', false)
            ->where('het_han_luc', '>', now())
            ->exists();
        if ($existingHold) {
            return back()->withInput()->withErrors(['error' => 'This room is temporarily held by another customer. Please try again later.']);
        }

        // optional: check overlapping bookings if phong_da_dat exists (adjust column names if needed)
        $alreadyBooked = false;
        try {
            $alreadyBooked = DB::table('phong_da_dat')
                ->where('phong_id', $phong->id)
                ->where(function ($q) use ($from, $to) {
                    $q->where(function ($qq) use ($from, $to) {
                        $qq->where('from_date', '<', $to)->where('to_date', '>', $from);
                    });
                })
                ->exists();
        } catch (\Throwable $e) {
            $alreadyBooked = false;
        }
        if ($alreadyBooked) {
            return back()->withInput()->withErrors(['error' => 'This room is already booked in the selected period.']);
        }

        // Pricing
        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);

        // adults for charge: computedAdults (adults + children >=13)
        $adultsChargePerNight = $computedAdults * self::ADULT_PRICE;
        $childrenChargePerNight = $chargeableChildren * self::CHILD_PRICE;
        $bedsChargePerNight = $selectedBedPricePerNight;

        $finalPerNight = $basePerNight + $bedsChargePerNight + $adultsChargePerNight + $childrenChargePerNight;
        $snapshotTotal = $finalPerNight * $nights;

        DB::beginTransaction();
        try {
            $datPhongId = DB::table('dat_phong')->insertGetId([
                'nguoi_dung_id' => $user->id,
                'ngay_nhan_phong' => $from->toDateString(),
                'ngay_tra_phong' => $to->toDateString(),
                'so_khach' => $adults + $children, // keep total guests for record
                'trang_thai' => 'dang_cho',
                'snapshot_total' => $snapshotTotal,
                'created_at' => now(),
                'updated_at' => now(),
                'contact_name' => $request->input('name'),
                'contact_address' => $request->input('address'),
                'contact_phone' => $request->input('phone', $user->so_dien_thoai ?? null),
                'snapshot_meta' => json_encode([
                    'adults_input' => $adults,
                    'children_input' => $children,
                    'children_ages' => $childrenAges,
                    'computed_adults' => $computedAdults,
                    'chargeable_children' => $chargeableChildren,
                    'room_capacity' => $roomCapacity,
                    'selected_extra_beds' => $selectedBedsSnapshot,
                    'room_base_per_night' => $basePerNight,
                    'beds_charge_per_night' => $bedsChargePerNight,
                    'adults_charge_per_night' => $adultsChargePerNight,
                    'children_charge_per_night' => $childrenChargePerNight,
                    'final_per_night' => $finalPerNight,
                    'nights' => $nights,
                ]),
            ]);

            DB::table('dat_phong_item')->insert([
                'dat_phong_id' => $datPhongId,
                'loai_phong_id' => $phong->loai_phong_id,
                'so_luong' => 1,
                'gia_tren_dem' => $basePerNight,
                'tong_item' => $basePerNight * $nights,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('giu_phong')->insert([
                'dat_phong_id' => $datPhongId,
                'phong_id' => $phong->id,
                'loai_phong_id' => $phong->loai_phong_id,
                'het_han_luc' => now()->addMinutes(15),
                'released' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('account.booking.create', $phong->id)
                ->with('success', 'Room held for 15 minutes. Please proceed to payment to confirm the booking.')
                ->with('dat_phong_id', $datPhongId);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Could not create booking: ' . $e->getMessage()]);
        }
    }
}
