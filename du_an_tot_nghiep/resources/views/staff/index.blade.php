@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
            </h2>
            <p class="text-muted mb-0">Tổng quan hoạt động khách sạn</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <small class="text-muted d-block">Cập nhật lần cuối</small>
                <strong class="text-primary">{{ now()->format('d/m/Y H:i') }}</strong>
            </div>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Làm mới
            </button>
        </div>
    </div>

    {{-- CÁC CHỈ SỐ KPI --}}
    <div class="row g-3 mb-4">
        {{-- 1. Số phòng trống --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-house-door-fill fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Số phòng trống</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($availableRooms ?? 0) }}</h2>
                    <small class="text-white-50">Lễ tân xem để bố trí khách</small>
                </div>
            </div>
        </div>

        {{-- 2. Số phòng đang có khách --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Phòng đang có khách</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($soPhongDangCoKhach ?? 0) }}</h2>
                    <small class="text-white-50">Theo dõi phòng đang sử dụng</small>
                </div>
            </div>
        </div>

        {{-- 3. Số phòng chờ dọn / bảo trì --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-tools fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Chờ dọn / Bảo trì</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($soPhongChoDonBaoTri ?? 0) }}</h2>
                    <small class="text-white-50">Quản lý dọn phòng</small>
                </div>
            </div>
        </div>

        {{-- 4. Số đặt phòng hôm nay --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-calendar-check-fill fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Đặt phòng hôm nay</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($soDatPhongHomNay ?? 0) }}</h2>
                    <small class="text-white-50">Xem khách đến trong ngày</small>
                </div>
            </div>
        </div>

        {{-- 5. Tổng doanh thu hôm nay --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu hôm nay</h6>
                    <h2 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ number_format($todayRevenue ?? 0, 0) }}đ</h2>
                    <small class="text-white-50">Theo dõi thu nhập</small>
                </div>
            </div>
        </div>

        {{-- 6. Doanh thu tháng này --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu tháng này</h6>
                    <h2 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ number_format($monthlyRevenue ?? 0, 0) }}đ</h2>
                    <small class="text-white-50">Phục vụ quản lý</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Phần Doanh Thu với Tabs --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm chart-card">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2 text-primary"></i>Thống Kê Doanh Thu
                        </h5>
                        <ul class="nav nav-pills" id="revenueTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="today-tab" data-bs-toggle="pill" data-bs-target="#today" type="button" role="tab">
                                    <i class="bi bi-calendar-day me-1"></i>Hôm Nay
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="week-tab" data-bs-toggle="pill" data-bs-target="#week" type="button" role="tab">
                                    <i class="bi bi-calendar-week me-1"></i>Tuần Này
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="month-tab" data-bs-toggle="pill" data-bs-target="#month" type="button" role="tab">
                                    <i class="bi bi-calendar-month me-1"></i>Tháng Này
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="roomtype-tab" data-bs-toggle="pill" data-bs-target="#roomtype" type="button" role="tab">
                                    <i class="bi bi-house-door me-1"></i>Theo Loại Phòng
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="revenueTabContent">
                        {{-- Tab Hôm Nay --}}
                        <div class="tab-pane fade show active" id="today" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Doanh thu hôm nay ({{ now()->format('d/m/Y') }})</h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-grow-1">
                                                <div class="display-4 fw-bold text-primary mb-0">
                                                    {{ number_format($todayRevenue ?? 0, 0) }}<small class="fs-6">đ</small>
                                                </div>
                                                <small class="text-muted">
                                                    @if($todayRefund > 0)
                                                        <span class="text-warning">Hoàn: {{ number_format($todayRefund, 0) }}đ</span> | 
                                                    @endif
                                                    <span class="text-success">Net: {{ number_format($todayNetRevenue ?? 0, 0) }}đ</span>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge bg-success-subtle text-success fs-6 px-3 py-2">
                                                    <i class="bi bi-arrow-up me-1"></i>Hôm nay
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-info mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Lưu ý:</strong> Doanh thu hôm nay bao gồm tất cả giao dịch và hóa đơn được tạo trong ngày {{ now()->format('d/m/Y') }}
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-3">Chi tiết</h6>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small">Giao dịch thanh toán</span>
                                                <strong class="text-success">{{ number_format($todayPaid ?? 0, 0) }}đ</strong>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small">Hóa đơn xuất</span>
                                                <strong class="text-info">{{ number_format($todayInvoiced ?? 0, 0) }}đ</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">Tổng doanh thu</span>
                                                <strong class="text-primary fs-5">{{ number_format($todayRevenue ?? 0, 0) }}đ</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Tuần Này --}}
                        <div class="tab-pane fade" id="week" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Doanh thu tuần này ({{ \Carbon\Carbon::now()->startOfWeek()->format('d/m') }} - {{ \Carbon\Carbon::now()->endOfWeek()->format('d/m/Y') }})</h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-grow-1">
                                                <div class="display-4 fw-bold text-primary mb-0">
                                                    {{ number_format($weeklyRevenue ?? 0, 0) }}<small class="fs-6">đ</small>
                                                </div>
                                                <small class="text-muted">
                                                    @if($weeklyRefund > 0)
                                                        <span class="text-warning">Hoàn: {{ number_format($weeklyRefund, 0) }}đ</span> | 
                                                    @endif
                                                    <span class="text-success">Net: {{ number_format($weeklyNetRevenue ?? 0, 0) }}đ</span>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge bg-info-subtle text-info fs-6 px-3 py-2">
                                                    <i class="bi bi-calendar-week me-1"></i>Tuần này
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <canvas id="weekRevenueChart" style="max-height: 300px;"></canvas>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-3">Doanh thu theo ngày</h6>
                                            <div style="max-height: 300px; overflow-y: auto;">
                                                @php
                                                    $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
                                                @endphp
                                                @for($i = 0; $i < 7; $i++)
                                                    @php
                                                        $date = $startOfWeek->copy()->addDays($i);
                                                        $dayPaid = \App\Models\GiaoDich::where('trang_thai', 'thanh_cong')
                                                            ->whereDate('created_at', $date->toDateString())
                                                            ->sum('so_tien');
                                                        $dayInvoiced = \App\Models\HoaDon::whereDate('created_at', $date->toDateString())
                                                            ->whereNotIn('trang_thai', ['da_huy'])
                                                            ->sum('tong_thuc_thu');
                                                        $dayRevenue = $dayPaid + $dayInvoiced;
                                                    @endphp
                                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                        <div>
                                                            <div class="small fw-semibold">{{ $date->format('d/m') }}</div>
                                                            <div class="small text-muted">
                                                                @php
                                                                    $dayNames = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
                                                                    $dayIndex = $date->dayOfWeek;
                                                                @endphp
                                                                {{ $dayNames[$dayIndex] }}
                                                            </div>
                                                        </div>
                                                        <strong class="text-success">{{ number_format($dayRevenue, 0) }}đ</strong>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Tháng Này --}}
                        <div class="tab-pane fade" id="month" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Doanh thu tháng này (Tháng {{ now()->month }}/{{ now()->year }})</h6>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-grow-1">
                                                <div class="display-4 fw-bold text-primary mb-0">
                                                    {{ number_format($monthlyRevenue ?? 0, 0) }}<small class="fs-6">đ</small>
                                                </div>
                                                <small class="text-muted">
                                                    @if($monthlyRefund > 0)
                                                        <span class="text-warning">Hoàn: {{ number_format($monthlyRefund, 0) }}đ</span> | 
                                                    @endif
                                                    <span class="text-success">Net: {{ number_format($monthlyNetRevenue ?? 0, 0) }}đ</span>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge bg-warning-subtle text-warning fs-6 px-3 py-2">
                                                    <i class="bi bi-calendar-month me-1"></i>Tháng này
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <canvas id="monthRevenueChart" style="max-height: 300px;"></canvas>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-3">Tóm tắt</h6>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small">Hôm nay</span>
                                                    <strong class="text-success">{{ number_format($todayRevenue ?? 0, 0) }}đ</strong>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small">Tuần này</span>
                                                    <strong class="text-info">{{ number_format($weeklyRevenue ?? 0, 0) }}đ</strong>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small">Tháng này</span>
                                                    <strong class="text-primary">{{ number_format($monthlyRevenue ?? 0, 0) }}đ</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">Tổng cộng</span>
                                                    <strong class="text-primary fs-5">{{ number_format($totalRevenue ?? 0, 0) }}đ</strong>
                                                </div>
                                            </div>
                                            <div class="alert alert-success mb-0">
                                                <small>
                                                    <i class="bi bi-info-circle me-1"></i>
                                                    Trung bình mỗi ngày: <strong>{{ number_format(($monthlyRevenue ?? 0) / now()->day, 0) }}đ</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Theo Loại Phòng --}}
                        <div class="tab-pane fade" id="roomtype" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Doanh thu theo loại phòng - Tháng này</h6>
                                        <canvas id="roomTypeRevenueChart" style="max-height: 300px;"></canvas>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="text-muted mb-3">Tổng doanh thu theo loại</h6>
                                            <div style="max-height: 300px; overflow-y: auto;">
                                                @forelse($roomTypeRevenueMonth ?? [] as $roomType)
                                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                        <div>
                                                            <div class="fw-semibold text-dark">{{ $roomType->ten }}</div>
                                                            <small class="text-muted">{{ $roomType->booking_count ?? 0 }} đơn</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <strong class="text-success d-block">{{ number_format($roomType->revenue ?? 0, 0) }}đ</strong>
                                                            <small class="text-muted">
                                                                {{ $monthlyRevenue > 0 ? number_format((($roomType->revenue ?? 0) / $monthlyRevenue) * 100, 1) : 0 }}%
                                                            </small>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center text-muted py-4 small">
                                                        <i class="bi bi-inbox"></i> Không có dữ liệu
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Bảng chi tiết --}}
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="ps-3 py-2">Loại phòng</th>
                                                            <th class="text-center py-2">Hôm nay</th>
                                                            <th class="text-center py-2">Tuần này</th>
                                                            <th class="text-center py-2">Tháng này</th>
                                                            <th class="text-end pe-3 py-2">Số đơn</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $allRoomTypes = collect();
                                                            if (!empty($roomTypeRevenueToday)) $allRoomTypes = $allRoomTypes->merge($roomTypeRevenueToday);
                                                            if (!empty($roomTypeRevenueWeek)) $allRoomTypes = $allRoomTypes->merge($roomTypeRevenueWeek);
                                                            if (!empty($roomTypeRevenueMonth)) $allRoomTypes = $allRoomTypes->merge($roomTypeRevenueMonth);
                                                            $allRoomTypes = $allRoomTypes->unique('id');
                                                        @endphp
                                                        @forelse($allRoomTypes as $roomType)
                                                            @php
                                                                $todayRev = $roomTypeRevenueToday->firstWhere('id', $roomType->id)->revenue ?? 0;
                                                                $weekRev = $roomTypeRevenueWeek->firstWhere('id', $roomType->id)->revenue ?? 0;
                                                                $monthRev = $roomTypeRevenueMonth->firstWhere('id', $roomType->id)->revenue ?? 0;
                                                                $bookingCount = $roomTypeRevenueMonth->firstWhere('id', $roomType->id)->booking_count ?? 0;
                                                            @endphp
                                                            <tr>
                                                                <td class="ps-3 fw-semibold">{{ $roomType->ten }}</td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-success-subtle text-success">
                                                                        {{ number_format($todayRev, 0) }}đ
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-info-subtle text-info">
                                                                        {{ number_format($weekRev, 0) }}đ
                                                                    </span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge bg-primary-subtle text-primary">
                                                                        {{ number_format($monthRev, 0) }}đ
                                                                    </span>
                                                                </td>
                                                                <td class="text-end pe-3">
                                                                    <strong>{{ $bookingCount }}</strong>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted py-4">
                                                                    <i class="bi bi-inbox"></i> Không có dữ liệu
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hoạt động hôm nay và lịch --}}
    <div class="row g-4 mb-4">
        {{-- Check-in/Check-out hôm nay --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-calendar-check me-2"></i>Hoạt Động Hôm Nay
                    </h5>
                    <span class="badge bg-light text-primary">{{ now()->format('d/m/Y') }}</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        {{-- Check-ins Today --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold text-success">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Check-in
                                </h6>
                                <span class="badge bg-success rounded-pill">{{ $todayCheckins->count() }}</span>
                            </div>
                            <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                @forelse($todayCheckins as $booking)
                                    <a href="{{ route('staff.bookings.show', $booking->id) }}" 
                                       class="list-group-item list-group-item-action px-3 py-2 border-0 bg-light mb-2 rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small text-dark">{{ $booking->ma_tham_chieu }}</div>
                                                <div class="small text-muted">
                                                    @if($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-open"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.ma_phong')->filter()->join(', ') ?: 'Chưa gán' }}
                                                    @else
                                                        Chưa gán phòng
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if($booking->checked_in_at)
                                                    <span class="badge bg-success">✓</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">⏳</span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-center text-muted py-4 small">
                                        <i class="bi bi-inbox"></i> Không có check-in hôm nay
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Check-outs Today --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 fw-semibold text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i>Check-out
                                </h6>
                                <span class="badge bg-danger rounded-pill">{{ $todayCheckouts->count() }}</span>
                            </div>
                            <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                @forelse($todayCheckouts as $booking)
                                    <a href="{{ route('staff.bookings.show', $booking->id) }}" 
                                       class="list-group-item list-group-item-action px-3 py-2 border-0 bg-light mb-2 rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small text-dark">{{ $booking->ma_tham_chieu }}</div>
                                                <div class="small text-muted">
                                                    @if($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-closed"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.ma_phong')->filter()->join(', ') ?: 'N/A' }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if($booking->checkout_at)
                                                    <span class="badge bg-success">✓</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">⏳</span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="text-center text-muted py-4 small">
                                        <i class="bi bi-inbox"></i> Không có check-out hôm nay
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hoạt động gần đây --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Hoạt Động Gần Đây
                    </h5>
                </div>
                <div class="card-body p-0 overflow-auto" style="max-height: 400px;">
                    <div class="list-group list-group-flush">
                        @forelse ($recentActivities as $activity)
                            <a href="{{ route('staff.bookings.show', $activity->id) }}" 
                               class="list-group-item list-group-item-action px-3 py-3 border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="fw-semibold small d-block text-dark">{{ $activity->ma_tham_chieu }}</span>
                                        <span class="badge 
                                            @if($activity->trang_thai == 'dang_su_dung') bg-success
                                            @elseif($activity->trang_thai == 'dang_cho') bg-warning text-dark
                                            @elseif($activity->trang_thai == 'da_xac_nhan') bg-info
                                            @elseif($activity->trang_thai == 'da_huy') bg-danger
                                            @else bg-secondary
                                            @endif fs-7 px-2 py-1 mt-1">
                                            {{ Str::ucfirst(str_replace('_', ' ', $activity->trang_thai)) }}
                                        </span>
                                    </div>
                                    <small class="text-muted text-nowrap">{{ $activity->updated_at->format('H:i') }}</small>
                                </div>
                            </a>
                        @empty
                            <div class="text-center text-muted py-4 small">Không có hoạt động nào.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lịch đặt phòng --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-calendar3 me-2"></i>Lịch Đặt Phòng
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        <span class="badge bg-success">
                            <i class="bi bi-circle-fill"></i> Đang sử dụng
                        </span>
                        <span class="badge bg-primary">
                            <i class="bi bi-circle-fill"></i> Đã xác nhận
                        </span>
                        <span class="badge bg-info">
                            <i class="bi bi-circle-fill"></i> Đã gán phòng
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-3">
            <div id="calendar" style="min-height: 500px;"></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

<script>
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

let weekRevenueChart = null;
let monthRevenueChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Biểu đồ doanh thu tuần này
    const weekLabels = @json($weekChartLabels ?? []);
    const weekData = @json($weekRevenueData ?? []);
    const weekCtx = document.getElementById('weekRevenueChart');
    
    if (weekCtx && weekLabels.length && weekData.length) {
        weekRevenueChart = new Chart(weekCtx, {
            type: 'line',
            data: {
                labels: weekLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: weekData,
                    borderColor: 'rgb(79, 172, 254)',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(79, 172, 254)',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 13, weight: '600' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Biểu đồ doanh thu tháng này
    const monthLabels = @json($monthChartLabels ?? []);
    const monthData = @json($monthRevenueData ?? []);
    const monthCtx = document.getElementById('monthRevenueChart');
    
    if (monthCtx && monthLabels.length && monthData.length) {
        monthRevenueChart = new Chart(monthCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: monthData,
                    borderColor: 'rgb(79, 172, 254)',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(79, 172, 254)',
                    pointBorderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 13, weight: '600' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Biểu đồ doanh thu theo loại phòng
    const roomTypeLabels = @json($roomTypeRevenueMonth->pluck('ten')->toArray() ?? []);
    const roomTypeRevenueData = @json($roomTypeRevenueMonth->pluck('revenue')->toArray() ?? []);
    const roomTypeCtx = document.getElementById('roomTypeRevenueChart');
    
    if (roomTypeCtx && roomTypeLabels.length && roomTypeRevenueData.length) {
        new Chart(roomTypeCtx, {
            type: 'bar',
            data: {
                labels: roomTypeLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: roomTypeRevenueData,
                    backgroundColor: [
                        'rgba(79, 172, 254, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(102, 126, 234, 0.7)'
                    ],
                    borderColor: [
                        'rgba(79, 172, 254, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(13, 110, 253, 1)',
                        'rgba(102, 126, 234, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            font: { size: 11 },
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Xử lý khi chuyển tab
    const tabButtons = document.querySelectorAll('#revenueTabs button[data-bs-toggle="pill"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (event) {
            // Resize charts khi chuyển tab
            setTimeout(() => {
                if (weekRevenueChart) weekRevenueChart.resize();
                if (monthRevenueChart) monthRevenueChart.resize();
            }, 100);
        });
    });

    // FullCalendar
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const events = @json($events ?? []);
        const processedEvents = events.map(event => {
            let backgroundColor = '#6c757d';
            if (event.description?.includes('dang_su_dung')) {
                backgroundColor = '#198754';
            } else if (event.description?.includes('da_xac_nhan')) {
                backgroundColor = '#0d6efd';
            } else if (event.description?.includes('da_gan_phong')) {
                backgroundColor = '#20c997';
            }
            
            return {
                title: event.title,
                start: event.start,
                end: event.end,
                backgroundColor: backgroundColor,
                borderColor: 'transparent',
                textColor: '#ffffff'
            };
        });
        
        new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng',
                week: 'Tuần',
                day: 'Ngày'
            },
            locale: 'vi',
            events: processedEvents,
            eventTimeFormat: { 
                hour: '2-digit', 
                minute: '2-digit', 
                hour12: false 
            },
            eventDisplay: 'block',
            displayEventTime: false
        }).render();
    }
});
</script>
@endpush

<style>
/* KPI Cards */
.kpi-card {
    border-radius: 12px;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}

.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.kpi-icon-wrapper {
    transition: transform 0.3s ease;
}

.kpi-card:hover .kpi-icon-wrapper {
    transform: scale(1.1);
}

.chart-card {
    border-radius: 12px;
    transition: all 0.3s ease;
}

.chart-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

#calendar {
    background: #fff;
    border-radius: 12px;
}

.fc .fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.fc .fc-button-primary {
    background-color: #667eea;
    border-color: #667eea;
    font-weight: 600;
    border-radius: 8px;
}

.fc .fc-button-primary:hover {
    background-color: #764ba2;
    border-color: #764ba2;
}

.fc .fc-event {
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.fc .fc-event:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Revenue Tabs */
.nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:not(.active) {
    background-color: #f8f9fa;
    color: #6c757d;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.nav-pills .nav-link:hover:not(.active) {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

/* Display revenue */
.display-4 {
    font-size: 2.5rem;
    line-height: 1.2;
}

@media (max-width: 768px) {
    .kpi-card .card-body h2 {
        font-size: 1.3rem;
    }
    
    .kpi-card .card-body h6 {
        font-size: 0.7rem;
    }
    
    .display-4 {
        font-size: 1.8rem;
    }
    
    .nav-pills {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nav-pills .nav-link {
        width: 100%;
    }
}
</style>
@endsection
