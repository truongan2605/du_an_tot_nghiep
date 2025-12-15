<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Phong;
use App\Models\DanhGia; // (đang dùng ở show() theo logic cũ)
use App\Models\LoaiPhong;
use App\Models\TienNghi;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\DatPhong;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    // Tăng giá 10% cho 3 ngày cuối tuần (T6, T7, CN)
    public const WEEKEND_MULTIPLIER = 1.10;

    public function index(Request $request)
    {
        $perPage = 9;

        // ================== LOGIC SỐ KHÁCH / SỐ PHÒNG ==================
        $adults     = max(1, (int) $request->input('adults', 1));   // Người lớn (13+)
        $children   = max(0, (int) $request->input('children', 0)); // Trẻ em (<13) – không tính sức chứa
        $roomsCount = max(1, (int) $request->input('rooms_count', 1));

        // Rule: mỗi phòng tối đa 2 trẻ em -> chỉ giới hạn, KHÔNG đưa vào sức chứa
        $maxChildrenAllowed = $roomsCount * 2;
        if ($children > $maxChildrenAllowed) {
            $children = $maxChildrenAllowed;
            // GHI NGƯỢC LẠI VÀO request để giao diện hiển thị đúng
            $request->merge(['children' => $children]);
        }

        // Sức chứa chỉ tính theo người lớn
        $minCapacityPerRoom = (int) ceil($adults / $roomsCount);

        // ==== Phát hiện khoảng ngày & có dính cuối tuần hay không ====
        $checkIn = null;
        $checkOut = null;
        $hasWeekend = false;

        if ($request->filled('date_range')) {
            $dates = explode(' to ', $request->date_range);
            if (count($dates) === 2) {
                try {
                    $checkIn = Carbon::parse(trim($dates[0]))->startOfDay();
                    $checkOut = Carbon::parse(trim($dates[1]))->startOfDay();
                } catch (\Throwable $e) {
                    $checkIn = $checkOut = null;
                }
            }
        }

        if ($checkIn && $checkOut && $checkIn->lt($checkOut)) {
            $cursor = $checkIn->copy();
            while ($cursor->lt($checkOut)) {
                // ISO: 5=Fri, 6=Sat, 7=Sun
                if (in_array($cursor->dayOfWeekIso, [5, 6, 7], true)) {
                    $hasWeekend = true;
                    break;
                }
                $cursor->addDay();
            }
        }

        // Nếu có dính cuối tuần thì giá thực tế tăng 10%
        $weekendMultiplier = $hasWeekend ? self::WEEKEND_MULTIPLIER : 1.0;

        // ==== Query gốc ====
        $query = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis'])
            ->orderByDesc('created_at')
            // ====== TÍNH ĐÁNH GIÁ THỰC TẾ từ danh_gia_space ======
            ->withAvg([
                'danhGiaspace as avg_rating' => function ($q) {
                    $q->whereNotNull('rating')
                        ->whereNull('parent_id') // chỉ review gốc, không tính reply
                        ->where('status', 1);    // chỉ tính review active
                }
            ], 'rating')
            ->withCount([
                'danhGiaspace as rating_count' => function ($q) {
                    $q->whereNotNull('rating')
                        ->whereNull('parent_id')
                        ->where('status', 1);
                }
            ]);

        // =============== Lọc theo loại phòng ===============
        if ($request->filled('loai_phong_id')) {
            $query->where('loai_phong_id', $request->loai_phong_id);
        }

        // =============== Lọc theo đánh giá sao (THỰC TẾ) ===============
        // UI: radio 5..1 -> hiểu là ">= X sao"
        if ($request->filled('diem')) {
            $diem = (int) $request->diem;
            $query->havingRaw('COALESCE(avg_rating, 0) >= ?', [$diem]);
        }

        // =============== Lọc theo khoảng giá preset (1–4) dựa trên giá NGÀY THƯỜNG ===============
        if ($request->filled('gia_khoang')) {
            switch ($request->gia_khoang) {
                case '1':
                    $query->where('gia_mac_dinh', '<', 500000);
                    break;
                case '2':
                    $query->whereBetween('gia_mac_dinh', [500000, 1000000]);
                    break;
                case '3':
                    $query->whereBetween('gia_mac_dinh', [1000000, 1500000]);
                    break;
                case '4':
                    $query->where('gia_mac_dinh', '>', 1500000);
                    break;
            }
        }

        // =============== Lọc theo giá slider ===============
        // Thanh giá thể hiện "giá khách phải trả" => nếu có weekend thì đó là base * 1.1
        if ($request->filled('gia_min') && $request->filled('gia_max')) {
            $filterMin = (float) $request->gia_min;
            $filterMax = (float) $request->gia_max;

            // Quy đổi ngược về giá ngày thường để whereBetween trong DB
            $mult    = $weekendMultiplier > 0 ? $weekendMultiplier : 1.0;
            $minBase = floor($filterMin / $mult);
            $maxBase = ceil($filterMax / $mult);

            $query->whereBetween('gia_cuoi_cung', [$minBase, $maxBase]);
        }

        // =============== Lọc theo tiện nghi ===============
        if ($request->filled('tien_nghi')) {
            $tienNghiIds = (array) $request->tien_nghi;
            $query->whereHas('tienNghis', function ($q) use ($tienNghiIds) {
                $q->whereIn('tien_nghi.id', $tienNghiIds);
            });
        }

        // Sau khi áp tất cả filter -> lấy danh sách phòng
        $allRooms = $query->get();

        // Nhóm theo loại phòng
        $groupedByType = $allRooms->groupBy('loai_phong_id');

        // Tổng số phòng / loại
        $totalRoomsByType = $groupedByType->map(function ($group) {
            return $group->count();
        });

        // ===== Tính số phòng TRỐNG theo loại phòng trong khoảng ngày đã chọn (nếu có) =====
        $availableByType = [];

        if ($checkIn && $checkOut) {
            $from = $checkIn->toDateString();
            $to   = $checkOut->toDateString();

            $busyByType = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->join('phong', 'phong.id', '=', 'dat_phong_item.phong_id')
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->where(function ($q) use ($from, $to) {
                    $q->whereBetween('dat_phong.ngay_nhan_phong', [$from, $to])
                        ->orWhereBetween('dat_phong.ngay_tra_phong', [$from, $to])
                        ->orWhere(function ($q2) use ($from, $to) {
                            $q2->where('dat_phong.ngay_nhan_phong', '<=', $from)
                                ->where('dat_phong.ngay_tra_phong', '>=', $to);
                        });
                })
                ->selectRaw('phong.loai_phong_id, COUNT(DISTINCT phong.id) as so_phong_ban')
                ->groupBy('phong.loai_phong_id')
                ->pluck('so_phong_ban', 'phong.loai_phong_id')
                ->toArray();

            foreach ($totalRoomsByType as $typeId => $totalCount) {
                $busy = $busyByType[$typeId] ?? 0;
                $availableByType[$typeId] = max($totalCount - $busy, 0);
            }
        }

        // Tạo collection loại phòng: 1 phòng đại diện + số lượng / số phòng trống
        $roomTypeCollection = $groupedByType->map(function ($group, $typeId) use ($availableByType) {
            /** @var \App\Models\Phong $room */
            $room = $group->first();
            $room->so_luong_phong_cung_loai = $group->count();
            $room->so_phong_trong = $availableByType[$typeId] ?? null;

            // avg_rating & rating_count đã có sẵn từ query (withAvg/withCount)
            return $room;
        })->values();

        // ====== Lọc theo sức chứa (CHỈ tính người lớn) ======
        $roomTypeCollection = $roomTypeCollection->filter(function ($room) use ($minCapacityPerRoom) {
            // Ưu tiên lấy trên phòng, nếu không có thì lấy trên loại phòng
            $capacity = $room->suc_chua
                ?? $room->so_nguoi
                ?? $room->so_nguoi_toi_da
                ?? ($room->loaiPhong->suc_chua ?? null)
                ?? ($room->loaiPhong->so_nguoi ?? null)
                ?? ($room->loaiPhong->so_nguoi_toi_da ?? null);

            // Nếu không có thông tin sức chứa thì không lọc theo tiêu chí này
            if (is_null($capacity) || (int) $capacity <= 0) {
                return true;
            }

            return (int) $capacity >= $minCapacityPerRoom;
        })->values();

        // ====== Nếu có chọn ngày thì loại luôn các loại phòng không đủ số phòng yêu cầu ======
        if ($checkIn && $checkOut) {
            $roomTypeCollection = $roomTypeCollection->filter(function ($room) use ($roomsCount) {
                if (is_null($room->so_phong_trong)) {
                    return true;
                }
                return $room->so_phong_trong >= $roomsCount;
            })->values();
        }

        // Phân trang theo loại phòng
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $total   = $roomTypeCollection->count();
        $results = $roomTypeCollection->slice(($page - 1) * $perPage, $perPage)->values();

        $phongs = new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Dữ liệu sidebar
        $loaiPhongs = LoaiPhong::all();
        $tienNghis  = TienNghi::where('active', 1)->get();

        // ==== GIÁ MIN/MAX CHO SLIDER ====
        // Giá ngày thường trong DB
        $baseMin = (int) (Phong::min('gia_cuoi_cung') ?? 0);
        $baseMax = (int) (Phong::max('gia_cuoi_cung') ?? 0);

        $giaMin = $baseMin;
        // Slider luôn cho phép tới giá cuối tuần tối đa (max + 10%)
        $giaMax = (int) ceil($baseMax * self::WEEKEND_MULTIPLIER);

        return view('list-room', compact(
            'phongs',
            'loaiPhongs',
            'tienNghis',
            'giaMin',
            'giaMax',
            'checkIn',
            'checkOut',
            'hasWeekend'
        ));
    }

    public function show($id)
    {
        $phong = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis', 'bedTypes'])->findOrFail($id);

        $related = Phong::with('images')
            ->where('loai_phong_id', $phong->loai_phong_id)
            ->where('id', '<>', $phong->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Ratings logic (giữ nguyên)
        $avgRating = 0;
        $reviews = collect();

        if (Schema::hasTable('dat_phong') && Schema::hasColumn('dat_phong', 'phong_id')) {
            $avgRating = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->avg('danh_gia.diem');

            $reviews = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->select('danh_gia.*')
                ->orderByDesc('danh_gia.created_at')
                ->get();
        } elseif (Schema::hasTable('dat_phong_items') && Schema::hasColumn('dat_phong_items', 'phong_id')) {
            $avgRating = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->join('dat_phong_items', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->avg('danh_gia.diem');

            $reviews = DanhGia::join('dat_phong', 'danh_gia.dat_phong_id', '=', 'dat_phong.id')
                ->join('dat_phong_items', 'dat_phong_items.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_items.phong_id', $phong->id)
                ->where('danh_gia.trang_thai_kiem_duyet', 'da_dang')
                ->select('danh_gia.*')
                ->orderByDesc('danh_gia.created_at')
                ->get();
        } elseif (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'phong_id')) {
            $avgRating = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->avg('diem');

            $reviews = DanhGia::where('phong_id', $phong->id)
                ->where('trang_thai_kiem_duyet', 'da_dang')
                ->orderByDesc('created_at')
                ->get();
        } else {
            $avgRating = 0;
            $reviews = collect();
        }

        $avgRating = $avgRating ? round(floatval($avgRating), 1) : 0.0;

        $bedSummary = collect();
        $totalBeds = 0;

        if ($phong->relationLoaded('bedTypes') && $phong->bedTypes->count()) {
            foreach ($phong->bedTypes as $bt) {
                $qty = (int)($bt->pivot->quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $price = $bt->pivot->price !== null
                    ? (float)$bt->pivot->price
                    : (float)($bt->price ?? 0);
                $bedSummary->push([
                    'id'       => $bt->id,
                    'name'     => $bt->name ?? ($bt->title ?? 'Bed'),
                    'quantity' => $qty,
                    'price'    => $price,
                    'capacity' => $bt->capacity ?? null,
                    'icon'     => $bt->icon ?? null,
                ]);
                $totalBeds += $qty;
            }
        }

        if ($totalBeds <= 0) {
            $totalBeds = (int)($phong->so_giuong ?? $phong->loaiPhong->so_giuong ?? 0);
        }

        $isWished = false;
        if (Auth::check()) {
            $isWished = Wishlist::where('user_id', Auth::id())
                ->where('phong_id', $phong->id)
                ->exists();
        }

        return view('detail-room', compact(
            'phong',
            'related',
            'avgRating',
            'reviews',
            'bedSummary',
            'totalBeds',
            'isWished'
        ));
    }

    /**
     * Đếm số đêm rơi vào cuối tuần (Thứ 6, 7, CN) trong khoảng [from, to)
     */
    private function countWeekendNights(Carbon $fromDate, Carbon $toDate): int
    {
        $cursor = $fromDate->copy()->startOfDay();
        $end = $toDate->copy()->startOfDay();
        $count = 0;

        while ($cursor < $end) {
            // ISO day: 5 = Friday, 6 = Saturday, 7 = Sunday
            if (in_array($cursor->dayOfWeekIso, [5, 6, 7], true)) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    /**
     * Display the compare page
     */
    public function compare()
    {
        return view('client.compare');
    }

    /**
     * Get compare data via API
     */
    public function getCompareData(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));
        $ids = array_filter($ids, 'is_numeric');

        if (empty($ids)) {
            return response()->json([], 200);
        }

        $rooms = Phong::with(['loaiPhong', 'images', 'tienNghis', 'bedTypes'])
            ->whereIn('id', $ids)
            ->get();

        // Transform bed types data
        $rooms = $rooms->map(function ($room) {
            if ($room->relationLoaded('bedTypes') && $room->bedTypes->count()) {
                $room->bed_types = $room->bedTypes->map(function ($bt) {
                    return [
                        'id' => $bt->id,
                        'name' => $bt->name ?? ($bt->title ?? 'Bed'),
                        'quantity' => (int) ($bt->pivot->quantity ?? 0),
                        'capacity' => $bt->capacity ?? null,
                    ];
                })->toArray();
            } else {
                $room->bed_types = [];
            }
            return $room;
        });

        return response()->json($rooms);
    }

    /**
     * Get room type quick view data for room change modal
     * GET /api/room-types/{id}/quick-view
     */
    public function getRoomTypeQuickView($id)
    {
        $loaiPhong = LoaiPhong::with(['tienNghis'])->findOrFail($id);
        
        // Get a sample room of this type to show additional details
        $sampleRoom = Phong::where('loai_phong_id', $id)
            ->with(['images', 'bedTypes'])
            ->first();
        
        // Prepare bed types data
        $bedTypes = [];
        if ($sampleRoom && $sampleRoom->relationLoaded('bedTypes') && $sampleRoom->bedTypes->count()) {
            foreach ($sampleRoom->bedTypes as $bt) {
                $qty = (int)($bt->pivot->quantity ?? 0);
                if ($qty > 0) {
                    $bedTypes[] = [
                        'name' => $bt->name ?? ($bt->title ?? 'Bed'),
                        'quantity' => $qty,
                        'capacity' => $bt->capacity ?? null,
                        'icon' => $bt->icon ?? null,
                    ];
                }
            }
        }
        
        // Prepare images from sample room only (LoaiPhong doesn't have images)
        $images = [];
        if ($sampleRoom && $sampleRoom->relationLoaded('images') && $sampleRoom->images->count()) {
            $images = $sampleRoom->images->map(function ($img) {
                return [
                    'url' => $img->image_url ?? asset('storage/' . $img->image_path),
                    'alt' => $img->alt_text ?? 'Room image'
                ];
            })->toArray();
        }
        
        // Prepare amenities
        $amenities = [];
        if ($loaiPhong->relationLoaded('tienNghis')) {
            $amenities = $loaiPhong->tienNghis->map(function ($tn) {
                return [
                    'name' => $tn->ten,
                    'icon' => $tn->icon ?? 'bi-check-circle'
                ];
            })->toArray();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $loaiPhong->id,
                'name' => $loaiPhong->ten,
                'slug' => $loaiPhong->slug,
                'capacity' => $loaiPhong->suc_chua ?? 2,
                'area' => $loaiPhong->dien_tich ?? null,
                'description' => $loaiPhong->mo_ta ?? '',
                'base_price' => $loaiPhong->gia_mac_dinh ?? 0,
                'images' => $images,
                'amenities' => $amenities,
                'bed_types' => $bedTypes,
            ]
        ]);
    }
}
