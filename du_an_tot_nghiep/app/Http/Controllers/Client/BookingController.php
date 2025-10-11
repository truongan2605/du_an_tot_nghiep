<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Phong;
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
        $phong->load(['loaiPhong', 'tienNghis', 'images', 'bedTypes']);
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
            'children' => 'nullable|integer|min:0|max:2',
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'nullable|integer|min:0|max:12',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:50',
        ]);

        $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes'])->findOrFail($request->input('phong_id'));

        $from = Carbon::parse($request->input('ngay_nhan_phong'))->startOfDay();
        $to = Carbon::parse($request->input('ngay_tra_phong'))->startOfDay();
        $nights = $from->diffInDays($to);
        if ($nights <= 0) {
            return back()->withInput()->withErrors(['ngay_tra_phong' => 'Check-out date must be after check-in date.']);
        }

        $adults = (int)$request->input('adults', 1);
        $children = (int)$request->input('children', 0);
        $childrenAges = $request->input('children_ages', []);

        if ($children > 0) {
            $provided = is_array($childrenAges) ? count($childrenAges) : 0;
            if ($provided !== $children) {
                return back()->withInput()->withErrors(['children_ages' => 'Please provide ages for each child.']);
            }
        }

        $computedAdults = $adults;
        $chargeableChildren = 0;
        foreach ($childrenAges as $age) {
            $age = (int) $age;
            if ($age >= 13) {
                $computedAdults++;
            } elseif ($age >= 7) {
                $chargeableChildren++;
            } else {
                // <7 free
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

        if ($computedAdults > $roomCapacity) {
            return back()->withInput()->withErrors(['adults' => 'Number of adults (including children aged 13+) exceeds room capacity of ' . $roomCapacity . '.']);
        }

        if ($children > 2) {
            return back()->withInput()->withErrors(['children' => 'Maximum 2 children allowed per room.']);
        }

        if (Schema::hasTable('giu_phong')) {
            $holdQuery = DB::table('giu_phong')->where('phong_id', $phong->id)
                ->where('released', false)
                ->where('het_han_luc', '>', now());
            if ($holdQuery->exists()) {
                return back()->withInput()->withErrors(['error' => 'This room is temporarily reserved. Please try another room or try again later.']);
            }
        }

        $overlapExists = false;
        $start = $from->toDateString();
        $end = $to->toDateString();

        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.phong_id', $phong->id)
                ->whereNotIn('dat_phong.trang_thai', ['huy']);
            $q->where(function ($w) use ($start, $end) {
                $w->whereBetween('dat_phong.ngay_nhan_phong', [$start, $end])
                    ->orWhereBetween('dat_phong.ngay_tra_phong', [$start, $end])
                    ->orWhere(function ($ww) use ($start, $end) {
                        $ww->where('dat_phong.ngay_nhan_phong', '<', $start)
                            ->where('dat_phong.ngay_tra_phong', '>', $end);
                    });
            });
            $overlapExists = $q->exists();
        } elseif (Schema::hasTable('dat_phong') && Schema::hasColumn('dat_phong', 'phong_id')) {
            $q = DB::table('dat_phong')->where('phong_id', $phong->id)->whereNotIn('trang_thai', ['huy']);
            $q->where(function ($w) use ($start, $end) {
                $w->whereBetween('ngay_nhan_phong', [$start, $end])
                    ->orWhereBetween('ngay_tra_phong', [$start, $end])
                    ->orWhere(function ($ww) use ($start, $end) {
                        $ww->where('ngay_nhan_phong', '<', $start)
                            ->where('ngay_tra_phong', '>', $end);
                    });
            });
            $overlapExists = $q->exists();
        }

        if ($overlapExists) {
            return back()->withInput()->withErrors(['error' => 'This room is already booked for the selected dates. Please choose different dates or another room.']);
        }

        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);

        $adultsChargePerNight = $computedAdults * self::ADULT_PRICE;
        $childrenChargePerNight = $chargeableChildren * self::CHILD_PRICE;

        $finalPerNight = $basePerNight + $adultsChargePerNight + $childrenChargePerNight;
        $snapshotTotal = $finalPerNight * $nights;

        DB::beginTransaction();
        try {
            $datPhongId = DB::table('dat_phong')->insertGetId([
                'nguoi_dung_id' => $user->id,
                'ngay_nhan_phong' => $from->toDateString(),
                'ngay_tra_phong' => $to->toDateString(),
                'so_khach' => $adults + $children,
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
                    'room_base_per_night' => $basePerNight,
                    'adults_charge_per_night' => $adultsChargePerNight,
                    'children_charge_per_night' => $childrenChargePerNight,
                    'final_per_night' => $finalPerNight,
                    'nights' => $nights,
                ]),
            ]);

            if (Schema::hasTable('dat_phong_item')) {
                DB::table('dat_phong_item')->insert([
                    'dat_phong_id' => $datPhongId,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'so_luong' => 1,
                    'gia_tren_dem' => $basePerNight,
                    'tong_item' => $basePerNight * $nights,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (Schema::hasTable('dat_phong_items')) {
                DB::table('dat_phong_items')->insert([
                    'dat_phong_id' => $datPhongId,
                    'phong_id' => $phong->id,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'so_luong' => 1,
                    'gia_tren_dem' => $basePerNight,
                    'tong_item' => $basePerNight * $nights,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // If neither item table exists, still proceed and can log here 
            }

            if (Schema::hasTable('giu_phong')) {
                DB::table('giu_phong')->insert([
                    'dat_phong_id' => $datPhongId,
                    'phong_id' => $phong->id,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'het_han_luc' => now()->addMinutes(15),
                    'released' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
