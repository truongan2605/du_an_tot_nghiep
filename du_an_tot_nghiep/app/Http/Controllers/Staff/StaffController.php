<?php

namespace App\Http\Controllers\Staff;


use App\Models\Phong;
use App\Models\HoaDon;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\GiuPhong;
use App\Models\LoaiPhong;
use App\Models\HoaDonItem;
use App\Models\PhongDaDat;
use App\Models\DatPhongItem;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\VatDungIncident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PhongVatDungInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\PhongVatDungConsumption;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Storage;
use App\Services\PaymentNotificationService;

class StaffController extends Controller
{


    public function index(Request $request)
    {
        $today = now();
        $weekRange = [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()];
        $month = $today->month;
        $year = $today->year;

        $activeStatus = ['da_xac_nhan', 'dang_su_dung'];
        $activeQuery = DatPhong::whereIn('trang_thai', $activeStatus)
            ->where('trang_thai', '!=', 'da_huy');

        $pendingBookings = DatPhong::where('trang_thai', 'dang_cho')->count();
        $totalBookings = DatPhong::count();

        // === HYBRID REVENUE MODEL ===
        // Revenue = GiaoDich (paid) + HoaDon (unpaid charges)
        
        // Today
        $todayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereDate('created_at', $today)
            ->sum('so_tien');
        $todayInvoiced = HoaDon::whereDate('created_at', $today)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        // Doanh thu hôm nay = Chỉ tính từ hóa đơn không bị hủy
        $todayRevenue = $todayInvoiced;
        
        $todayRefund = RefundRequest::where('status', 'completed')
            ->whereDate('processed_at', $today)
            ->sum('amount');
        $todayNetRevenue = $todayRevenue - $todayRefund;

        // Weekly
        $weeklyPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereBetween(DB::raw('DATE(created_at)'), $weekRange)
            ->sum('so_tien');
        $weeklyInvoiced = HoaDon::whereBetween(DB::raw('DATE(created_at)'), $weekRange)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        // Doanh thu tuần này = Chỉ tính từ hóa đơn không bị hủy
        $weeklyRevenue = $weeklyInvoiced;
        
        $weeklyRefund = RefundRequest::where('status', 'completed')
            ->whereBetween(DB::raw('DATE(processed_at)'), $weekRange)
            ->sum('amount');
        $weeklyNetRevenue = $weeklyRevenue - $weeklyRefund;

        // Monthly
        $monthlyPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('so_tien');
        $monthlyInvoiced = HoaDon::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        // Doanh thu tháng này = Chỉ tính từ hóa đơn không bị hủy
        $monthlyRevenue = $monthlyInvoiced;
        
        $monthlyRefund = RefundRequest::where('status', 'completed')
            ->whereYear('processed_at', $year)
            ->whereMonth('processed_at', $month)
            ->sum('amount');
        $monthlyNetRevenue = $monthlyRevenue - $monthlyRefund;

        // Total
        $totalPaid = GiaoDich::where('trang_thai', 'thanh_cong')->sum('so_tien');
        $totalInvoiced = HoaDon::whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        // Tổng cộng doanh thu = Chỉ tính từ hóa đơn không bị hủy
        $totalRevenue = $totalInvoiced;
        
        $totalRefund = RefundRequest::where('status', 'completed')->sum('amount');
        $totalNetRevenue = $totalRevenue - $totalRefund;

        $todayDeposit = GiaoDich::where('trang_thai', 'thanh_cong')
            ->where('nha_cung_cap', 'like', '%coc%')
            ->whereDate('created_at', $today)
            ->sum('so_tien');

        $weeklyDeposit = GiaoDich::where('trang_thai', 'thanh_cong')
            ->where('nha_cung_cap', 'like', '%coc%')
            ->whereBetween(DB::raw('DATE(created_at)'), $weekRange)
            ->sum('so_tien');

        $monthlyDeposit = GiaoDich::where('trang_thai', 'thanh_cong')
            ->where('nha_cung_cap', 'like', '%coc%')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('so_tien');

        $totalDeposit = GiaoDich::where('trang_thai', 'thanh_cong')
            ->where('nha_cung_cap', 'like', '%coc%')
            ->sum('so_tien');
        
        // ========== CÁC CHỈ SỐ KPI ==========
        $availableRooms = Phong::where('trang_thai', 'trong')->count();
        $soPhongDangCoKhach = Phong::where('trang_thai', 'dang_o')->count();
        $soPhongChoDon = Phong::where('don_dep', true)->count();
        $soPhongBaoTri = Phong::where('trang_thai', 'bao_tri')->count();
        $soPhongChoDonBaoTri = $soPhongChoDon + $soPhongBaoTri;
        
        $todayDate = $today->toDateString();
        $soDatPhongHomNay = DatPhong::where(function($query) use ($todayDate) {
                $query->whereDate('ngay_nhan_phong', $todayDate)
                      ->orWhereDate('created_at', $todayDate);
            })
            ->where('trang_thai', '!=', 'da_huy')
            ->count();
        
        // === Room Analytics Overview (Current Month) ===
        // IMPORTANT: Use HoaDonItems instead of dat_phong_item because dat_phong_item gets deleted after checkout!
        $currentMonth = $today->month;
        $currentYear = $today->year;
        
        // Get all room-related revenue from HoaDonItems (includes both checked-out and active bookings)
        $roomTypeStats = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->join('dat_phong as dp', 'hd.dat_phong_id', '=', 'dp.id')
            ->leftJoin('loai_phong as lp', 'hdi.loai_phong_id', '=', 'lp.id')
            ->whereYear('hd.created_at', $currentYear)
            ->whereMonth('hd.created_at', $currentMonth)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.loai_phong_id')
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('COUNT(DISTINCT CASE WHEN hdi.type = "room_booking" THEN hd.dat_phong_id END) as total_bookings'),
                DB::raw('SUM(CASE WHEN hdi.type = "room_booking" THEN hdi.amount ELSE 0 END) as booking_revenue'),
                DB::raw('SUM(CASE WHEN hdi.type != "room_booking" THEN hdi.amount ELSE 0 END) as additional_revenue'),
                DB::raw('SUM(hdi.amount) as total_revenue')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->get();
        
        // Get room counts for occupancy
        $roomCounts = DB::table('phong')
            ->select('loai_phong_id', DB::raw('COUNT(*) as total_rooms'))
            ->groupBy('loai_phong_id')
            ->pluck('total_rooms', 'loai_phong_id');
        
        $daysInMonth = $today->daysInMonth;
        
        $roomTypeStats = $roomTypeStats->map(function($stat) use ($roomCounts, $daysInMonth) {
            $totalRooms = $roomCounts[$stat->id] ?? 0;
            $maxPossibleBookings = $totalRooms * $daysInMonth;
            $occupancyRate = $maxPossibleBookings > 0 
                ? round(($stat->total_bookings / $maxPossibleBookings) * 100, 1)
                : 0;
            
            return (object)[
                'id' => $stat->id,
                'ten' => $stat->ten,
                'total_rooms' => $totalRooms,
                'total_bookings' => $stat->total_bookings,
                'booking_revenue' => $stat->booking_revenue ?? 0,
                'additional_revenue' => $stat->additional_revenue ?? 0,
                'total_revenue' => $stat->total_revenue ?? 0,
                'occupancy_rate' => $occupancyRate
            ];
        });
        
        // Chart data for mini visualization
        $analyticsChartLabels = $roomTypeStats->pluck('ten')->toArray();
        $analyticsChartData = $roomTypeStats->pluck('total_bookings')->toArray();

        // === Dữ liệu cho biểu đồ doanh thu ===
        // 1. Doanh thu 7 ngày gần nhất
        $chartLabels = [];
        $revenueData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $chartLabels[] = $date->format('d/m');
            $dailyPaid = GiaoDich::where('trang_thai', 'thanh_cong')
                ->whereDate('created_at', $date->toDateString())
                ->sum('so_tien');
            $dailyInvoiced = HoaDon::whereDate('created_at', $date->toDateString())
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
            $revenueData[] = (int) ($dailyPaid + $dailyInvoiced);
        }
        
        // 2. Doanh thu theo ngày trong tuần (7 ngày của tuần này)
        $weekChartLabels = [];
        $weekRevenueData = [];
        $startOfWeek = $today->copy()->startOfWeek();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weekChartLabels[] = $date->format('d/m');
            $dayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
                ->whereDate('created_at', $date->toDateString())
                ->sum('so_tien');
            $dayInvoiced = HoaDon::whereDate('created_at', $date->toDateString())
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
            $weekRevenueData[] = (int) ($dayPaid + $dayInvoiced);
        }
        
        // 3. Doanh thu theo ngày trong tháng
        $monthChartLabels = [];
        $monthRevenueData = [];
        $daysInMonth = $today->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $monthChartLabels[] = $day . '/' . $month;
            $dayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
                ->whereDate('created_at', $date->toDateString())
                ->sum('so_tien');
            $dayInvoiced = HoaDon::whereDate('created_at', $date->toDateString())
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
            $monthRevenueData[] = (int) ($dayPaid + $dayInvoiced);
        }
        
        // 4. Doanh thu theo từng loại phòng (hôm nay, tuần này, tháng này)
        // Hôm nay
        $roomTypeRevenueToday = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->leftJoin('loai_phong as lp', 'hdi.loai_phong_id', '=', 'lp.id')
            ->whereDate('hd.created_at', $todayDate)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.loai_phong_id')
            ->where('hdi.type', 'room_booking')
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();
        
        // Tuần này
        $roomTypeRevenueWeek = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->leftJoin('loai_phong as lp', 'hdi.loai_phong_id', '=', 'lp.id')
            ->whereBetween(DB::raw('DATE(hd.created_at)'), $weekRange)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.loai_phong_id')
            ->where('hdi.type', 'room_booking')
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();
        
        // Tháng này
        $roomTypeRevenueMonth = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->leftJoin('loai_phong as lp', 'hdi.loai_phong_id', '=', 'lp.id')
            ->whereYear('hd.created_at', $year)
            ->whereMonth('hd.created_at', $month)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.loai_phong_id')
            ->where('hdi.type', 'room_booking')
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // === Doanh thu theo từng phòng cụ thể ===
        // Hôm nay
        $roomRevenueToday = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->join('phong as p', 'hdi.phong_id', '=', 'p.id')
            ->leftJoin('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id')
            ->whereDate('hd.created_at', $todayDate)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.phong_id')
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Tuần này
        $roomRevenueWeek = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->join('phong as p', 'hdi.phong_id', '=', 'p.id')
            ->leftJoin('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id')
            ->whereBetween(DB::raw('DATE(hd.created_at)'), $weekRange)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.phong_id')
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Tháng này
        $roomRevenueMonth = DB::table('hoa_don_items as hdi')
            ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
            ->join('phong as p', 'hdi.phong_id', '=', 'p.id')
            ->leftJoin('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id')
            ->whereYear('hd.created_at', $year)
            ->whereMonth('hd.created_at', $month)
            ->whereNotIn('hd.trang_thai', ['da_huy'])
            ->whereNotNull('hdi.phong_id')
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        $events = DatPhong::select('id', 'ma_tham_chieu', 'trang_thai', 'ngay_nhan_phong', 'ngay_tra_phong')
            ->whereIn('trang_thai', $activeStatus)
            ->with('user:id,name')
            ->get()
            ->map(fn($b) => [
                'title' => $b->ma_tham_chieu,
                'start' => $b->ngay_nhan_phong,
                'end' => $b->ngay_tra_phong,
                'description' => "Khách: " . ($b->user->name ?? 'Ẩn danh') . " | " . ucfirst($b->trang_thai)
            ]);

        $recentActivities = DatPhong::whereIn('trang_thai', $activeStatus)
            ->orderByDesc('updated_at')->take(20)->get();

        // Cancelled bookings count
        $cancelledBookings = DatPhong::where('trang_thai', 'da_huy')->count();

        // Today's check-ins (bookings with check-in date today)
        $todayCheckins = DatPhong::whereDate('ngay_nhan_phong', $today)
            ->whereIn('trang_thai', ['da_xac_nhan', 'da_gan_phong'])
            ->with(['datPhongItems.phong'])
            ->orderBy('ngay_nhan_phong')
            ->limit(10)
            ->get();

        // Today's check-outs (bookings with check-out date today)
        $todayCheckouts = DatPhong::whereDate('ngay_tra_phong', $today)
            ->where('trang_thai', 'dang_su_dung')
            ->with(['datPhongItems.phong'])
            ->orderBy('ngay_tra_phong')
            ->limit(10)
            ->get();

        // Custom Date Range Filter
        $customRevenue = null;
        $customRefund = null;
        $customNetRevenue = null;
        $customRangeLabel = null;
        $customChartLabels = [];
        $customChartData = [];
        $customPaid = 0;

        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                
                // Doanh thu tùy chỉnh = Chỉ tính từ hóa đơn không bị hủy
                $customInvoiced = HoaDon::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotIn('trang_thai', ['da_huy'])
                    ->sum('tong_thuc_thu');
                
                $customRevenue = $customInvoiced;
                $customPaid = $customInvoiced; // Gán giá trị cho customPaid để tương thích với view
                
                // Use RefundRequest instead of GiaoDich
                $customRefund = RefundRequest::where('status', 'completed')
                    ->whereBetween('processed_at', [$startDate, $endDate])
                    ->sum('amount');
                
                $customNetRevenue = $customRevenue - $customRefund;
                $customRangeLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                
                // Tính doanh thu theo ngày trong khoảng thời gian đã lọc (chỉ tính từ hóa đơn)
                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    $customChartLabels[] = $currentDate->format('d/m');
                    $dayInvoiced = HoaDon::whereDate('created_at', $currentDate->toDateString())
                        ->whereNotIn('trang_thai', ['da_huy'])
                        ->sum('tong_thuc_thu');
                    $customChartData[] = (int) $dayInvoiced;
                    $currentDate->addDay();
                }
            } catch (\Exception $e) {
                Log::error('Error parsing date range', ['error' => $e->getMessage()]);
            }
        }

        return view('staff.index', compact(
            'pendingBookings',
            'todayRevenue',
            'todayPaid',
            'todayInvoiced',
            'todayRefund',
            'todayNetRevenue',
            'weeklyRevenue',
            'weeklyRefund',
            'weeklyNetRevenue',
            'totalBookings',
            'monthlyRevenue',
            'monthlyRefund',
            'monthlyNetRevenue',
            'totalRevenue',
            'totalRefund',
            'totalNetRevenue',
            'availableRooms',
            'soPhongDangCoKhach',
            'soPhongChoDonBaoTri',
            'soDatPhongHomNay',
            'cancelledBookings',
            'todayCheckins',
            'todayCheckouts',
            'events',
            'recentActivities',
            'chartLabels',
            'revenueData',
            'weekChartLabels',
            'weekRevenueData',
            'monthChartLabels',
            'monthRevenueData',
            'roomTypeRevenueToday',
            'roomTypeRevenueWeek',
            'roomTypeRevenueMonth',
            'roomRevenueToday',
            'roomRevenueWeek',
            'roomRevenueMonth',
            'roomTypeStats',
            'analyticsChartLabels',
            'analyticsChartData',
            'customRevenue',
            'customRefund',
            'customNetRevenue',
            'customRangeLabel',
            'customChartLabels',
            'customChartData',
            'customPaid',
        ));
    }


    public function reports()
    {
        $today = now();
        $todayDate = $today->toDateString(); // Đảm bảo so sánh đúng ngày
        $month = $today->month;
        $year = $today->year;
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // ========== I. CÁC CHỈ SỐ KPI ==========
        
        // 1. Số phòng trống
        $soPhongTrong = Phong::where('trang_thai', 'trong')->count();
        
        // 2. Số phòng đang có khách
        $soPhongDangCoKhach = Phong::where('trang_thai', 'dang_o')->count();
        
        // 3. Số phòng chờ dọn / bảo trì
        $soPhongChoDon = Phong::where('don_dep', true)->count();
        $soPhongBaoTri = Phong::where('trang_thai', 'bao_tri')->count();
        $soPhongChoDonBaoTri = $soPhongChoDon + $soPhongBaoTri;
        
        // 4. Số đặt phòng hôm nay (đơn có ngày nhận phòng hôm nay hoặc được tạo hôm nay)
        $soDatPhongHomNay = DatPhong::where(function($query) use ($todayDate) {
                $query->whereDate('ngay_nhan_phong', $todayDate)
                      ->orWhereDate('created_at', $todayDate);
            })
            ->where('trang_thai', '!=', 'da_huy')
            ->count();
        
        // 5. Tổng doanh thu hôm nay
        $todayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereDate('created_at', $today)
            ->sum('so_tien');
        $todayInvoiced = HoaDon::whereDate('created_at', $today)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        $tongDoanhThuHomNay = $todayPaid + $todayInvoiced;
        
        // 6. Doanh thu tháng này
        $monthlyPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('so_tien');
        $monthlyInvoiced = HoaDon::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        $doanhThuThangNay = $monthlyPaid + $monthlyInvoiced;

        // ========== II. DỮ LIỆU CHO BIỂU ĐỒ ==========
        
        // 1. Biểu đồ doanh thu theo ngày trong tháng (Line Chart)
        $revenueByDay = [];
        $revenueLabels = [];
        $daysInMonth = $today->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $dayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
                ->whereDate('created_at', $date)
                ->sum('so_tien');
            $dayInvoiced = HoaDon::whereDate('created_at', $date)
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
            $revenueByDay[] = $dayPaid + $dayInvoiced;
            $revenueLabels[] = $day . '/' . $month;
        }
        
        // 2. Biểu đồ loại phòng được đặt nhiều nhất (Bar Chart)
        $roomTypeBookings = DB::table('dat_phong_item as dpi')
            ->join('dat_phong as dp', 'dpi.dat_phong_id', '=', 'dp.id')
            ->join('loai_phong as lp', 'dpi.loai_phong_id', '=', 'lp.id')
            ->whereYear('dp.created_at', $year)
            ->whereMonth('dp.created_at', $month)
            ->where('dp.trang_thai', '!=', 'da_huy')
            ->select(
                'lp.ten as loai_phong',
                DB::raw('COUNT(DISTINCT dp.id) as so_luong_dat')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('so_luong_dat')
            ->limit(10)
            ->get();
        
        $roomTypeLabels = $roomTypeBookings->pluck('loai_phong')->toArray();
        $roomTypeData = $roomTypeBookings->pluck('so_luong_dat')->toArray();
        
        // 3. Biểu đồ trạng thái phòng (Pie Chart)
        $roomStatusStats = Phong::select('trang_thai', DB::raw('COUNT(*) as so_luong'))
            ->groupBy('trang_thai')
            ->get();
        
        $roomStatusLabels = [];
        $roomStatusData = [];
        $roomStatusColors = [
            'trong' => '#28a745',
            'dang_o' => '#007bff',
            'bao_tri' => '#ffc107',
            'da_dat' => '#6c757d',
            'khong_su_dung' => '#dc3545'
        ];
        
        foreach ($roomStatusStats as $stat) {
            $label = match($stat->trang_thai) {
                'trong' => 'Trống',
                'dang_o' => 'Đang ở',
                'bao_tri' => 'Bảo trì',
                'da_dat' => 'Đã đặt',
                'khong_su_dung' => 'Không sử dụng',
                default => $stat->trang_thai
            };
            $roomStatusLabels[] = $label;
            $roomStatusData[] = $stat->so_luong;
        }

        // ========== III. DỮ LIỆU CHO BẢNG ==========
        
        // 1. Danh sách đặt phòng hôm nay (đơn có ngày nhận phòng hôm nay hoặc được tạo hôm nay)
        $datPhongHomNay = DatPhong::with(['nguoiDung', 'datPhongItems.phong', 'datPhongItems.loaiPhong'])
            ->where(function($query) use ($todayDate) {
                $query->whereDate('ngay_nhan_phong', $todayDate)
                      ->orWhereDate('created_at', $todayDate);
            })
            ->where('trang_thai', '!=', 'da_huy')
            ->orderByRaw('CASE WHEN DATE(ngay_nhan_phong) = ? THEN 0 ELSE 1 END', [$todayDate])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // 2. Danh sách phòng theo trạng thái
        $phongTheoTrangThai = Phong::with(['loaiPhong', 'tang'])
            ->orderBy('trang_thai')
            ->orderBy('ma_phong')
            ->get();

        return view('staff.reports', compact(
            // KPI
            'soPhongTrong',
            'soPhongDangCoKhach',
            'soPhongChoDonBaoTri',
            'soDatPhongHomNay',
            'tongDoanhThuHomNay',
            'doanhThuThangNay',
            // Biểu đồ
            'revenueLabels',
            'revenueByDay',
            'roomTypeLabels',
            'roomTypeData',
            'roomStatusLabels',
            'roomStatusData',
            'roomStatusColors',
            // Bảng
            'datPhongHomNay',
            'phongTheoTrangThai'
        ));
    }

    public function roomOverview()
    {
        $rooms = Phong::with(['tang', 'loaiPhong'])
            ->orderBy('tang_id')
            ->orderBy('ma_phong')
            ->get();
        $floors = $rooms->groupBy(fn($room) => $room->tang->so_tang ?? $room->tang->id);
        return view('staff.room-overview', compact('floors'));
    }

    public function checkinForm()
    {
        // Lấy danh sách phòng bị chặn bởi đơn checkout muộn
        $blockedBookings = DatPhong::where('blocks_checkin', true)
            ->where('trang_thai', 'dang_su_dung')
            ->with('datPhongItems')
            ->get();
        
        $blockedPhongIds = collect();
        foreach ($blockedBookings as $blockedBooking) {
            $blockedPhongIds = $blockedPhongIds->merge(
                $blockedBooking->datPhongItems->pluck('phong_id')->filter()
            );
        }
        $blockedPhongIds = $blockedPhongIds->unique()->toArray();

        $bookings = DatPhong::with([
            'nguoiDung',
            'checkedInBy',
            'giaoDichs' => function ($q) {
                $q->where('trang_thai', 'thanh_cong');
            },
            'datPhongItems.phong'
        ])
            ->whereIn('trang_thai', ['da_xac_nhan', 'da_gan_phong'])
            ->orderBy('ngay_nhan_phong', 'asc')
            ->get()
            ->map(function ($booking) use ($blockedPhongIds) {
                $paid      = $booking->giaoDichs->sum('so_tien');
                $remaining = $booking->tong_tien - $paid;

                $hasDonDep = $booking->datPhongItems
                    ->pluck('phong')
                    ->filter()
                    ->pluck('don_dep')
                    ->contains(true);

                // Kiểm tra phòng bị chặn bởi đơn checkout muộn
                $bookingPhongIds = $booking->datPhongItems->pluck('phong_id')->filter()->toArray();
                $hasBlockedRoom = !empty($bookingPhongIds) && !empty(array_intersect($bookingPhongIds, $blockedPhongIds));

                $booking->paid = $paid;
                $booking->remaining = $remaining;

                $booking->can_checkin = ($remaining <= 0) && (!$hasDonDep) && (!$hasBlockedRoom);

                if ($hasDonDep) {
                    $booking->checkin_blocked_due_to = 'room_in_cleaning';
                } elseif ($hasBlockedRoom) {
                    $booking->checkin_blocked_due_to = 'late_checkout';
                } else {
                    $booking->checkin_blocked_due_to = null;
                }

                $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
                $today       = \Carbon\Carbon::today();
                $now = \Carbon\Carbon::now();
                $standardCheckinTime = $checkinDate->copy()->setTime(14, 0, 0);

                // Tính toán thông tin checkin sớm/muộn
                $booking->is_early_checkin_possible = false;
                $booking->is_late_checkin_warning = false;
                $booking->early_checkin_fee_estimate = 0;
                
                if ($checkinDate->isToday()) {
                    // Nếu là ngày checkin
                    if ($now->isBefore($standardCheckinTime)) {
                        // Có thể checkin sớm
                        $booking->is_early_checkin_possible = true;
                        $hoursEarly = $now->diffInHours($standardCheckinTime, false);
                        if ($hoursEarly > 0) {
                            $datPhongItems = $booking->datPhongItems;
                            $dailyTotal = $datPhongItems->reduce(function ($carry, $item) {
                                $qty = $item->so_luong ?? 1;
                                $unit = $item->gia_tren_dem ?? 0;
                                return $carry + ($unit * $qty);
                            }, 0.0);
                            
                            // Kiểm tra hình thức thanh toán (50% hay 100%)
                            if (is_array($booking->snapshot_meta)) {
                                $meta = $booking->snapshot_meta;
                            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                                $meta = json_decode($booking->snapshot_meta, true) ?? [];
                            } else {
                                $meta = [];
                            }
                            $depositPercentage = $meta['deposit_percentage'] ?? 50;
                            
                            // Tính tổng số tiền đã thanh toán
                            $paidAmount = GiaoDich::where('dat_phong_id', $booking->id)
                                ->where('trang_thai', 'thanh_cong')
                                ->sum('so_tien');
                            
                            // Tính tổng tiền booking
                            $totalAmount = $booking->tong_tien ?? 0;
                            
                            // Xác định hình thức thanh toán: >= 95% coi là 100%
                            $isFullPayment = false;
                            if ($totalAmount > 0) {
                                $paymentPercentage = ($paidAmount / $totalAmount) * 100;
                                $isFullPayment = ($paymentPercentage >= 95) || ($depositPercentage == 100);
                            }
                            
                            $perHourRate = $dailyTotal * 0.3 / 24;
                            
                            if ($isFullPayment) {
                                // Thanh toán 100%: tính phí không giới hạn (100%)
                                $booking->early_checkin_fee_estimate = $perHourRate * $hoursEarly;
                            } else {
                                // Đặt cọc 50%: tối đa 50% giá phòng/ngày
                                $booking->early_checkin_fee_estimate = min($perHourRate * $hoursEarly, $dailyTotal * 0.5);
                            }
                            
                            $booking->early_checkin_fee_estimate = (int) round($booking->early_checkin_fee_estimate, 0);
                        }
                    } elseif ($now->isAfter($standardCheckinTime)) {
                        // Checkin muộn trong ngày
                        $booking->is_late_checkin_warning = true;
                    }
                } elseif ($checkinDate->isPast()) {
                    // Quá ngày checkin - sẽ bị hủy nếu checkin
                    $booking->is_late_checkin_warning = true;
                    $booking->will_be_cancelled = true;
                }

                $booking->checkin_status = $checkinDate->isToday()
                    ? 'Hôm nay'
                    : ($checkinDate->isFuture() ? 'Sắp tới' : 'Quá hạn');

                $booking->checkin_date_diff = $checkinDate->isFuture()
                    ? $checkinDate->diffInDays($today) . ' ngày nữa'
                    : ($checkinDate->isPast() ? 'Quá ' . $checkinDate->diffInDays($today) . ' ngày' : '');

                return $booking;
            });

        Log::info('Checkin bookings loaded (ALL)', [
            'count' => $bookings->count(),
            'ids'   => $bookings->pluck('id')->toArray(),
        ]);

        return view('staff.checkin', compact('bookings'));
    }

    public function processCheckin(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:dat_phong,id',
        ]);

        $booking = DatPhong::with(['datPhongItems', 'giaoDichs'])->findOrFail($request->booking_id);
        
        // Kiểm tra đã có CCCD chưa HOẶC đang submit CCCD mới
        if (is_array($booking->snapshot_meta)) {
            $meta = $booking->snapshot_meta;
        } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
            /** @var string $snapshotMeta */
            $snapshotMeta = $booking->snapshot_meta;
            $meta = json_decode($snapshotMeta, true) ?? [];
        } else {
            $meta = [];
        }
        
        // Kiểm tra có CCCD trong database
        $cccdList = $meta['checkin_cccd_list'] ?? [];
        $hasCCCD = !empty($cccdList) && is_array($cccdList) && count($cccdList) > 0;
        
        // Backward compatibility: kiểm tra CCCD cũ
        if (!$hasCCCD) {
            $hasCCCD = !empty($meta['checkin_cccd_front']) 
                || !empty($meta['checkin_cccd_back']) 
                || !empty($meta['checkin_cccd']);
        }
        
        // QUAN TRỌNG: Kiểm tra xem user có ĐANG SUBMIT ảnh CCCD mới không
        // Hỗ trợ cả format cũ (1 người) và format mới (nhiều người)
        $isSubmittingNewCCCD = $request->hasFile('cccd_image_front') || $request->hasFile('cccd_image_back');
        
        // Kiểm tra format mới (nhiều người): cccd_image_front_0, cccd_image_back_0, etc.
        if (!$isSubmittingNewCCCD) {
            $allFiles = $request->allFiles();
            foreach ($allFiles as $key => $file) {
                if (preg_match('/^cccd_image_(front|back)_\d+$/', $key)) {
                    $isSubmittingNewCCCD = true;
                    break;
                }
            }
        }
        
        // Chỉ báo lỗi nếu KHÔNG có CCCD cũ VÀ KHÔNG submit CCCD mới
        if (!$hasCCCD && !$isSubmittingNewCCCD) {
            return redirect()->back()->with('error', "Cần nhập CCCD/CMND trước khi check-in.");
        }

        $phongIds = collect($booking->datPhongItems)->pluck('phong_id')->filter()->toArray();

        // Kiểm tra phòng bị chặn bởi đơn checkout muộn
        if (!empty($phongIds)) {
            // Tìm các đơn có blocks_checkin = true và trang_thai = 'dang_su_dung'
            $blockedBookings = DatPhong::where('blocks_checkin', true)
                ->where('trang_thai', 'dang_su_dung')
                ->with('datPhongItems')
                ->get();
            
            // Lấy danh sách phòng_id từ các đơn bị chặn
            $blockedPhongIds = collect();
            foreach ($blockedBookings as $blockedBooking) {
                $blockedPhongIds = $blockedPhongIds->merge(
                    $blockedBooking->datPhongItems->pluck('phong_id')->filter()
                );
            }
            $blockedPhongIds = $blockedPhongIds->unique()->toArray();
            
            // Kiểm tra xem có phòng nào trùng không
            $conflictingPhongIds = array_intersect($phongIds, $blockedPhongIds);
            
            if (!empty($conflictingPhongIds)) {
                return redirect()->back()->with('error', 'Không thể check-in: Đang có đơn checkout muộn trong các phòng này. Vui lòng chờ đơn checkout hoàn tất.');
            }
        }

        $hasDonDep = false;
        if (!empty($phongIds)) {
            $hasDonDep = Phong::whereIn('id', $phongIds)->where('don_dep', true)->exists();
        }

        if ($hasDonDep) {
            return redirect()->back()->with('error', 'Không thể check-in: Một hoặc nhiều phòng đang dọn dẹp. Vui lòng kiểm tra lại sau.');
        }

        if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
            return redirect()->back()->with('error', 'Booking không thể check-in');
        }

        $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
        $remaining = $booking->tong_tien - $paid;

        if ($remaining > 0) {
            return redirect()->back()->with('error', "Cần thanh toán còn lại " . number_format($remaining) . " VND trước khi check-in.");
        }

        // Kiểm tra checkin sớm/muộn
        $now = \Carbon\Carbon::now();
        $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
        $standardCheckinTime = $checkinDate->copy()->setTime(14, 0, 0); // Giờ checkin chuẩn: 14:00
        
        $isEarlyCheckin = false;
        $isLateCheckin = false;
        $earlyCheckinFee = 0;
        
        // Kiểm tra checkin muộn (sang ngày khác) - HỦY ĐẶT PHÒNG
        if ($now->isAfter($checkinDate->endOfDay())) {
            // Checkin muộn sang ngày khác -> Hủy đặt phòng
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'trang_thai' => 'da_huy',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Hủy đặt phòng do checkin muộn (sang ngày khác)',
                ]);
                
                // Xóa dat_phong_items
                DatPhongItem::where('dat_phong_id', $booking->id)->delete();
                
                // Xóa giu_phong records
                if (Schema::hasTable('giu_phong')) {
                    DB::table('giu_phong')
                        ->where('dat_phong_id', $booking->id)
                        ->delete();
                }
            });
            
            return redirect()->back()->with('error', 'Đặt phòng đã bị hủy do checkin muộn (sang ngày khác). Ngày checkin dự kiến: ' . $checkinDate->format('d/m/Y'));
        }
        
        // Kiểm tra checkin sớm (trước 14:00 của ngày checkin)
        if ($now->isBefore($standardCheckinTime) && $now->isSameDay($checkinDate)) {
            $isEarlyCheckin = true;
            // Tính phí checkin sớm: tính theo giờ trước 14:00
            $hoursEarly = $now->diffInHours($standardCheckinTime, false);
            if ($hoursEarly > 0) {
                // Tính tổng giá phòng theo ngày
                $datPhongItems = $booking->datPhongItems;
                $dailyTotal = $datPhongItems->reduce(function ($carry, $item) {
                    $qty = $item->so_luong ?? 1;
                    $unit = $item->gia_tren_dem ?? 0;
                    return $carry + ($unit * $qty);
                }, 0.0);
                
                // Kiểm tra hình thức thanh toán (50% hay 100%)
                if (is_array($booking->snapshot_meta)) {
                    $meta = $booking->snapshot_meta;
                } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                    $meta = json_decode($booking->snapshot_meta, true) ?? [];
                } else {
                    $meta = [];
                }
                $depositPercentage = $meta['deposit_percentage'] ?? 50;
                
                // Tính tổng số tiền đã thanh toán
                $paidAmount = GiaoDich::where('dat_phong_id', $booking->id)
                    ->where('trang_thai', 'thanh_cong')
                    ->sum('so_tien');
                
                // Tính tổng tiền booking
                $totalAmount = $booking->tong_tien ?? 0;
                
                // Xác định hình thức thanh toán: >= 95% coi là 100%
                $isFullPayment = false;
                if ($totalAmount > 0) {
                    $paymentPercentage = ($paidAmount / $totalAmount) * 100;
                    $isFullPayment = ($paymentPercentage >= 95) || ($depositPercentage == 100);
                }
                
                // Phí checkin sớm: 30% giá phòng/ngày cho mỗi giờ sớm
                $perHourRate = $dailyTotal * 0.3 / 24; // 30% giá ngày chia cho 24 giờ
                
                if ($isFullPayment) {
                    // Thanh toán 100%: tính phí không giới hạn (100%)
                    $earlyCheckinFee = $perHourRate * $hoursEarly;
                } else {
                    // Đặt cọc 50%: tối đa 50% giá phòng/ngày
                    $earlyCheckinFee = min($perHourRate * $hoursEarly, $dailyTotal * 0.5);
                }
                
                $earlyCheckinFee = (int) round($earlyCheckinFee, 0);
            }
        }
        
        // Kiểm tra checkin muộn (sau 14:00 nhưng trong cùng ngày)
        if ($now->isAfter($standardCheckinTime) && $now->isSameDay($checkinDate)) {
            $isLateCheckin = true;
            // Cho phép checkin muộn trong ngày, không tính phí
        }

        DB::transaction(function () use ($booking, $request, $isEarlyCheckin, $isLateCheckin, $earlyCheckinFee) {
            // Xóa ảnh cũ nếu có
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                /** @var string $snapshotMeta */
                $snapshotMeta = $booking->snapshot_meta;
                $meta = json_decode($snapshotMeta, true) ?? [];
            } else {
                $meta = [];
            }

            // Xóa ảnh cũ trong checkin_cccd_list nếu có
            if (!empty($meta['checkin_cccd_list']) && is_array($meta['checkin_cccd_list'])) {
                foreach ($meta['checkin_cccd_list'] as $cccdItem) {
                    if (!empty($cccdItem['front']) && Storage::disk('public')->exists($cccdItem['front'])) {
                        Storage::disk('public')->delete($cccdItem['front']);
                    }
                    if (!empty($cccdItem['back']) && Storage::disk('public')->exists($cccdItem['back'])) {
                        Storage::disk('public')->delete($cccdItem['back']);
                    }
                }
            }
            
            // Xóa ảnh cũ (backward compatibility)
            if (!empty($meta['checkin_cccd']) && Storage::disk('public')->exists($meta['checkin_cccd'])) {
                Storage::disk('public')->delete($meta['checkin_cccd']);
            }
            if (!empty($meta['checkin_cccd_front']) && Storage::disk('public')->exists($meta['checkin_cccd_front'])) {
                Storage::disk('public')->delete($meta['checkin_cccd_front']);
            }
            if (!empty($meta['checkin_cccd_back']) && Storage::disk('public')->exists($meta['checkin_cccd_back'])) {
                Storage::disk('public')->delete($meta['checkin_cccd_back']);
            }

            // Lưu ảnh mới - hỗ trợ cả format cũ (1 người) và format mới (nhiều người)
            $cccdList = [];
            
            // Kiểm tra format mới (nhiều người): cccd_image_front_0, cccd_image_back_0, etc.
            $allFiles = $request->allFiles();
            $hasMultiplePeople = false;
            $maxIndex = -1;
            
            foreach ($allFiles as $key => $file) {
                if (preg_match('/^cccd_image_(front|back)_(\d+)$/', $key, $matches)) {
                    $hasMultiplePeople = true;
                    $index = (int) $matches[2];
                    if ($index > $maxIndex) {
                        $maxIndex = $index;
                    }
                }
            }
            
            if ($hasMultiplePeople && $maxIndex >= 0) {
                // Format mới: lưu nhiều người vào checkin_cccd_list
                for ($i = 0; $i <= $maxIndex; $i++) {
                    if ($request->hasFile("cccd_image_front_{$i}") && $request->hasFile("cccd_image_back_{$i}")) {
                        $frontImagePath = $request->file("cccd_image_front_{$i}")->store('cccd', 'public');
                        $backImagePath = $request->file("cccd_image_back_{$i}")->store('cccd', 'public');
                        
                        $cccdList[] = [
                            'front' => $frontImagePath,
                            'back' => $backImagePath,
                        ];
                    }
                }
                
                $meta['checkin_cccd_list'] = $cccdList;
                $meta['cccd_count'] = count($cccdList);
                
                // Giữ backward compatibility (lưu CCCD đầu tiên)
                if (!empty($cccdList[0])) {
                    $meta['checkin_cccd_front'] = $cccdList[0]['front'];
                    $meta['checkin_cccd_back'] = $cccdList[0]['back'];
                    $meta['checkin_cccd'] = $cccdList[0]['front'];
                }
            } else {
                // Format cũ: 1 người (backward compatibility)
                if ($request->hasFile('cccd_image_front') && $request->hasFile('cccd_image_back')) {
                    $frontImagePath = $request->file('cccd_image_front')->store('cccd', 'public');
                    $backImagePath = $request->file('cccd_image_back')->store('cccd', 'public');
                    
                    $cccdList[] = [
                        'front' => $frontImagePath,
                        'back' => $backImagePath,
                    ];
                    
                    $meta['checkin_cccd_list'] = $cccdList;
                    $meta['cccd_count'] = 1;
                    $meta['checkin_cccd_front'] = $frontImagePath;
                    $meta['checkin_cccd_back'] = $backImagePath;
                    $meta['checkin_cccd'] = $frontImagePath;
                }
            }
            
            // Kiểm tra xem đã thanh toán phụ thu checkin sớm chưa
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                $meta = json_decode($booking->snapshot_meta, true) ?? [];
            } else {
                $meta = [];
            }
            
            $meta['checkin_at'] = now()->toDateTimeString();
            $meta['checkin_by'] = Auth::id();
            $hasPaidEarlyCheckinFee = !empty($meta['early_checkin_fee_paid']) && $meta['early_checkin_fee_paid'] > 0;
            
            // Tạo hóa đơn phụ thu nếu checkin sớm và chưa thanh toán phụ thu
            $hoaDon = null;
            if ($isEarlyCheckin && $earlyCheckinFee > 0 && !$hasPaidEarlyCheckinFee) {
                $hoaDon = HoaDon::where('dat_phong_id', $booking->id)
                    ->where('trang_thai', '!=', 'da_thanh_toan')
                    ->orderByDesc('id')
                    ->first();
                
                if (!$hoaDon) {
                    $hoaDon = HoaDon::create([
                        'dat_phong_id' => $booking->id,
                        'so_hoa_don' => 'HD' . time() . rand(100, 999),
                        'tong_thuc_thu' => 0,
                        'don_vi' => $booking->don_vi_tien ?? 'VND',
                        'trang_thai' => 'tao',
                        'created_by' => Auth::id(),
                    ]);
                }
                
                // Thêm item phụ thu checkin sớm
                HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'early_checkin_fee',
                    'name' => 'Phụ thu checkin sớm',
                    'quantity' => 1,
                    'unit_price' => $earlyCheckinFee,
                    'amount' => $earlyCheckinFee,
                    'note' => 'Phụ thu checkin sớm',
                ]);
                
                // Cập nhật tổng hóa đơn
                $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $earlyCheckinFee;
                $hoaDon->save();
            } elseif ($isEarlyCheckin && $hasPaidEarlyCheckinFee) {
                // Đã thanh toán phụ thu rồi, không tạo hóa đơn nữa
                // Nhưng vẫn cần cập nhật early_checkin_fee_amount để ghi nhận
                $earlyCheckinFee = $meta['early_checkin_fee_paid'];
            }

            $booking->update([
                'trang_thai' => 'dang_su_dung',
                'checked_in_at' => now(),
                'checked_in_by' => Auth::id(),
                'snapshot_meta' => $meta,
                'is_early_checkin' => $isEarlyCheckin,
                'early_checkin_fee_amount' => $isEarlyCheckin ? $earlyCheckinFee : 0,
                'is_late_checkin' => $isLateCheckin,
            ]);

            $phongIds = $booking->datPhongItems->pluck('phong_id')->filter()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_o', 'don_dep' => false, 'updated_at' => now()]);
            }

            // Gửi thông báo check-in
            $notificationService = new PaymentNotificationService();
            $notificationService->sendCheckinNotification($booking);
        });
        
        $successMessage = 'Check-in thành công cho booking ' . $booking->ma_tham_chieu . ' lúc ' . now()->format('H:i d/m/Y');
        if ($isEarlyCheckin && $earlyCheckinFee > 0) {
            $successMessage .= '. Phụ thu checkin sớm: ' . number_format($earlyCheckinFee) . ' VND đã được ghi nhận trong hóa đơn.';
        } elseif ($isLateCheckin) {
            $successMessage .= ' (Checkin muộn trong ngày).';
        }

        return redirect()->route('staff.checkin')
            ->with('success', $successMessage);
    }

    public function saveCCCD(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:dat_phong,id',
            'cccd_count' => 'required|integer|min:1|max:20',
        ], [
            'cccd_count.required' => 'Vui lòng nhập số lượng CCCD cần nhập',
            'cccd_count.integer' => 'Số lượng CCCD phải là số nguyên',
            'cccd_count.min' => 'Số lượng CCCD phải lớn hơn 0',
            'cccd_count.max' => 'Số lượng CCCD không được vượt quá 20',
        ]);
        
        $booking = DatPhong::findOrFail($request->booking_id);
        if (is_array($booking->snapshot_meta)) {
            $meta = $booking->snapshot_meta;
        } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
            /** @var string $snapshotMeta */
            $snapshotMeta = $booking->snapshot_meta;
            $meta = json_decode($snapshotMeta, true) ?? [];
        } else {
            $meta = [];
        }
        
        // Lấy số lượng CCCD từ input
        $cccdCount = (int) $request->input('cccd_count', 1);
        
        // Validation: yêu cầu đủ số lượng CCCD đã nhập
        $validationRules = [];
        $validationMessages = [];
        
        for ($i = 0; $i < $cccdCount; $i++) {
            $validationRules["cccd_image_front_{$i}"] = 'required|image|mimes:jpeg,jpg,png,webp|max:5120';
            $validationRules["cccd_image_back_{$i}"] = 'required|image|mimes:jpeg,jpg,png,webp|max:5120';
            
            $validationMessages["cccd_image_front_{$i}.required"] = "Vui lòng chọn ảnh mặt trước CCCD/CMND của người thứ " . ($i + 1);
            $validationMessages["cccd_image_back_{$i}.required"] = "Vui lòng chọn ảnh mặt sau CCCD/CMND của người thứ " . ($i + 1);
            $validationMessages["cccd_image_front_{$i}.image"] = "File mặt trước của người thứ " . ($i + 1) . " phải là ảnh";
            $validationMessages["cccd_image_back_{$i}.image"] = "File mặt sau của người thứ " . ($i + 1) . " phải là ảnh";
            $validationMessages["cccd_image_front_{$i}.mimes"] = "Ảnh mặt trước của người thứ " . ($i + 1) . " phải có định dạng: jpeg, jpg, png, hoặc webp";
            $validationMessages["cccd_image_back_{$i}.mimes"] = "Ảnh mặt sau của người thứ " . ($i + 1) . " phải có định dạng: jpeg, jpg, png, hoặc webp";
            $validationMessages["cccd_image_front_{$i}.max"] = "Kích thước ảnh mặt trước của người thứ " . ($i + 1) . " không được vượt quá 5MB";
            $validationMessages["cccd_image_back_{$i}.max"] = "Kích thước ảnh mặt sau của người thứ " . ($i + 1) . " không được vượt quá 5MB";
        }
        
        $request->validate($validationRules, $validationMessages);

        // Xóa ảnh cũ nếu có
        if (!empty($meta['checkin_cccd_list']) && is_array($meta['checkin_cccd_list'])) {
            foreach ($meta['checkin_cccd_list'] as $cccdItem) {
                if (!empty($cccdItem['front']) && Storage::disk('public')->exists($cccdItem['front'])) {
                    Storage::disk('public')->delete($cccdItem['front']);
                }
                if (!empty($cccdItem['back']) && Storage::disk('public')->exists($cccdItem['back'])) {
                    Storage::disk('public')->delete($cccdItem['back']);
                }
            }
        }
        
        // Xóa ảnh cũ (backward compatibility)
        if (!empty($meta['checkin_cccd']) && Storage::disk('public')->exists($meta['checkin_cccd'])) {
            Storage::disk('public')->delete($meta['checkin_cccd']);
        }
        if (!empty($meta['checkin_cccd_front']) && Storage::disk('public')->exists($meta['checkin_cccd_front'])) {
            Storage::disk('public')->delete($meta['checkin_cccd_front']);
        }
        if (!empty($meta['checkin_cccd_back']) && Storage::disk('public')->exists($meta['checkin_cccd_back'])) {
            Storage::disk('public')->delete($meta['checkin_cccd_back']);
        }

        // Lưu ảnh mới cho từng người
        $cccdList = [];
        for ($i = 0; $i < $cccdCount; $i++) {
            $frontImagePath = $request->file("cccd_image_front_{$i}")->store('cccd', 'public');
            $backImagePath = $request->file("cccd_image_back_{$i}")->store('cccd', 'public');
            
            $cccdList[] = [
                'front' => $frontImagePath,
                'back' => $backImagePath,
            ];
        }

        // Lưu đường dẫn ảnh vào snapshot_meta
        $meta['checkin_cccd_list'] = $cccdList;
        $meta['cccd_count'] = $cccdCount; // Lưu số lượng CCCD đã nhập
        
        // Giữ backward compatibility (lưu CCCD đầu tiên)
        if (!empty($cccdList[0])) {
            $meta['checkin_cccd_front'] = $cccdList[0]['front'];
            $meta['checkin_cccd_back'] = $cccdList[0]['back'];
            $meta['checkin_cccd'] = $cccdList[0]['front'];
        }
        
        $meta['cccd_saved_at'] = now()->toDateTimeString();
        $meta['cccd_saved_by'] = Auth::id();

        $booking->update([
            'snapshot_meta' => $meta,
        ]);

        return redirect()->route('staff.checkin')
            ->with('success', "Đã lưu ảnh CCCD/CMND cho {$cccdCount} người trong booking " . $booking->ma_tham_chieu);
    }

    public function checkoutForm()
    {
        $bookings = DatPhong::whereIn('trang_thai', ['da_gan_phong', 'dang_o'])
            ->whereDate('ngay_tra_phong', now())
            ->with('user', 'datPhongItems.phong')
            ->get();
        return view('staff.checkout', compact('bookings'));
    }


    public function pendingPayments()
    {
        $pendingPayments = DatPhong::where('can_xac_nhan', true)->get();
        return view('payment.pending_payments', compact('pendingPayments'));
    }

    public function bookings()
    {
        $today = now();
        $todayDate = $today->toDateString();
        
        // Tính toán các chỉ số thống kê
        // 1. Số phòng trống
        $soPhongTrong = Phong::where('trang_thai', 'trong')->count();
        
        // 2. Số phòng đang có khách
        $soPhongDangCoKhach = Phong::where('trang_thai', 'dang_o')->count();
        
        // 3. Số phòng chờ dọn / bảo trì
        $soPhongChoDon = Phong::where('don_dep', true)->count();
        $soPhongBaoTri = Phong::where('trang_thai', 'bao_tri')->count();
        $soPhongChoDonBaoTri = $soPhongChoDon + $soPhongBaoTri;
        
        // 4. Số đặt phòng hôm nay
        $soDatPhongHomNay = DatPhong::where(function($query) use ($todayDate) {
                $query->whereDate('ngay_nhan_phong', $todayDate)
                      ->orWhereDate('created_at', $todayDate);
            })
            ->where('trang_thai', '!=', 'da_huy')
            ->count();
        
        // 5. Tổng doanh thu hôm nay
        $todayPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereDate('created_at', $todayDate)
            ->sum('so_tien');
        $todayInvoiced = HoaDon::whereDate('created_at', $todayDate)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        $tongDoanhThuHomNay = $todayPaid + $todayInvoiced;
        
        // 6. Doanh thu tháng này
        $month = $today->month;
        $year = $today->year;
        $monthlyPaid = GiaoDich::where('trang_thai', 'thanh_cong')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('so_tien');
        $monthlyInvoiced = HoaDon::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotIn('trang_thai', ['da_huy'])
            ->sum('tong_thuc_thu');
        $doanhThuThangNay = $monthlyPaid + $monthlyInvoiced;

        $bookings = DatPhong::with([
            'nguoiDung',
            'datPhongItems.loaiPhong',
            'phongDaDats.phong',
            'hoaDons.hoaDonItems.phong',
            'roomChanges.oldRoom',
            'roomChanges.newRoom',
            'roomChanges.changedByUser'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('staff.bookings', compact(
            'bookings',
            'soPhongTrong',
            'soPhongDangCoKhach',
            'soPhongChoDonBaoTri',
            'soDatPhongHomNay',
            'tongDoanhThuHomNay',
            'doanhThuThangNay'
        ));
    }

    /**
     * Quick view booking details for modal (AJAX)
     */
    public function quickView($id)
    {
        try {
            $booking = DatPhong::with([
                'nguoiDung',
                'datPhongItems.phong',
                'datPhongItems.loaiPhong',
                'hoaDons.hoaDonItems.phong'
            ])->findOrFail($id);

            // Get room codes
            $roomCodes = [];
            if ($booking->datPhongItems && $booking->datPhongItems->isNotEmpty()) {
                foreach ($booking->datPhongItems as $item) {
                    if (!empty($item->phong) && !empty($item->phong->ma_phong)) {
                        $roomCodes[] = $item->phong->ma_phong;
                    } elseif (!empty($item->phong_id)) {
                        $roomCodes[] = 'Phòng #' . $item->phong_id;
                    }
                }
            } else {
                // Fallback to hoa_don_items if dat_phong_item deleted
                if (!empty($booking->hoaDons)) {
                    foreach ($booking->hoaDons as $hd) {
                        if (empty($hd->hoaDonItems)) continue;
                        foreach ($hd->hoaDonItems as $it) {
                            if (in_array($it->type, ['room', 'room_booking'])) {
                                if (!empty($it->phong) && !empty($it->phong->ma_phong)) {
                                    $roomCodes[] = $it->phong->ma_phong;
                                } elseif (!empty($it->phong_id)) {
                                    $roomCodes[] = 'Phòng #' . $it->phong_id;
                                }
                            }
                        }
                    }
                }
            }
            $roomCodes = array_values(array_unique(array_filter($roomCodes)));

            // Get notes from snapshot_meta
            $notes = [];
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                $meta = json_decode($booking->snapshot_meta, true) ?? [];
            } else {
                $meta = [];
            }
            
            if (!empty($meta['staff_notes']) && is_array($meta['staff_notes'])) {
                $notes = array_slice($meta['staff_notes'], -1); // Get last note
            }

            $data = [
                'id' => $booking->id,
                'ma_tham_chieu' => $booking->ma_tham_chieu,
                'trang_thai' => $booking->trang_thai,
                'customer_name' => $booking->nguoiDung->name ?? $booking->contact_name ?? 'N/A',
                'customer_email' => $booking->nguoiDung->email ?? $booking->contact_email ?? 'N/A',
                'customer_phone' => $booking->nguoiDung->phone ?? $booking->contact_phone ?? 'N/A',
                'ngay_nhan_phong' => Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0)->format('d/m/Y H:i'),
                'ngay_tra_phong' => Carbon::parse($booking->ngay_tra_phong)->setTime(12, 0)->format('d/m/Y H:i'),
                'rooms' => $roomCodes,
                'tong_tien' => $booking->tong_tien ?? 0,
                'deposit_amount' => $booking->deposit_amount ?? 0,
                'notes' => $notes
            ];

            return response()->json([
                'success' => true,
                'booking' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Quick view error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể tải thông tin booking'
            ], 500);
        }
    }

    /**
     * Add quick note to booking
     */
    public function addNote(Request $request, $id)
    {
        try {
            $request->validate([
                'note' => 'required|string|max:1000'
            ]);

            $booking = DatPhong::findOrFail($id);
            
            // Get current meta
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                $meta = json_decode($booking->snapshot_meta, true) ?? [];
            } else {
                $meta = [];
            }

            // Initialize staff_notes array if not exists
            if (!isset($meta['staff_notes']) || !is_array($meta['staff_notes'])) {
                $meta['staff_notes'] = [];
            }

            // Add new note
            $meta['staff_notes'][] = [
                'content' => $request->note,
                'created_at' => now()->format('d/m/Y H:i'),
                'created_by' => Auth::user()->name ?? 'Staff',
                'created_by_id' => Auth::id()
            ];

            // Save meta
            $booking->update([
                'snapshot_meta' => $meta
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ghi chú đã được lưu thành công'
            ]);

        } catch (\Exception $e) {
            Log::error('Add note error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu ghi chú'
            ], 500);
        }
    }


    protected function checkAvailability($phong_id, $start, $end)
    {
        $currentTime = now();
        $phong = Phong::find($phong_id);
        if (!$phong) return false;


        if (in_array($phong->trang_thai, ['trong', 'dang_don_dep'])) {
        } else {

            $currentBooking = DatPhong::whereHas('datPhongItems', function ($q) use ($phong_id) {
                $q->where('phong_id', $phong_id);
            })->whereIn('trang_thai', ['da_gan_phong', 'dang_su_dung'])
                ->orderBy('ngay_tra_phong', 'desc')->first();
            if ($currentBooking) {
                $currentCheckout = SupportCarbon::parse($currentBooking->ngay_tra_phong)->setTime(12, 0);
                $newCheckin = Carbon::parse($start)->setTime(14, 0);
                if ($currentCheckout > $newCheckin) {
                    return false;
                }
            }
        }


        $overlappingBookings = PhongDaDat::where('phong_id', $phong_id)
            ->whereNotIn('trang_thai', ['da_huy', 'hoan_thanh'])
            ->where('checkin_datetime', '<', $end)
            ->where('checkout_datetime', '>', $start)
            ->count();

        $holds = GiuPhong::where('phong_id', $phong_id)
            ->where('het_han_luc', '>', $currentTime)
            ->where('released', false)
            ->count();

        return ($overlappingBookings + $holds) == 0;
    }
    public function rooms(Request $request)
    {
        $query = Phong::with(['tang', 'datPhongItems.datPhong.nguoiDung']);

        // Lọc theo mã phòng
        if ($ma_phong = $request->input('ma_phong')) {
            $query->where('ma_phong', 'like', '%' . $ma_phong . '%');
        }


        if ($trang_thai = $request->input('trang_thai')) {
            if (in_array($trang_thai, ['dang_su_dung', 'dang_o', 'da_xac_nhan'])) {

                $query->whereHas('datPhongItems.datPhong', function ($q) use ($trang_thai) {
                    $q->where('trang_thai', $trang_thai);
                });
            } else {

                $query->where('trang_thai', $trang_thai);
            }
        }

        $rooms = $query->orderBy('ma_phong')->paginate(12);

        return view('staff.rooms', compact('rooms'));
    }




    public function updateRoom(Request $request, Phong $room)
    {
        $request->validate([
            'trang_thai' => 'required|in:trong,dang_o,dang_don_dep',
        ]);
        $room->update([
            'trang_thai' => $request->trang_thai,
        ]);
        return redirect()->route('staff.rooms')->with('success', 'Cập nhật trạng thái phòng ' . $room->ma_phong . ' thành công.');
    }

    public function checkinFromRoom(Request $request, Phong $room)
    {
        $request->validate([
            'booking_id' => 'required|exists:dat_phong,id',
        ]);

        $booking = DatPhong::with(['datPhongItems', 'giaoDichs'])->findOrFail($request->booking_id);

        // Kiểm tra booking có thuộc phòng này không
        $hasRoom = $booking->datPhongItems()->where('phong_id', $room->id)->exists();
        if (!$hasRoom) {
            return redirect()->route('staff.rooms')->with('error', 'Booking không thuộc phòng này.');
        }

        // Kiểm tra trạng thái booking
        if ($booking->trang_thai !== 'da_xac_nhan') {
            return redirect()->route('staff.rooms')->with('error', 'Booking không ở trạng thái chờ check-in.');
        }

        // Kiểm tra thanh toán
        $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
        $remaining = $booking->tong_tien - $paid;

        if ($remaining > 0) {
            return redirect()->route('staff.rooms')->with('error', "Cần thanh toán còn lại " . number_format($remaining) . " VND trước khi check-in.");
        }

        // Kiểm tra checkin sớm/muộn
        $now = \Carbon\Carbon::now();
        $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
        $standardCheckinTime = $checkinDate->copy()->setTime(14, 0, 0);
        
        $isEarlyCheckin = false;
        $isLateCheckin = false;
        $earlyCheckinFee = 0;
        
        // Kiểm tra checkin muộn (sang ngày khác) - HỦY ĐẶT PHÒNG
        if ($now->isAfter($checkinDate->endOfDay())) {
            DB::transaction(function () use ($booking) {
                $booking->update([
                    'trang_thai' => 'da_huy',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Hủy đặt phòng do checkin muộn (sang ngày khác)',
                ]);
                
                DatPhongItem::where('dat_phong_id', $booking->id)->delete();
                
                if (Schema::hasTable('giu_phong')) {
                    DB::table('giu_phong')
                        ->where('dat_phong_id', $booking->id)
                        ->delete();
                }
            });
            
            return redirect()->route('staff.rooms')->with('error', 'Đặt phòng đã bị hủy do checkin muộn (sang ngày khác). Ngày checkin dự kiến: ' . $checkinDate->format('d/m/Y'));
        }
        
        // Kiểm tra checkin sớm
        if ($now->isBefore($standardCheckinTime) && $now->isSameDay($checkinDate)) {
            $isEarlyCheckin = true;
            $hoursEarly = $now->diffInHours($standardCheckinTime, false);
            if ($hoursEarly > 0) {
                $datPhongItems = $booking->datPhongItems;
                $dailyTotal = $datPhongItems->reduce(function ($carry, $item) {
                    $qty = $item->so_luong ?? 1;
                    $unit = $item->gia_tren_dem ?? 0;
                    return $carry + ($unit * $qty);
                }, 0.0);
                
                // Kiểm tra hình thức thanh toán (50% hay 100%)
                if (is_array($booking->snapshot_meta)) {
                    $meta = $booking->snapshot_meta;
                } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                    $meta = json_decode($booking->snapshot_meta, true) ?? [];
                } else {
                    $meta = [];
                }
                $depositPercentage = $meta['deposit_percentage'] ?? 50;
                
                // Tính tổng số tiền đã thanh toán
                $paidAmount = GiaoDich::where('dat_phong_id', $booking->id)
                    ->where('trang_thai', 'thanh_cong')
                    ->sum('so_tien');
                
                // Tính tổng tiền booking
                $totalAmount = $booking->tong_tien ?? 0;
                
                // Xác định hình thức thanh toán: >= 95% coi là 100%
                $isFullPayment = false;
                if ($totalAmount > 0) {
                    $paymentPercentage = ($paidAmount / $totalAmount) * 100;
                    $isFullPayment = ($paymentPercentage >= 95) || ($depositPercentage == 100);
                }
                
                $perHourRate = $dailyTotal * 0.3 / 24;
                
                if ($isFullPayment) {
                    // Thanh toán 100%: tính phí không giới hạn (100%)
                    $earlyCheckinFee = $perHourRate * $hoursEarly;
                } else {
                    // Đặt cọc 50%: tối đa 50% giá phòng/ngày
                    $earlyCheckinFee = min($perHourRate * $hoursEarly, $dailyTotal * 0.5);
                }
                
                $earlyCheckinFee = (int) round($earlyCheckinFee, 0);
            }
        }
        
        // Kiểm tra checkin muộn (sau 14:00 nhưng trong cùng ngày)
        if ($now->isAfter($standardCheckinTime) && $now->isSameDay($checkinDate)) {
            $isLateCheckin = true;
        }

        // Kiểm tra phòng bị chặn bởi đơn checkout muộn
        $bookingPhongIds = $booking->datPhongItems->pluck('phong_id')->filter()->toArray();
        if (!empty($bookingPhongIds) && in_array($room->id, $bookingPhongIds)) {
            // Tìm các đơn có blocks_checkin = true và trang_thai = 'dang_su_dung'
            $blockedBookings = DatPhong::where('blocks_checkin', true)
                ->where('trang_thai', 'dang_su_dung')
                ->with('datPhongItems')
                ->get();
            
            // Lấy danh sách phòng_id từ các đơn bị chặn
            $blockedPhongIds = collect();
            foreach ($blockedBookings as $blockedBooking) {
                $blockedPhongIds = $blockedPhongIds->merge(
                    $blockedBooking->datPhongItems->pluck('phong_id')->filter()
                );
            }
            $blockedPhongIds = $blockedPhongIds->unique()->toArray();
            
            // Kiểm tra xem phòng này có bị chặn không
            if (in_array($room->id, $blockedPhongIds)) {
                return redirect()->route('staff.rooms')->with('error', 'Không thể check-in: Đang có đơn checkout muộn trong phòng này. Vui lòng chờ đơn checkout hoàn tất.');
            }
        }

        // Thực hiện check-in
        DB::transaction(function () use ($booking, $room, $isEarlyCheckin, $isLateCheckin, $earlyCheckinFee) {
            // Cập nhật meta với thông tin check-in (giữ nguyên CCCD nếu đã có)
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                /** @var string $snapshotMeta */
                $snapshotMeta = $booking->snapshot_meta;
                $meta = json_decode($snapshotMeta, true) ?? [];
            } else {
                $meta = [];
            }
            // Kiểm tra xem đã thanh toán phụ thu checkin sớm chưa
            if (is_array($booking->snapshot_meta)) {
                $meta = $booking->snapshot_meta;
            } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
                $meta = json_decode($booking->snapshot_meta, true) ?? [];
            } else {
                $meta = [];
            }
            
            $meta['checkin_at'] = now()->toDateTimeString();
            $meta['checkin_by'] = Auth::id();
            $hasPaidEarlyCheckinFee = !empty($meta['early_checkin_fee_paid']) && $meta['early_checkin_fee_paid'] > 0;
            
            // Tạo hóa đơn phụ thu nếu checkin sớm và chưa thanh toán phụ thu
            $hoaDon = null;
            if ($isEarlyCheckin && $earlyCheckinFee > 0 && !$hasPaidEarlyCheckinFee) {
                $hoaDon = HoaDon::where('dat_phong_id', $booking->id)
                    ->where('trang_thai', '!=', 'da_thanh_toan')
                    ->orderByDesc('id')
                    ->first();
                
                if (!$hoaDon) {
                    $hoaDon = HoaDon::create([
                        'dat_phong_id' => $booking->id,
                        'so_hoa_don' => 'HD' . time() . rand(100, 999),
                        'tong_thuc_thu' => 0,
                        'don_vi' => $booking->don_vi_tien ?? 'VND',
                        'trang_thai' => 'da_xuat',
                        'created_by' => Auth::id(),
                    ]);
                }
                
                HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'early_checkin_fee',
                    'name' => 'Phụ thu checkin sớm',
                    'quantity' => 1,
                    'unit_price' => $earlyCheckinFee,
                    'amount' => $earlyCheckinFee,
                    'note' => 'Phụ thu checkin sớm',
                ]);
                
                $hoaDon->tong_thuc_thu = (float)$hoaDon->tong_thuc_thu + $earlyCheckinFee;
                $hoaDon->save();
            } elseif ($isEarlyCheckin && $hasPaidEarlyCheckinFee) {
                // Đã thanh toán phụ thu rồi, không tạo hóa đơn nữa
                // Nhưng vẫn cần cập nhật early_checkin_fee_amount để ghi nhận
                $earlyCheckinFee = $meta['early_checkin_fee_paid'];
            }

            // Cập nhật booking
            $booking->update([
                'trang_thai' => 'dang_su_dung',
                'checked_in_at' => now(),
                'checked_in_by' => Auth::id(),
                'snapshot_meta' => $meta,
                'is_early_checkin' => $isEarlyCheckin,
                'early_checkin_fee_amount' => $isEarlyCheckin ? $earlyCheckinFee : 0,
                'is_late_checkin' => $isLateCheckin,
            ]);

            // Cập nhật trạng thái phòng
            $room->update([
                'trang_thai' => 'dang_o',
            ]);

            // Gửi thông báo check-in
            $notificationService = new PaymentNotificationService();
            $notificationService->sendCheckinNotification($booking);
        });
        
        $successMessage = 'Check-in thành công cho phòng ' . $room->ma_phong . ' - Booking ' . $booking->ma_tham_chieu;
        if ($isEarlyCheckin && $earlyCheckinFee > 0) {
            $successMessage .= '. Phụ thu checkin sớm: ' . number_format($earlyCheckinFee) . ' VND đã được ghi nhận trong hóa đơn.';
        } elseif ($isLateCheckin) {
            $successMessage .= ' (Checkin muộn trong ngày).';
        }

        return redirect()->route('staff.rooms')
            ->with('success', $successMessage);
    }

    public function showBooking(DatPhong $booking)
    {
        $booking->load([
            'datPhongItems.phong', 
            'datPhongItems.loaiPhong', 
            'nguoiDung', 
            'checkedInBy', 
            'giaoDichs',
            'roomChanges.oldRoom',
            'roomChanges.newRoom',
            'roomChanges.changedByUser'
        ]);

        if (is_array($booking->snapshot_meta)) {
            $meta = $booking->snapshot_meta;
        } elseif (is_string($booking->snapshot_meta) && !empty($booking->snapshot_meta)) {
            /** @var string $snapshotMeta */
            $snapshotMeta = $booking->snapshot_meta;
            $meta = json_decode($snapshotMeta, true) ?? [];
        } else {
            $meta = [];
        }

        $roomIds = $booking->datPhongItems->pluck('phong_id')->filter()->toArray();

        $consumptions = PhongVatDungConsumption::where('dat_phong_id', $booking->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('phong_id');

        $incidentsCollection = VatDungIncident::where('dat_phong_id', $booking->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $bookingInvoiceIds = HoaDon::where('dat_phong_id', $booking->id)->pluck('id')->toArray();
        $hoaDonItemsForBooking = HoaDonItem::whereIn('hoa_don_id', $bookingInvoiceIds)->get();

        $incidentBookingIds = $incidentsCollection->pluck('dat_phong_id')->unique()->filter()->values()->all();
        $extraBookingIds = HoaDon::whereIn('id', $hoaDonItemsForBooking->pluck('hoa_don_id')->unique()->filter()->values()->all())
            ->pluck('dat_phong_id')
            ->filter()
            ->toArray();
        $allBookingIds = array_unique(array_filter(array_merge($incidentBookingIds, $extraBookingIds, [$booking->id])));

        $bookingMap = [];
        if (!empty($allBookingIds)) {
            $bookingMap = DatPhong::whereIn('id', $allBookingIds)
                ->pluck('ma_tham_chieu', 'id')
                ->toArray();
        }

        $incidentsCollection = $incidentsCollection->map(function ($ins) use ($hoaDonItemsForBooking, $bookingMap) {
            $billedItem = $hoaDonItemsForBooking->first(function ($h) use ($ins) {
                if (($h->type ?? null) === 'incident' && !empty($h->ref_id) && (int)$h->ref_id === (int)$ins->id) {
                    return true;
                }
                if (!empty($h->vat_dung_id) && !empty($ins->vat_dung_id) && (int)$h->vat_dung_id === (int)$ins->vat_dung_id) {
                    return true;
                }
                return false;
            });

            $ins->billed = $billedItem ? true : false;
            $ins->billed_hoa_don_id = $billedItem ? $billedItem->hoa_don_id : null;

            if ($ins->billed_hoa_don_id) {
                $hd = HoaDon::find($ins->billed_hoa_don_id);
                if ($hd) {
                    $ins->billed_dat_phong_id = $hd->dat_phong_id;
                    $ins->billed_booking_code = $bookingMap[$hd->dat_phong_id] ?? null;
                } else {
                    $ins->billed_dat_phong_id = null;
                    $ins->billed_booking_code = null;
                }
            } else {
                $ins->billed_dat_phong_id = null;
                $ins->billed_booking_code = null;
            }

            return $ins;
        });

        $incidents = $incidentsCollection->groupBy('phong_id');
        $incidentsByInstance = $incidentsCollection
            ->filter(fn($it) => !empty($it->phong_vat_dung_instance_id))
            ->groupBy('phong_vat_dung_instance_id');

        $instances = PhongVatDungInstance::whereIn('phong_id', $roomIds)
            ->with('vatDung')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('phong_id');

        $roomLinesFromInvoice = collect();
        if ($booking->trang_thai === 'hoan_thanh') {
            $invoiceItems = HoaDonItem::whereHas('hoaDon', function ($q) use ($booking) {
                $q->where('dat_phong_id', $booking->id);
            })
                ->whereIn('type', ['room_booking'])
                ->with(['phong', 'loaiPhong'])
                ->orderBy('id')
                ->get();

            foreach ($invoiceItems as $it) {
                $roomLinesFromInvoice->push([
                    'phong_id'   => $it->phong_id,
                    'ma_phong'   => $it->phong?->ma_phong ?? null,
                    'loai'       => $it->loaiPhong?->ten ?? ($it->name ?? null),
                    'unit_price' => (float)($it->unit_price ?? 0),
                    'qty'        => (int)($it->quantity ?? 1),
                    'nights'     => 1,
                    'line_total' => (float)($it->amount ?? 0),
                    'note'       => $it->note ?? null,
                ]);
            }
        }

        $refundItems = HoaDonItem::whereHas('hoaDon', function ($q) use ($booking) {
            $q->where('dat_phong_id', $booking->id);
        })->where('type', 'refund')->get();

        $earlyRefundTotal = 0;
        if ($refundItems->isNotEmpty()) {
            $earlyRefundTotal = $refundItems->sum('amount');
            $earlyRefundTotal = $earlyRefundTotal < 0 ? abs($earlyRefundTotal) : $earlyRefundTotal;
        }

        $finalInvoice = null;
        $finalInvoiceTotal = null;
        $finalInvoiceId = null;
        if ($booking->trang_thai === 'hoan_thanh') {
            $finalInvoice = HoaDon::where('dat_phong_id', $booking->id)
                ->where('trang_thai', 'da_thanh_toan')
                ->orderByDesc('id')
                ->first()
                ?? HoaDon::where('dat_phong_id', $booking->id)
                ->orderByDesc('id')
                ->first();

            if ($finalInvoice) {
                $finalInvoiceTotal = (float) $finalInvoice->tong_thuc_thu;
                $finalInvoiceId = $finalInvoice->id;
            }
        }

        $lateFeeTotal = 0;
        $lateItems = HoaDonItem::whereHas('hoaDon', function ($q) use ($booking) {
            $q->where('dat_phong_id', $booking->id);
        })->where('type', 'late_fee')->get();

        if ($lateItems->isNotEmpty()) {
            $lateFeeTotal = (float) $lateItems->sum('amount');
        } else {
            $lateFeeTotal = (float) ($booking->late_checkout_fee_amount ?? 0);
        }

        return view('staff.bookings.show', compact(
            'booking',
            'meta',
            'consumptions',
            'incidents',
            'instances',
            'incidentsByInstance',
            'bookingMap',
            'roomLinesFromInvoice',
            'earlyRefundTotal',
            'finalInvoice',
            'finalInvoiceTotal',
            'finalInvoiceId',
            'lateFeeTotal'
        ));
    }

    /**
     * Cancel a booking (staff-side) with advanced refund policy
     */
    public function cancel($id)
    {
        $booking = DatPhong::findOrFail($id);

        // Check if the booking status allows cancellation
        if (!in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan'])) {
            return back()->with('error', 'Không thể hủy đặt phòng với trạng thái hiện tại: ' . $booking->trang_thai);
        }

        try {
            DB::beginTransaction();

            // Calculate refund using advanced policy (Option B)
            // Use actual check-in time (14:00) for accurate calculation
            $checkInDateTime = Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0, 0);
            $now = Carbon::now();
            $daysUntilCheckIn = $now->diffInDays($checkInDateTime, false); // Calculates full days until 14:00 check-in time
            
            // Determine deposit type from snapshot_meta
            $meta = $booking->snapshot_meta ?? [];
            $depositType = $meta['deposit_percentage'] ?? 50;
            
            // Calculate refund percentage using Option B logic
            $refundPercentage = $this->calculateRefundPercentage($daysUntilCheckIn, $depositType);
            
            // Calculate refund amount
            $paidAmount = $booking->deposit_amount ?? 0;
            $refundAmount = $paidAmount * ($refundPercentage / 100);

            // Update booking status to cancelled with refund info
            $booking->update([
                'trang_thai' => 'da_huy',
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'cancelled_at' => now(),
                'cancellation_reason' => 'Admin/Staff hủy đặt phòng'
            ]);

            // Delete giu_phong records
            if (Schema::hasTable('giu_phong')) {
                DB::table('giu_phong')
                    ->where('dat_phong_id', $booking->id)
                    ->delete();
            }

            // NOTE: Do NOT change existing successful transactions to 'that_bai'
            // Keep audit trail of actual money received
            // If refund needed, create NEW refund transaction instead
            
            // Create refund transaction if refund amount > 0
            if ($refundAmount > 0) {
                GiaoDich::create([
                    'dat_phong_id' => $booking->id,
                    'so_tien' => $refundAmount,
                    'trang_thai' => 'da_hoan',
                    'nha_cung_cap' => 'Hoàn tiền hủy phòng',
                    'ghi_chu' => "Hoàn {$refundPercentage}% tiền cọc do staff hủy booking",
                ]);
            }

            // Delete dat_phong_items
            DatPhongItem::where('dat_phong_id', $booking->id)->delete();

            // Create refund request if refund amount > 0
            if ($refundAmount > 0) {
                RefundRequest::create([
                    'dat_phong_id' => $booking->id,
                    'amount' => $refundAmount,
                    'percentage' => $refundPercentage,
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);
            }

            DB::commit();

            Log::info('Booking cancelled by staff with refund', [
                'booking_id' => $booking->id,
                'ma_tham_chieu' => $booking->ma_tham_chieu,
                'days_until_checkin' => $daysUntilCheckIn,
                'deposit_type' => $depositType,
                'refund_percentage' => $refundPercentage,
                'refund_amount' => $refundAmount,
            ]);

            $message = 'Đã hủy đặt phòng thành công. ';
            if ($refundAmount > 0) {
                $message .= sprintf(
                    'Số tiền hoàn: %s ₫ (%d%%).',
                    number_format($refundAmount, 0, ',', '.'),
                    $refundPercentage
                );
            } else {
                $message .= 'Không có tiền hoàn.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Staff booking cancellation error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Có lỗi xảy ra khi hủy đặt phòng.');
        }
    }

    /**
     * Calculate refund percentage based on Option B policy
     */
    private function calculateRefundPercentage(int $daysUntilCheckIn, int $depositType): int
    {
        if ($depositType == 100) {
            // Thanh toán 100% - được ưu đãi khi hủy
            if ($daysUntilCheckIn >= 7) {
                return 90;
            } elseif ($daysUntilCheckIn >= 3) {
                return 60;
            } elseif ($daysUntilCheckIn >= 1) {
                return 40;
            } else {
                return 20;
            }
        } else {
            // Đặt cọc 50% - policy thông thường
            if ($daysUntilCheckIn >= 7) {
                return 100;
            } elseif ($daysUntilCheckIn >= 3) {
                return 70;
            } elseif ($daysUntilCheckIn >= 1) {
                return 30;
            } else {
                return 0;
            }
        }
    }

    /**
     * Room Analytics Dashboard
     * Shows booking statistics by room type and individual rooms
     */
    public function roomAnalytics(Request $request)
    {
        // Get filters from request
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $roomTypeId = $request->input('room_type_id');
        $roomId = $request->input('room_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Create date for display
        $selectedDate = Carbon::create($year, $month, 1);
        
        // Determine date range - use custom range if provided, otherwise use month/year
        if ($startDate && $endDate) {
            $dateStart = Carbon::parse($startDate)->startOfDay();
            $dateEnd = Carbon::parse($endDate)->endOfDay();
        } else {
            $dateStart = Carbon::create($year, $month, 1)->startOfDay();
            $dateEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }
        
        // === 1. Stats by Room Type ===
        $roomTypeStatsQuery = DB::table('dat_phong_item as dpi')
            ->join('dat_phong as dp', 'dpi.dat_phong_id', '=', 'dp.id')
            ->join('loai_phong as lp', 'dpi.loai_phong_id', '=', 'lp.id')
            ->whereBetween('dp.ngay_nhan_phong', [$dateStart, $dateEnd])
            ->whereIn('dp.trang_thai', ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh']);
        
        // Apply room type filter
        if ($roomTypeId) {
            $roomTypeStatsQuery->where('lp.id', $roomTypeId);
        }
        
        $roomTypeStats = $roomTypeStatsQuery
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('COUNT(DISTINCT dp.id) as total_bookings'),
                DB::raw('SUM(dpi.tong_item) as total_revenue')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->get();
        
        // Get total rooms per type for occupancy calculation
        $roomCounts = DB::table('phong')
            ->select('loai_phong_id', DB::raw('COUNT(*) as total_rooms'))
            ->groupBy('loai_phong_id')
            ->pluck('total_rooms', 'loai_phong_id');
        
        // Calculate occupancy rate (days in range)
        $daysInRange = $dateStart->diffInDays($dateEnd) + 1;
        
        // Enhance room type stats with totals and occupancy
        $roomTypeStats = $roomTypeStats->map(function($stat) use ($roomCounts, $daysInRange) {
            $totalRooms = $roomCounts[$stat->id] ?? 0;
            $maxPossibleBookings = $totalRooms * $daysInRange;
            $occupancyRate = $maxPossibleBookings > 0 
                ? round(($stat->total_bookings / $maxPossibleBookings) * 100, 1)
                : 0;
            
            return (object)[
                'id' => $stat->id,
                'ten' => $stat->ten,
                'total_rooms' => $totalRooms,
                'total_bookings' => $stat->total_bookings,
                'total_revenue' => $stat->total_revenue ?? 0,
                'occupancy_rate' => $occupancyRate
            ];
        });
        
        // === 2. Stats by Individual Room ===
        $roomStatsQuery = DB::table('phong as p')
            ->leftJoin('dat_phong_item as dpi', 'p.id', '=', 'dpi.phong_id')
            ->leftJoin('dat_phong as dp', function($join) use ($dateStart, $dateEnd) {
                $join->on('dpi.dat_phong_id', '=', 'dp.id')
                    ->whereBetween('dp.ngay_nhan_phong', [$dateStart, $dateEnd])
                    ->whereIn('dp.trang_thai', ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh']);
            })
            ->join('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id');
        
        // Apply filters
        if ($roomTypeId) {
            $roomStatsQuery->where('lp.id', $roomTypeId);
        }
        if ($roomId) {
            $roomStatsQuery->where('p.id', $roomId);
        }
        
        $roomStats = $roomStatsQuery
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten',
                DB::raw('COUNT(DISTINCT CASE WHEN dp.id IS NOT NULL THEN dp.id END) as booking_count'),
                DB::raw('SUM(CASE WHEN dp.id IS NOT NULL THEN dpi.tong_item ELSE 0 END) as revenue')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderBy('lp.ten')
            ->orderBy('p.ma_phong')
            ->get();
        
        // Calculate occupancy for each room
        $daysInRange = $dateStart->diffInDays($dateEnd) + 1;
        $roomStats = $roomStats->map(function($room) use ($daysInRange) {
            $occupancyRate = $daysInRange > 0 
                ? round(($room->booking_count / $daysInRange) * 100, 1)
                : 0;
            
            $room->occupancy_rate = $occupancyRate;
            return $room;
        });
        
        // === 3. Prepare Chart Data ===
        $chartLabels = $roomTypeStats->pluck('ten')->toArray();
        $chartData = $roomTypeStats->pluck('total_bookings')->toArray();
        
        // === 4. Generate filter options ===
        $monthOptions = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthOptions[$i] = Carbon::create(null, $i, 1)->locale('vi')->translatedFormat('F');
        }
        
        $yearOptions = range(date('Y'), date('Y') - 3); // Last 3 years
        
        // Get all room types for filter dropdown
        $allRoomTypes = LoaiPhong::orderBy('ten')->get(['id', 'ten']);
        
        // Get all rooms for filter dropdown
        $allRooms = Phong::with('loaiPhong:id,ten')
            ->orderBy('ma_phong')
            ->get(['id', 'ma_phong', 'loai_phong_id']);
        
        return view('staff.analytics.rooms', compact(
            'roomTypeStats',
            'roomStats',
            'chartLabels',
            'chartData',
            'selectedDate',
            'month',
            'year',
            'monthOptions',
            'yearOptions',
            'allRoomTypes',
            'allRooms',
            'roomTypeId',
            'roomId',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Room Analytics as PDF
     * Simple HTML export that can be printed to PDF
     */
    public function exportRoomAnalyticsPDF(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $selectedDate = Carbon::create($year, $month, 1);
        
        // Reuse same queries from roomAnalytics
        $roomTypeStats = DB::table('dat_phong_item as dpi')
            ->join('dat_phong as dp', 'dpi.dat_phong_id', '=', 'dp.id')
            ->join('loai_phong as lp', 'dpi.loai_phong_id', '=', 'lp.id')
            ->whereYear('dp.ngay_nhan_phong', $year)
            ->whereMonth('dp.ngay_nhan_phong', $month)
            ->whereIn('dp.trang_thai', ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh'])
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('COUNT(DISTINCT dp.id) as total_bookings'),
                DB::raw('SUM(dpi.tong_item) as total_revenue')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->get();
        
        $roomCounts = DB::table('phong')
            ->select('loai_phong_id', DB::raw('COUNT(*) as total_rooms'))
            ->groupBy('loai_phong_id')
            ->pluck('total_rooms', 'loai_phong_id');
        
        $daysInMonth = $selectedDate->daysInMonth;
        
        $roomTypeStats = $roomTypeStats->map(function($stat) use ($roomCounts, $daysInMonth) {
            $totalRooms = $roomCounts[$stat->id] ?? 0;
            $maxPossibleBookings = $totalRooms * $daysInMonth;
            $occupancyRate = $maxPossibleBookings > 0 
                ? round(($stat->total_bookings / $maxPossibleBookings) * 100, 1)
                : 0;
            
            return (object)[
                'id' => $stat->id,
                'ten' => $stat->ten,
                'total_rooms' => $totalRooms,
                'total_bookings' => $stat->total_bookings,
                'total_revenue' => $stat->total_revenue ?? 0,
                'occupancy_rate' => $occupancyRate
            ];
        });
        
        $roomStats = DB::table('phong as p')
            ->leftJoin('dat_phong_item as dpi', 'p.id', '=', 'dpi.phong_id')
            ->leftJoin('dat_phong as dp', function($join) use ($year, $month) {
                $join->on('dpi.dat_phong_id', '=', 'dp.id')
                    ->whereYear('dp.ngay_nhan_phong', '=', $year)
                    ->whereMonth('dp.ngay_nhan_phong', '=', $month)
                    ->whereIn('dp.trang_thai', ['da_xac_nhan', 'dang_su_dung', 'hoan_thanh']);
            })
            ->join('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id')
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten',
                DB::raw('COUNT(DISTINCT CASE WHEN dp.id IS NOT NULL THEN dp.id END) as booking_count'),
                DB::raw('SUM(CASE WHEN dp.id IS NOT NULL THEN dpi.tong_item ELSE 0 END) as revenue')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderBy('lp.ten')
            ->orderBy('p.ma_phong')
            ->get();
        
        $roomStats = $roomStats->map(function($room) use ($daysInMonth) {
            $occupancyRate = $daysInMonth > 0 
                ? round(($room->booking_count / $daysInMonth) * 100, 1)
                : 0;
            
            $room->occupancy_rate = $occupancyRate;
            return $room;
        });
        
        // Return printable HTML view
        return view('staff.analytics.rooms-pdf', compact(
            'roomTypeStats',
            'roomStats',
            'selectedDate'
        ));
    }

    public function clearRoomCleaning(Request $request, DatPhong $booking, Phong $room)
    {
        // Kiểm tra quyền (middleware role:nhan_vien|admin đã có ở route group)
        // Kiểm tra booking phải đang ở trạng thái da_xac_nhan (theo yêu cầu)
        if ($booking->id !== (int)$booking->id) {
            // chưa cần, DatPhong binding đã đảm bảo
        }

        if (!in_array($booking->trang_thai, ['da_xac_nhan'])) {
            // trả về cho UI
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Booking không hợp lệ để thao tác.'], 422);
            }
            return redirect()->back()->with('error', 'Chỉ có booking trạng thái "đã xác nhận" mới cho phép thao tác dọn dẹp.');
        }

        // kiểm tra phòng thuộc booking
        $roomIds = $booking->datPhongItems->pluck('phong_id')->filter()->map(fn($v) => (int)$v)->toArray();
        if (!in_array($room->id, $roomIds)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Phòng không thuộc booking này.'], 422);
            }
            return redirect()->back()->with('error', 'Phòng không thuộc booking này.');
        }

        if (! (bool) $room->don_dep) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Phòng đã được đánh dấu là đã dọn.'], 200);
            }
            return redirect()->back()->with('info', 'Phòng đã được đánh dấu là đã dọn.');
        }

        DB::transaction(function () use ($room) {
            $room->don_dep = false;
            $room->updated_at = now();
            $room->save();
            Log::info('Room cleaned by staff', ['room_id' => $room->id, 'by_user_id' => Auth::id() ?? null]);
        });

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã cập nhật: phòng đã được đánh dấu là đã dọn xong.', 'room_id' => $room->id]);
        }
        return redirect()->back()->with('success', 'Đã đánh dấu phòng là đã dọn xong.');
    }

    /**
     * Cancel individual room item from a multi-room booking (Staff/Admin)
     */
    public function cancelRoomItem(Request $request, $id, $itemId)
    {
        $booking = DatPhong::with(['datPhongItems.phong', 'datPhongItems.loaiPhong'])
            ->findOrFail($id);

        // Check booking status
        if (!in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan'])) {
            return back()->with('error', 'Không thể hủy phòng từ booking có trạng thái này.');
        }

        // Find the room item
        $roomItem = $booking->datPhongItems->firstWhere('id', $itemId);
        if (!$roomItem) {
            return back()->with('error', 'Không tìm thấy phòng trong booking.');
        }

        // Check if there are at least 2 rooms
        $activeRoomsCount = $booking->datPhongItems->count();
        if ($activeRoomsCount <= 1) {
            return back()->with('error', 'Không thể hủy phòng cuối cùng. Vui lòng hủy toàn bộ booking.');
        }

        try {
            DB::beginTransaction();

            // Calculate room price
            $roomPricePerNight = $roomItem->gia_tren_dem ?? 0;
            $nights = $roomItem->so_dem ?? 1;
            $qty = $roomItem->so_luong ?? 1;
            $roomTotalPrice = $roomPricePerNight * $nights * $qty;
            
            // Calculate refund
            $paidAmount = $booking->deposit_amount ?? 0;
            $currentBookingTotal = $booking->tong_tien;
            $roomProportion = $currentBookingTotal > 0 ? ($roomTotalPrice / $currentBookingTotal) : 0;
            $roomDepositShare = $paidAmount * $roomProportion;

            // Calculate refund percentage based on days until check-in
            $checkInDate = Carbon::parse($booking->ngay_nhan_phong);
            $now = Carbon::now();
            $daysUntilCheckIn = $now->diffInDays($checkInDate, false);
            
            $depositPercentage = ($booking->snapshot_meta['deposit_percentage'] ?? 50);
            
            if ($depositPercentage == 100) {
                if ($daysUntilCheckIn >= 7) $refundPercentage = 90;
                elseif ($daysUntilCheckIn >= 3) $refundPercentage = 60;
                elseif ($daysUntilCheckIn >= 1) $refundPercentage = 40;
                else $refundPercentage = 20;
            } else {
                if ($daysUntilCheckIn >= 7) $refundPercentage = 100;
                elseif ($daysUntilCheckIn >= 3) $refundPercentage = 70;
                elseif ($daysUntilCheckIn >= 1) $refundPercentage = 30;
                else $refundPercentage = 0;
            }

            $refundAmount = round($roomDepositShare * ($refundPercentage / 100));
            
            // Save room info before deletion
            // Phong has 'name', LoaiPhong has 'ten'
            $displayRoomName = $roomItem->phong->name ?? $roomItem->loaiPhong->ten ?? "Phòng #{$itemId}";
            $roomPhongId = $roomItem->phong_id;

            // Update booking totals
            $newBookingTotal = max(0, $currentBookingTotal - $roomTotalPrice);
            
            // QUAN TRỌNG: Khi hủy 1 phòng, toàn bộ phần cọc của phòng đó bị loại bỏ:
            // - Phần được hoàn (30%): trả về khách
            // - Phần bị phạt (70%): khách sạn giữ lại (MẤT LUÔN)
            // => Deposit còn lại = chỉ phần cọc của các phòng còn active
            $newDepositAmount = max(0, $paidAmount - $roomDepositShare);
            
            // Tính số tiền khách sạn giữ lại (phần phạt)
            $forfeitedAmount = $roomDepositShare - $refundAmount;
            
            $booking->update([
                'tong_tien' => $newBookingTotal,
                'deposit_amount' => $newDepositAmount,  // Chỉ còn cọc của các phòng active
            ]);

            // Delete related records
            // phong_da_dat uses dat_phong_item_id, not dat_phong_id
            PhongDaDat::where('dat_phong_item_id', $roomItem->id)->delete();
            
            // giu_phong uses dat_phong_id and phong_id
            if ($roomPhongId) {
                GiuPhong::where('dat_phong_id', $booking->id)
                    ->where('phong_id', $roomPhongId)
                    ->delete();
            }

            // Delete the room item
            $roomItem->delete();

            // Create refund transaction and request
            if ($refundAmount > 0) {
                // Note: GiaoDich table only has: dat_phong_id, nha_cung_cap, provider_txn_ref, so_tien, don_vi, trang_thai, ghi_chu
                // Valid trang_thai values: dang_cho, thanh_cong, that_bai, da_hoan
                GiaoDich::create([
                    'dat_phong_id' => $booking->id,
                    'nha_cung_cap' => 'hoan_tien',
                    'provider_txn_ref' => 'REFUND_ROOM_' . $booking->id . '_' . $itemId . '_' . time(),
                    'so_tien' => -$refundAmount,
                    'trang_thai' => 'dang_cho',
                    'ghi_chu' => "Hoàn tiền hủy phòng {$displayRoomName} (Staff) - {$refundPercentage}%",
                ]);

                RefundRequest::create([
                    'dat_phong_id' => $booking->id,
                    'user_id' => $booking->nguoi_dung_id,
                    'amount' => $refundAmount,
                    'percentage' => $refundPercentage,
                    'status' => 'pending',
                    'requested_at' => now(),
                    'refund_type' => 'single_room',
                    'dat_phong_item_id' => null,
                    'admin_note' => "Hủy phòng: {$displayRoomName} | Giá: " . number_format($roomTotalPrice) . "đ | Cọc phòng: " . number_format($roomDepositShare) . "đ | Hoàn: {$refundPercentage}% | Bởi Staff: " . (Auth::user()->name ?? 'Unknown'),
                ]);
            }

            DB::commit();

            Log::info('Staff cancelled room item', [
                'booking_id' => $booking->id,
                'item_id' => $itemId,
                'room_name' => $displayRoomName,
                'old_deposit' => $paidAmount,
                'room_deposit_share' => round($roomDepositShare),
                'refund_amount' => $refundAmount,
                'forfeited_amount' => $forfeitedAmount,  // Phần khách sạn giữ lại
                'new_deposit' => $newDepositAmount,
                'staff_id' => Auth::id(),
            ]);

            $message = sprintf(
                'Đã hủy phòng "%s" thành công. Số tiền hoàn: %s ₫ (%d%%).',
                $displayRoomName,
                number_format($refundAmount, 0, ',', '.'),
                $refundPercentage
            );

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff room cancellation error', [
                'booking_id' => $id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Có lỗi xảy ra khi hủy phòng: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get filtered room revenue data (AJAX)
     */
    public function getFilteredRoomRevenue(Request $request)
    {
        $today = now();
        $todayDate = $today->toDateString();
        $weekRange = [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()];
        $year = $request->input('year', $today->year);
        $month = $request->input('month', $today->month);
        
        // Get date filter values
        $filterStartDate = $request->input('filter_start_date');
        $filterEndDate = $request->input('filter_end_date');
        
        // If date filter is provided, use it for all queries
        $hasDateFilter = $filterStartDate && $filterEndDate;
        
        // Base query builder function
        $buildRoomRevenueQuery = function() use ($hasDateFilter, $filterStartDate, $filterEndDate) {
            $query = DB::table('hoa_don_items as hdi')
                ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
                ->join('phong as p', 'hdi.phong_id', '=', 'p.id')
                ->leftJoin('loai_phong as lp', 'p.loai_phong_id', '=', 'lp.id')
                ->whereNotIn('hd.trang_thai', ['da_huy'])
                ->whereNotNull('hdi.phong_id');
            
            // Apply date filter if provided
            if ($hasDateFilter) {
                $query->whereBetween(DB::raw('DATE(hd.created_at)'), [$filterStartDate, $filterEndDate]);
            }
            
            return $query;
        };
        
        // Room Revenue Today (or filtered date range)
        $roomRevenueTodayQuery = $buildRoomRevenueQuery();
        if (!$hasDateFilter) {
            $roomRevenueTodayQuery->whereDate('hd.created_at', $todayDate);
        }
        $roomRevenueToday = $roomRevenueTodayQuery
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Room Revenue Week (or filtered date range)
        $roomRevenueWeekQuery = $buildRoomRevenueQuery();
        if (!$hasDateFilter) {
            $roomRevenueWeekQuery->whereBetween(DB::raw('DATE(hd.created_at)'), $weekRange);
        }
        $roomRevenueWeek = $roomRevenueWeekQuery
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Room Revenue Month (or filtered date range)
        $roomRevenueMonthQuery = $buildRoomRevenueQuery();
        if (!$hasDateFilter) {
            $roomRevenueMonthQuery->whereYear('hd.created_at', $year)
                ->whereMonth('hd.created_at', $month);
        }
        
        $roomRevenueMonth = $roomRevenueMonthQuery
            ->select(
                'p.id',
                'p.ma_phong',
                'p.trang_thai',
                'lp.ten as loai_phong',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('p.id', 'p.ma_phong', 'p.trang_thai', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Calculate monthly revenue for percentage
        if ($hasDateFilter) {
            $monthlyRevenue = HoaDon::whereBetween(DB::raw('DATE(created_at)'), [$filterStartDate, $filterEndDate])
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
        } else {
            $monthlyRevenue = HoaDon::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
        }

        return response()->json([
            'success' => true,
            'roomRevenueToday' => $roomRevenueToday,
            'roomRevenueWeek' => $roomRevenueWeek,
            'roomRevenueMonth' => $roomRevenueMonth,
            'monthlyRevenue' => $monthlyRevenue
        ]);
    }

    /**
     * API endpoint to get filtered room type revenue data (AJAX)
     */
    public function getFilteredRoomTypeRevenue(Request $request)
    {
        $today = now();
        $todayDate = $today->toDateString();
        $weekRange = [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()];
        $year = $request->input('year', $today->year);
        $month = $request->input('month', $today->month);
        
        // Get date filter values
        $filterStartDate = $request->input('filter_roomtype_start_date');
        $filterEndDate = $request->input('filter_roomtype_end_date');
        
        // If date filter is provided, use it for all queries
        $hasDateFilter = $filterStartDate && $filterEndDate;
        
        // Base query builder function
        $buildRoomTypeRevenueQuery = function() use ($hasDateFilter, $filterStartDate, $filterEndDate) {
            $query = DB::table('hoa_don_items as hdi')
                ->join('hoa_don as hd', 'hdi.hoa_don_id', '=', 'hd.id')
                ->leftJoin('loai_phong as lp', 'hdi.loai_phong_id', '=', 'lp.id')
                ->whereNotIn('hd.trang_thai', ['da_huy'])
                ->whereNotNull('hdi.loai_phong_id')
                ->where('hdi.type', 'room_booking');
            
            // Apply date filter if provided
            if ($hasDateFilter) {
                $query->whereBetween(DB::raw('DATE(hd.created_at)'), [$filterStartDate, $filterEndDate]);
            }
            
            return $query;
        };
        
        // Room Type Revenue Today (or filtered date range)
        $roomTypeRevenueTodayQuery = $buildRoomTypeRevenueQuery();
        if (!$hasDateFilter) {
            $roomTypeRevenueTodayQuery->whereDate('hd.created_at', $todayDate);
        }
        $roomTypeRevenueToday = $roomTypeRevenueTodayQuery
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Room Type Revenue Week (or filtered date range)
        $roomTypeRevenueWeekQuery = $buildRoomTypeRevenueQuery();
        if (!$hasDateFilter) {
            $roomTypeRevenueWeekQuery->whereBetween(DB::raw('DATE(hd.created_at)'), $weekRange);
        }
        $roomTypeRevenueWeek = $roomTypeRevenueWeekQuery
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Room Type Revenue Month (or filtered date range)
        $roomTypeRevenueMonthQuery = $buildRoomTypeRevenueQuery();
        if (!$hasDateFilter) {
            $roomTypeRevenueMonthQuery->whereYear('hd.created_at', $year)
                ->whereMonth('hd.created_at', $month);
        }
        
        $roomTypeRevenueMonth = $roomTypeRevenueMonthQuery
            ->select(
                'lp.id',
                'lp.ten',
                DB::raw('SUM(hdi.amount) as revenue'),
                DB::raw('COUNT(DISTINCT hd.dat_phong_id) as booking_count')
            )
            ->groupBy('lp.id', 'lp.ten')
            ->orderByDesc('revenue')
            ->get();

        // Calculate monthly revenue for percentage
        if ($hasDateFilter) {
            $monthlyRevenue = HoaDon::whereBetween(DB::raw('DATE(created_at)'), [$filterStartDate, $filterEndDate])
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
        } else {
            $monthlyRevenue = HoaDon::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereNotIn('trang_thai', ['da_huy'])
                ->sum('tong_thuc_thu');
        }

        return response()->json([
            'success' => true,
            'roomTypeRevenueToday' => $roomTypeRevenueToday,
            'roomTypeRevenueWeek' => $roomTypeRevenueWeek,
            'roomTypeRevenueMonth' => $roomTypeRevenueMonth,
            'monthlyRevenue' => $monthlyRevenue
        ]);
    }
}
