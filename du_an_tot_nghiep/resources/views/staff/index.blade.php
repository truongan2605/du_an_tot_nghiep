@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid px-4 py-4">
        {{-- Header --}}
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="h3 mb-1 fw-bold text-dark">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                </h2>
                <p class="text-muted mb-0">Tổng quan hoạt động khách sạn</p>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
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
            <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-house-door-fill fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Số phòng trống</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 2rem; line-height: 1.2;">{{ number_format($availableRooms ?? 0) }}</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Lễ tân xem để bố trí khách</small>
                    </div>
                </div>
            </div>

            {{-- <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-people-fill fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Phòng đang có khách</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 2rem; line-height: 1.2;">{{ number_format($soPhongDangCoKhach ?? 0) }}</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Theo dõi phòng đang sử dụng</small>
                    </div>
                </div>
            </div> --}}

            {{-- <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-tools fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Chờ dọn / Bảo trì</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 2rem; line-height: 1.2;">{{ number_format($soPhongChoDonBaoTri ?? 0) }}</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Quản lý dọn phòng</small>
                    </div>
                </div>
            </div> --}}

            <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-calendar-check-fill fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Đặt phòng hôm nay</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 2rem; line-height: 1.2;">{{ number_format($soDatPhongHomNay ?? 0) }}</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Xem khách đến trong ngày</small>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-cash-coin fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Doanh thu hôm nay</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 1.5rem; line-height: 1.2; word-break: break-word;">{{ number_format($todayRevenue ?? 0, 0, '.', '.') }}đ</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Theo dõi thu nhập</small>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4 col-xl-2">
                <div class="card kpi-card border-0 shadow-lg h-100 kpi-card-hover"
                    style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); transition: all 0.3s ease;">
                    <div class="card-body text-white p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-25 rounded-circle p-3 shadow-sm"
                                style="transition: transform 0.3s ease;">
                                <i class="bi bi-graph-up-arrow fs-3"></i>
                            </div>
                        </div>
                        <h6 class="mb-2 text-white-75 small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Doanh thu tháng này</h6>
                        <h2 class="mb-2 fw-bold" style="font-size: 1.5rem; line-height: 1.2; word-break: break-word;">{{ number_format($monthlyRevenue ?? 0, 0, '.', '.') }}đ</h2>
                        <small class="text-white-50 d-block" style="font-size: 0.75rem;">Phục vụ quản lý</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4" style="margin-left: 0; margin-right: 0;">
            <div class="col-12">
                <div class="card border-0 shadow-sm chart-card" style="overflow: hidden;">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <h5 class="mb-0 fw-bold">
                                    <i class="bi bi-graph-up me-2 text-primary"></i>Thống Kê Doanh Thu
                                </h5>
                                {{-- Form lọc đơn giản --}}
                                <form method="GET" action="{{ route('staff.index') }}"
                                    class="d-flex flex-column flex-sm-row gap-2 align-items-end"
                                    id="revenueFilterForm" onsubmit="return validateDateRange()">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <div>
                                            <label class="form-label small text-muted mb-1">Từ ngày</label>
                                            <input type="date" name="start_date" id="start_date"
                                                value="{{ request('start_date') }}" 
                                                class="form-control form-control-sm"
                                                style="min-width: 150px;" 
                                                max="{{ now()->format('Y-m-d') }}">
                                        </div>
                                        <div>
                                            <label class="form-label small text-muted mb-1">Đến ngày</label>
                                            <input type="date" name="end_date" id="end_date"
                                                value="{{ request('end_date') }}" 
                                                class="form-control form-control-sm"
                                                style="min-width: 150px;" 
                                                max="{{ now()->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-funnel me-1"></i>Lọc
                                        </button>
                                        @if(request()->has('start_date') || request()->has('end_date'))
                                            <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-x-circle me-1"></i>Xóa
                                            </a>
                                        @endif
                                    </div>
                                </form>
                            </div>
                            <ul class="nav nav-pills flex-wrap mb-0" id="revenueTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ !request()->has('start_date') ? 'active' : '' }}"
                                        id="today-tab" data-bs-toggle="pill" data-bs-target="#today" type="button"
                                        role="tab">
                                        <i class="bi bi-calendar-day me-1"></i>Hôm Nay
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="week-tab" data-bs-toggle="pill" data-bs-target="#week"
                                        type="button" role="tab">
                                        <i class="bi bi-calendar-week me-1"></i>Tuần Này
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="month-tab" data-bs-toggle="pill"
                                        data-bs-target="#month" type="button" role="tab">
                                        <i class="bi bi-calendar-month me-1"></i>Tháng Này
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="roomtype-tab" data-bs-toggle="pill"
                                        data-bs-target="#roomtype" type="button" role="tab">
                                        <i class="bi bi-house-door me-1"></i>Theo Loại Phòng
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="room-tab" data-bs-toggle="pill" data-bs-target="#room"
                                        type="button" role="tab">
                                        <i class="bi bi-door-open me-1"></i>Theo Phòng
                                    </button>
                                </li>
                                @if (request()->has('start_date') && request()->has('end_date'))
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="custom-tab" data-bs-toggle="pill"
                                            data-bs-target="#custom" type="button" role="tab">
                                            <i class="bi bi-calendar-range me-1"></i>Tùy Chỉnh
                                        </button>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-3" style="overflow-x: hidden;">
                        <div class="tab-content" id="revenueTabContent" style="width: 100%; max-width: 100%;">
                            {{-- Tab Hôm Nay --}}
                            <div class="tab-pane fade {{ !request()->has('start_date') ? 'show active' : '' }}"
                                id="today" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="mb-2">
                                            <h6 class="text-muted mb-2">Doanh thu hôm nay ({{ now()->format('d/m/Y') }})
                                            </h6>
                                            <div
                                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                                                <div class="flex-grow-1 w-100">
                                                    <div class="display-4 fw-bold text-primary mb-0"
                                                        style="word-break: break-word;">
                                                        {{ number_format($todayRevenue ?? 0, 0, '.', '.') }}<small
                                                            class="fs-6">đ</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if ($todayRefund > 0)
                                                            <span class="text-warning">Hoàn:
                                                                {{ number_format($todayRefund, 0, '.', '.') }}đ</span> |
                                                        @endif
                                                        <span class="text-success">Net:
                                                            {{ number_format($todayNetRevenue ?? 0, 0, '.', '.') }}đ</span>
                                                    </small>
                                                </div>
                                                <div class="text-start text-sm-end">
                                                    <div class="badge bg-success-subtle text-success fs-6 px-3 py-2">
                                                        <i class="bi bi-arrow-up me-1"></i>Hôm nay
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mb-0">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Lưu ý:</strong> Doanh thu hôm nay bao gồm tất cả giao dịch và hóa đơn
                                            được tạo trong ngày {{ now()->format('d/m/Y') }}
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Chi tiết</h6>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small">Giao dịch thanh toán</span>
                                                    <strong
                                                        class="text-success">{{ number_format($todayPaid ?? 0, 0, '.', '.') }}đ</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold">Lợi nhuận</span>
                                                    <strong
                                                        class="text-success fs-5">{{ number_format($todayNetRevenue ?? 0, 0, '.', '.') }}đ</strong>
                                                </div>
                                                @if ($todayRefund > 0)
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Đã trừ hoàn tiền: {{ number_format($todayRefund, 0, '.', '.') }}đ
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab Tuần Này --}}
                            <div class="tab-pane fade" id="week" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="mb-2">
                                            <h6 class="text-muted mb-2">Doanh thu tuần này
                                                ({{ \Carbon\Carbon::now()->startOfWeek()->format('d/m') }} -
                                                {{ \Carbon\Carbon::now()->endOfWeek()->format('d/m/Y') }})</h6>
                                            <div
                                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                                                <div class="flex-grow-1 w-100">
                                                    <div class="display-4 fw-bold text-primary mb-0"
                                                        style="word-break: break-word;">
                                                        {{ number_format($weeklyRevenue ?? 0, 0, '.', '.') }}<small
                                                            class="fs-6">đ</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if ($weeklyRefund > 0)
                                                            <span class="text-warning">Hoàn:
                                                                {{ number_format($weeklyRefund, 0, '.', '.') }}đ</span> |
                                                        @endif
                                                        <span class="text-success">Net:
                                                            {{ number_format($weeklyNetRevenue ?? 0, 0, '.', '.') }}đ</span>
                                                    </small>
                                                </div>
                                                <div class="text-start text-sm-end">
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
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Doanh thu theo ngày</h6>
                                                <div style="max-height: 300px; overflow-y: auto;">
                                                    @php
                                                        $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
                                                    @endphp
                                                    @for ($i = 0; $i < 7; $i++)
                                                        @php
                                                            $date = $startOfWeek->copy()->addDays($i);
                                                            // Doanh thu theo ngày = Chỉ tính từ hóa đơn không bị hủy
                                                            $dayRevenue = \App\Models\HoaDon::whereDate(
                                                                'created_at',
                                                                $date->toDateString(),
                                                            )
                                                                ->whereNotIn('trang_thai', ['da_huy'])
                                                                ->sum('tong_thuc_thu');
                                                        @endphp
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                            <div>
                                                                <div class="small fw-semibold">{{ $date->format('d/m') }}
                                                                </div>
                                                                <div class="small text-muted">
                                                                    @php
                                                                        $dayNames = [
                                                                            'Chủ nhật',
                                                                            'Thứ 2',
                                                                            'Thứ 3',
                                                                            'Thứ 4',
                                                                            'Thứ 5',
                                                                            'Thứ 6',
                                                                            'Thứ 7',
                                                                        ];
                                                                        $dayIndex = $date->dayOfWeek;
                                                                    @endphp
                                                                    {{ $dayNames[$dayIndex] }}
                                                                </div>
                                                            </div>
                                                            <strong
                                                                class="text-success">{{ number_format($dayRevenue, 0, '.', '.') }}đ</strong>
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
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="mb-2">
                                            <h6 class="text-muted mb-2">Doanh thu tháng này (Tháng
                                                {{ now()->month }}/{{ now()->year }})</h6>
                                            <div
                                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                                                <div class="flex-grow-1 w-100">
                                                    <div class="display-4 fw-bold text-primary mb-0"
                                                        style="word-break: break-word;">
                                                        {{ number_format($monthlyRevenue ?? 0, 0, '.', '.') }}<small
                                                            class="fs-6">đ</small>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if ($monthlyRefund > 0)
                                                            <span class="text-warning">Hoàn:
                                                                {{ number_format($monthlyRefund, 0, '.', '.') }}đ</span> |
                                                        @endif
                                                        <span class="text-success">Net:
                                                            {{ number_format($monthlyNetRevenue ?? 0, 0, '.', '.') }}đ</span>
                                                    </small>
                                                </div>
                                                <div class="text-start text-sm-end">
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
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Tóm tắt</h6>
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="small">Hôm nay</span>
                                                        <strong
                                                            class="text-success">{{ number_format($todayRevenue ?? 0, 0, '.', '.') }}đ</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="small">Tuần này</span>
                                                        <strong
                                                            class="text-info">{{ number_format($weeklyRevenue ?? 0, 0, '.', '.') }}đ</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="small">Tháng này</span>
                                                        <strong
                                                            class="text-primary">{{ number_format($monthlyRevenue ?? 0, 0, '.', '.') }}đ</strong>
                                                    </div>
                                                </div>
                                                <div class="alert alert-success mb-0">
                                                    <small>
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Trung bình mỗi ngày:
                                                        <strong>{{ number_format(($monthlyRevenue ?? 0) / now()->day, 0, '.', '.') }}đ</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab Theo Loại Phòng --}}
                            <div class="tab-pane fade" id="roomtype" role="tabpanel" style="padding-bottom: 0;">
                                {{-- Filter Section --}}
                                <div class="card shadow-sm rounded-3 border-0 mb-3">
                                    <div class="card-body p-3">
                                        <form method="GET" action="{{ route('staff.index') }}" id="roomTypeFilterForm" class="row g-2 align-items-end">
                                            <input type="hidden" name="tab" value="roomtype">
                                            
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold mb-1">Từ ngày</label>
                                                <input type="date" name="filter_roomtype_start_date" class="form-control form-control-sm" id="filterRoomTypeStartDate" value="{{ request('filter_roomtype_start_date') }}">
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold mb-1">Đến ngày</label>
                                                <input type="date" name="filter_roomtype_end_date" class="form-control form-control-sm" id="filterRoomTypeEndDate" value="{{ request('filter_roomtype_end_date') }}">
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                                    <i class="bi bi-funnel me-1"></i>Lọc
                                                </button>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <a href="{{ route('staff.index', ['tab' => 'roomtype']) }}" class="btn btn-outline-secondary btn-sm w-100">
                                                    <i class="bi bi-x-circle me-1"></i>Xóa
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <div class="mb-2">
                                            <h6 class="text-muted mb-2">Doanh thu theo loại phòng - Tháng này</h6>
                                            <canvas id="roomTypeRevenueChart" style="max-height: 300px;"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Tổng doanh thu theo loại</h6>
                                                <div style="max-height: 300px; overflow-y: auto;">
                                                    @forelse(collect($roomTypeRevenueMonth ?? []) as $roomType)
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                            <div>
                                                                <div class="fw-semibold text-dark">
                                                                    {{ $roomType->ten ?? 'N/A' }}</div>
                                                                <small
                                                                    class="text-muted">{{ $roomType->booking_count ?? 0 }}
                                                                    đơn</small>
                                                            </div>
                                                            <div class="text-end">
                                                                <strong
                                                                    class="text-success d-block">{{ number_format($roomType->revenue ?? 0, 0, '.', '.') }}đ</strong>
                                                                <small class="text-muted">
                                                                    {{ $monthlyRevenue > 0 ? number_format((($roomType->revenue ?? 0) / $monthlyRevenue) * 100, 1, '.', '.') : 0 }}%
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
                                <div class="row mt-3 mb-0">
                                    <div class="col-12">
                                        <div class="card border-0 bg-light mb-0" style="margin-bottom: 0 !important;">
                                            <div class="card-body p-0 mb-0" style="padding-bottom: 0 !important;">
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
                                                        <tbody id="roomTypeRevenueTableBody">
                                                            @php
                                                                // Bắt đầu từ tháng này (dữ liệu chính) - đây là nguồn dữ liệu chính cho tab "Tháng này"
                                                                $allRoomTypes = collect($roomTypeRevenueMonth ?? [])->filter(function($item) {
                                                                    return !empty($item->id) && !empty($item->ten);
                                                                });
                                                                
                                                                // Lấy danh sách ID từ tháng này
                                                                $existingIds = $allRoomTypes->pluck('id')->filter()->toArray();
                                                                
                                                                // Merge với tuần này để lấy thêm các loại phòng có thể không có trong tháng
                                                                if (!empty($roomTypeRevenueWeek)) {
                                                                    foreach ($roomTypeRevenueWeek as $weekType) {
                                                                        if (!empty($weekType->id) && !empty($weekType->ten) && !in_array($weekType->id, $existingIds)) {
                                                                            $allRoomTypes->push($weekType);
                                                                            $existingIds[] = $weekType->id;
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Merge với hôm nay để lấy thêm các loại phòng có thể không có trong tháng/tuần
                                                                if (!empty($roomTypeRevenueToday)) {
                                                                    foreach ($roomTypeRevenueToday as $todayType) {
                                                                        if (!empty($todayType->id) && !empty($todayType->ten) && !in_array($todayType->id, $existingIds)) {
                                                                            $allRoomTypes->push($todayType);
                                                                            $existingIds[] = $todayType->id;
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // Sắp xếp theo doanh thu tháng này (giảm dần), nếu không có trong tháng thì sắp xếp theo tuần, rồi hôm nay
                                                                $allRoomTypes = $allRoomTypes->sortByDesc(function($roomType) use ($roomTypeRevenueMonth, $roomTypeRevenueWeek, $roomTypeRevenueToday) {
                                                                    $monthData = $roomTypeRevenueMonth->firstWhere('id', $roomType->id);
                                                                    if ($monthData && isset($monthData->revenue)) {
                                                                        return $monthData->revenue;
                                                                    }
                                                                    $weekData = $roomTypeRevenueWeek->firstWhere('id', $roomType->id);
                                                                    if ($weekData && isset($weekData->revenue)) {
                                                                        return $weekData->revenue;
                                                                    }
                                                                    $todayData = $roomTypeRevenueToday->firstWhere('id', $roomType->id);
                                                                    return $todayData->revenue ?? 0;
                                                                })->values();
                                                            @endphp
                                                            @forelse($allRoomTypes as $roomType)
                                                                @php
                                                                    // Lấy thông tin từ tháng này trước (ưu tiên) để đảm bảo có đầy đủ thông tin
                                                                    $monthData = $roomTypeRevenueMonth->firstWhere('id', $roomType->id);
                                                                    $roomTypeName = $monthData->ten ?? $roomType->ten ?? 'N/A';
                                                                    
                                                                    $todayRev =
                                                                        $roomTypeRevenueToday->firstWhere(
                                                                            'id',
                                                                            $roomType->id,
                                                                        )->revenue ?? 0;
                                                                    $weekRev =
                                                                        $roomTypeRevenueWeek->firstWhere(
                                                                            'id',
                                                                            $roomType->id,
                                                                        )->revenue ?? 0;
                                                                    $monthRev = $monthData->revenue ?? 0;
                                                                    $bookingCount = $monthData->booking_count ?? 0;
                                                                @endphp
                                                                <tr>
                                                                    <td class="ps-3 fw-semibold">{{ $roomTypeName }}</td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-success-subtle text-success">
                                                                            {{ number_format($todayRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-info-subtle text-info">
                                                                            {{ number_format($weekRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-primary-subtle text-primary">
                                                                            {{ number_format($monthRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end pe-3">
                                                                        <strong>{{ $bookingCount }}</strong>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="5"
                                                                        class="text-center text-muted py-4">
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
                            <style>
                                #roomtype .row:last-child {
                                    margin-bottom: 0 !important;
                                }

                                #roomtype .card:last-child {
                                    margin-bottom: 0 !important;
                                }

                                #roomtype {
                                    padding-bottom: 0 !important;
                                }
                            </style>
                            {{-- Tab Theo Phòng --}}
                            <div class="tab-pane fade" id="room" role="tabpanel" aria-labelledby="room-tab">
                                {{-- Filter Section --}}
                                <div class="card shadow-sm rounded-3 border-0 mb-3">
                                    <div class="card-body p-3">
                                        <form method="GET" action="{{ route('staff.index') }}" id="roomFilterForm" class="row g-2 align-items-end">
                                            <input type="hidden" name="tab" value="room">
                                            
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold mb-1">Từ ngày</label>
                                                <input type="date" name="filter_start_date" class="form-control form-control-sm" id="filterStartDate" value="{{ request('filter_start_date') }}">
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold mb-1">Đến ngày</label>
                                                <input type="date" name="filter_end_date" class="form-control form-control-sm" id="filterEndDate" value="{{ request('filter_end_date') }}">
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                                    <i class="bi bi-funnel me-1"></i>Lọc
                                                </button>
                                            </div>
                                            
                                            <div class="col-md-2">
                                                <a href="{{ route('staff.index', ['tab' => 'room']) }}" class="btn btn-outline-secondary btn-sm w-100">
                                                    <i class="bi bi-x-circle me-1"></i>Xóa
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    {{-- Biểu đồ --}}
                                    <div class="col-12">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Doanh thu theo phòng - Tháng này</h6>
                                                <canvas id="roomRevenueChart" style="max-height: 300px;"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tổng quan --}}
                                    <div class="col-lg-4">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body p-3">
                                                <h6 class="text-muted mb-2">Top phòng doanh thu cao</h6>
                                                <div id="topRoomsList" style="max-height: 600px; overflow-y: auto;">
                                                    @php
                                                        // Lọc và sắp xếp các phòng cụ thể (chỉ lấy các phòng có ma_phong hợp lệ, không phải tên loại phòng)
                                                        $topRooms = collect($roomRevenueMonth ?? [])
                                                            ->filter(function($room) {
                                                                // Chỉ lấy các phòng có ma_phong và id hợp lệ
                                                                if (empty($room->id) || empty($room->ma_phong) || empty($room->revenue) || $room->revenue <= 0) {
                                                                    return false;
                                                                }
                                                                
                                                                // Loại bỏ các mục có ma_phong trùng với tên loại phòng (không chứa số hoặc dấu gạch ngang)
                                                                // Mã phòng hợp lệ thường có định dạng như "SPR - 1", "STD-2", "DLX-3", v.v.
                                                                $maPhong = trim($room->ma_phong);
                                                                // Kiểm tra xem ma_phong có chứa số hoặc dấu gạch ngang không (định dạng mã phòng)
                                                                $hasNumber = preg_match('/\d/', $maPhong);
                                                                $hasDash = strpos($maPhong, '-') !== false || strpos($maPhong, '_') !== false;
                                                                
                                                                // Nếu không có số và không có dấu gạch, có thể là tên loại phòng, bỏ qua
                                                                if (!$hasNumber && !$hasDash) {
                                                                    return false;
                                                                }
                                                                
                                                                return true;
                                                            })
                                                            ->sortByDesc('revenue')
                                                            ->take(10);
                                                    @endphp
                                                    @forelse($topRooms as $room)
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                            <div>
                                                                <div class="fw-semibold text-dark">{{ $room->ma_phong ?? 'N/A' }}
                                                                </div>
                                                                <small class="text-muted">{{ $room->loai_phong ?? '' }}</small>
                                                                <br>
                                                                <small class="text-muted">{{ $room->booking_count ?? 0 }}
                                                                    đơn</small>
                                                            </div>
                                                            <div class="text-end">
                                                                <strong
                                                                    class="text-success d-block">{{ number_format($room->revenue ?? 0, 0, '.', '.') }}đ</strong>
                                                                <small class="text-muted">
                                                                    {{ $monthlyRevenue > 0 ? number_format((($room->revenue ?? 0) / $monthlyRevenue) * 100, 1, '.', '.') : 0 }}%
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

                                    {{-- Bảng chi tiết --}}
                                    <div class="col-lg-8">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="ps-3 py-2">Mã phòng</th>
                                                                <th class="py-2">Loại phòng</th>
                                                                <th class="text-center py-2">Hôm nay</th>
                                                                <th class="text-center py-2">Tuần này</th>
                                                                <th class="text-center py-2">Tháng này</th>
                                                                <th class="text-end pe-3 py-2">Số đơn</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="roomRevenueTableBody">
                                                            @php
                                                                $allRooms = collect();
                                                                if (!empty($roomRevenueToday)) {
                                                                    $allRooms = $allRooms->merge($roomRevenueToday);
                                                                }
                                                                if (!empty($roomRevenueWeek)) {
                                                                    $allRooms = $allRooms->merge($roomRevenueWeek);
                                                                }
                                                                if (!empty($roomRevenueMonth)) {
                                                                    $allRooms = $allRooms->merge($roomRevenueMonth);
                                                                }
                                                                $allRooms = $allRooms->unique('id');
                                                            @endphp
                                                            @forelse($allRooms->sortByDesc(function($room) use ($roomRevenueMonth) {
                                                                return $roomRevenueMonth->firstWhere('id', $room->id)->revenue ?? 0;
                                                            }) as $room)
                                                                @php
                                                                    $todayRev =
                                                                        $roomRevenueToday->firstWhere('id', $room->id)
                                                                            ->revenue ?? 0;
                                                                    $weekRev =
                                                                        $roomRevenueWeek->firstWhere('id', $room->id)
                                                                            ->revenue ?? 0;
                                                                    $monthRev =
                                                                        $roomRevenueMonth->firstWhere('id', $room->id)
                                                                            ->revenue ?? 0;
                                                                    $bookingCount =
                                                                        $roomRevenueMonth->firstWhere('id', $room->id)
                                                                            ->booking_count ?? 0;
                                                                    $roomStatus =
                                                                        $roomRevenueMonth->firstWhere('id', $room->id)
                                                                            ->trang_thai ??
                                                                        ($room->trang_thai ?? 'trong');
                                                                @endphp
                                                                <tr>
                                                                    <td class="ps-3">
                                                                        <div class="fw-semibold">{{ $room->ma_phong }}
                                                                        </div>
                                                                        <small
                                                                            class="badge 
                                                                        @if ($roomStatus == 'dang_o') bg-danger
                                                                        @elseif($roomStatus == 'trong') bg-success
                                                                        @elseif($roomStatus == 'bao_tri') bg-warning
                                                                        @else bg-secondary @endif text-white">
                                                                            {{ ucfirst(str_replace('_', ' ', $roomStatus)) }}
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <small
                                                                            class="text-muted">{{ $room->loai_phong ?? 'N/A' }}</small>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-success-subtle text-success">
                                                                            {{ number_format($todayRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-info-subtle text-info">
                                                                            {{ number_format($weekRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-primary-subtle text-primary">
                                                                            {{ number_format($monthRev, 0, '.', '.') }}đ
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end pe-3">
                                                                        <strong>{{ $bookingCount }}</strong>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="6"
                                                                        class="text-center text-muted py-4">
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
                            {{-- Tab Tùy Chỉnh (khi có filter) --}}
                            @if (request()->has('start_date') && request()->has('end_date') && $customRevenue !== null)
                                <div class="tab-pane fade show active" id="custom" role="tabpanel"
                                    aria-labelledby="custom-tab">
                                    <div class="row g-3 mb-3">
                                        {{-- Header với thông tin tổng quan --}}
                                        <div class="col-12">
                                            <div
                                                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2">
                                                <div>
                                                    <h6 class="text-muted mb-1 small">Doanh thu từ {{ $customRangeLabel ?? '' }}
                                                    </h6>
                                                    <div
                                                        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
                                                        <div class="display-4 fw-bold text-primary mb-0"
                                                            style="font-size: clamp(1.5rem, 4vw, 2.5rem); word-break: break-word; line-height: 1.2;">
                                                            {{ number_format($customRevenue ?? 0, 0, '.', '.') }}<small
                                                                class="fs-6">đ</small>
                                                        </div>
                                                        <div class="badge bg-purple-subtle text-purple fs-6 px-3 py-2"
                                                            style="background-color: #e7d2ff; color: #6f42c1; white-space: nowrap;">
                                                            <i class="bi bi-calendar-range me-1"></i>Tùy chỉnh
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        @if ($customRefund > 0)
                                                            <span class="badge bg-warning-subtle text-warning me-2">
                                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Hoàn:
                                                                {{ number_format($customRefund, 0, '.', '.') }}đ
                                                            </span>
                                                        @endif
                                                        <span class="badge bg-success-subtle text-success">
                                                            <i class="bi bi-check-circle me-1"></i>Lợi nhuận:
                                                            {{ number_format($customNetRevenue ?? 0, 0, '.', '.') }}đ
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        {{-- Biểu đồ --}}
                                        <div class="col-lg-8">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-body p-3">
                                                    <h6 class="text-muted mb-3 small fw-semibold">
                                                        <i class="bi bi-graph-up me-1"></i>Biểu đồ doanh thu theo ngày
                                                    </h6>
                                                    @if (!empty($customChartLabels) && count($customChartLabels) > 0)
                                                        <div style="position: relative; height: 300px; max-height: 300px;">
                                                            <canvas id="customRevenueChart"></canvas>
                                                        </div>
                                                    @else
                                                        <div class="alert alert-info mb-0">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            Không có dữ liệu trong khoảng thời gian đã chọn
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Chi tiết --}}
                                        <div class="col-lg-4">
                                            <div class="card bg-light border-0 shadow-sm h-100">
                                                <div class="card-body p-3">
                                                    <h6 class="text-muted mb-3 small fw-semibold">
                                                        <i class="bi bi-list-ul me-1"></i>Chi tiết
                                                    </h6>
                                                    <div
                                                        class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                                        <span class="small text-muted">Giao dịch thanh toán</span>
                                                        <strong
                                                            class="text-success fs-6">{{ number_format($customPaid ?? 0, 0, '.', '.') }}đ</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="fw-semibold">Lợi nhuận</span>
                                                        <strong
                                                            class="text-success fs-5">{{ number_format($customNetRevenue ?? 0, 0, '.', '.') }}đ</strong>
                                                    </div>
                                                    @if ($customRefund > 0)
                                                        <div class="mt-2 pt-2 border-top">
                                                            <small class="text-muted d-flex align-items-center">
                                                                <i class="bi bi-info-circle me-1"></i>
                                                                Đã trừ hoàn tiền: <strong
                                                                    class="text-warning ms-1">{{ number_format($customRefund, 0, '.', '.') }}đ</strong>
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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
                                                    @if ($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-open"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.ma_phong')->filter()->join(', ') ?: 'Chưa gán' }}
                                                    @else
                                                        Chưa gán phòng
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if ($booking->checked_in_at)
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
                                                    @if ($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-closed"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.ma_phong')->filter()->join(', ') ?: 'N/A' }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if ($booking->checkout_at)
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
                                        <span
                                            class="fw-semibold small d-block text-dark">{{ $activity->ma_tham_chieu }}</span>
                                        <span
                                            class="badge 
                                            @if ($activity->trang_thai == 'dang_su_dung') bg-success
                                            @elseif($activity->trang_thai == 'dang_cho') bg-warning text-dark
                                            @elseif($activity->trang_thai == 'da_xac_nhan') bg-info
                                            @elseif($activity->trang_thai == 'da_huy') bg-danger
                                            @else bg-secondary @endif fs-7 px-2 py-1 mt-1">
                                            {{ Str::ucfirst(str_replace('_', ' ', $activity->trang_thai)) }}
                                        </span>
                                    </div>
                                    <small
                                        class="text-muted text-nowrap">{{ $activity->updated_at->format('H:i') }}</small>
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

    {{-- Top khách hàng chi tiêu nhiều nhất --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Top Khách Hàng Chi Tiêu Nhiều Nhất
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Khách hàng</th>
                                    <th class="text-center">Số đơn</th>
                                    <th class="text-end pe-3">Tổng chi tiêu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topCustomers as $index => $customer)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold;">
                                                {{ $index + 1 }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $customer->name ?? 'N/A' }}</div>
                                            <div class="small text-muted">
                                                <i class="bi bi-envelope me-1"></i>{{ $customer->email ?? 'N/A' }}
                                            </div>
                                            @if($customer->so_dien_thoai)
                                                <div class="small text-muted">
                                                    <i class="bi bi-telephone me-1"></i>{{ $customer->so_dien_thoai }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info fs-6 px-3 py-2">
                                                {{ number_format($customer->total_bookings ?? 0) }} đơn
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="fw-bold text-success" style="font-size: 1.1rem;">
                                                {{ number_format($customer->total_spent ?? 0, 0, '.', '.') }}đ
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            <span>Chưa có dữ liệu khách hàng</span>
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
            let customRevenueChart = null;

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
                                        font: {
                                            size: 13,
                                            weight: '600'
                                        }
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    callbacks: {
                                        label: function(context) {
                                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(
                                                context.parsed.y) + ' VNĐ';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
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
                                        font: {
                                            size: 13,
                                            weight: '600'
                                        }
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    callbacks: {
                                        label: function(context) {
                                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(
                                                context.parsed.y) + ' VNĐ';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45,
                                        font: {
                                            size: 10
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
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
                let roomTypeRevenueChart = null;

                if (roomTypeCtx && roomTypeLabels.length && roomTypeRevenueData.length) {
                    roomTypeRevenueChart = new Chart(roomTypeCtx, {
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
                                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(
                                                context.parsed.y) + ' VNĐ';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
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
                    
                    // Store chart instance globally for update
                    window.roomTypeRevenueChart = roomTypeRevenueChart;
                }

                // Biểu đồ doanh thu theo phòng
                const roomLabels = @json($roomRevenueMonth->pluck('ma_phong')->toArray() ?? []);
                const roomRevenueData = @json($roomRevenueMonth->pluck('revenue')->toArray() ?? []);
                const roomCtx = document.getElementById('roomRevenueChart');
                let roomRevenueChart = null;

                if (roomCtx && roomLabels.length && roomRevenueData.length) {
                    roomRevenueChart = new Chart(roomCtx, {
                        type: 'bar',
                        data: {
                            labels: roomLabels,
                            datasets: [{
                                label: 'Doanh thu (VNĐ)',
                                data: roomRevenueData,
                                backgroundColor: [
                                    'rgba(79, 172, 254, 0.7)',
                                    'rgba(25, 135, 84, 0.7)',
                                    'rgba(255, 193, 7, 0.7)',
                                    'rgba(220, 53, 69, 0.7)',
                                    'rgba(108, 117, 125, 0.7)',
                                    'rgba(13, 110, 253, 0.7)',
                                    'rgba(102, 126, 234, 0.7)',
                                    'rgba(255, 99, 132, 0.7)',
                                    'rgba(54, 162, 235, 0.7)',
                                    'rgba(255, 206, 86, 0.7)'
                                ],
                                borderColor: [
                                    'rgba(79, 172, 254, 1)',
                                    'rgba(25, 135, 84, 1)',
                                    'rgba(255, 193, 7, 1)',
                                    'rgba(220, 53, 69, 1)',
                                    'rgba(108, 117, 125, 1)',
                                    'rgba(13, 110, 253, 1)',
                                    'rgba(102, 126, 234, 1)',
                                    'rgba(255, 99, 132, 1)',
                                    'rgba(54, 162, 235, 1)',
                                    'rgba(255, 206, 86, 1)'
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
                                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(
                                                context.parsed.y) + ' VNĐ';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    ticks: {
                                        font: {
                                            size: 11
                                        },
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
                    
                    // Store chart instance globally for update
                    window.roomRevenueChart = roomRevenueChart;
                }

                // Biểu đồ doanh thu tùy chỉnh (khi có filter)
                @if (request()->has('start_date') && request()->has('end_date') && !empty($customChartLabels))
                    const customLabels = @json($customChartLabels ?? []);
                    const customData = @json($customChartData ?? []);
                    const customCtx = document.getElementById('customRevenueChart');

                    if (customCtx && customLabels.length && customData.length) {
                        customRevenueChart = new Chart(customCtx, {
                            type: 'line',
                            data: {
                                labels: customLabels,
                                datasets: [{
                                    label: 'Doanh thu (VNĐ)',
                                    data: customData,
                                    borderColor: 'rgb(111, 66, 193)',
                                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: 'rgb(111, 66, 193)',
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
                                            font: {
                                                size: 13,
                                                weight: '600'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        callbacks: {
                                            label: function(context) {
                                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN')
                                                    .format(context.parsed.y) + ' VNĐ';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        },
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
                @endif

                // Đảm bảo tab được hiển thị đúng khi trang load/quay lại
                function ensureActiveTabIsVisible() {
                    const activeTabButton = document.querySelector('#revenueTabs button[data-bs-toggle="pill"].active');
                    if (activeTabButton) {
                        const targetId = activeTabButton.getAttribute('data-bs-target');
                        if (targetId) {
                            const targetPane = document.querySelector(targetId);
                            if (targetPane) {
                                // Đảm bảo tab-pane có class show active
                                targetPane.classList.add('show', 'active');
                                // Ẩn các tab-pane khác
                                document.querySelectorAll('#revenueTabContent .tab-pane').forEach(pane => {
                                    if (pane.id !== targetId.replace('#', '')) {
                                        pane.classList.remove('show', 'active');
                                    }
                                });
                                
                                // Sử dụng Bootstrap Tab API để show tab (nếu có)
                                try {
                                    const tab = new bootstrap.Tab(activeTabButton);
                                    if (!targetPane.classList.contains('show')) {
                                        tab.show();
                                    }
                                } catch (e) {
                                    // Nếu Bootstrap chưa load, chỉ cần class là đủ
                                }
                            }
                        }
                    } else {
                        // Nếu không có tab nào active, active tab mặc định
                        const defaultTab = document.querySelector('#today-tab');
                        const defaultPane = document.querySelector('#today');
                        if (defaultTab && defaultPane) {
                            defaultTab.classList.add('active');
                            defaultPane.classList.add('show', 'active');
                            
                            // Sử dụng Bootstrap Tab API
                            try {
                                const tab = new bootstrap.Tab(defaultTab);
                                tab.show();
                            } catch (e) {
                                // Nếu Bootstrap chưa load, chỉ cần class là đủ
                            }
                        }
                    }
                }

                // Chạy ngay khi DOM ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', ensureActiveTabIsVisible);
                } else {
                    ensureActiveTabIsVisible();
                }

                // Chạy lại khi trang được hiển thị (khi quay lại từ trang khác)
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        setTimeout(ensureActiveTabIsVisible, 100);
                    }
                });

                // Xử lý khi quay lại trang từ cache (back/forward navigation)
                window.addEventListener('pageshow', function(event) {
                    // Nếu trang được load từ cache (back/forward)
                    if (event.persisted) {
                        setTimeout(ensureActiveTabIsVisible, 100);
                    }
                });

                // Xử lý khi cửa sổ được focus lại (khi quay lại tab)
                window.addEventListener('focus', function() {
                    setTimeout(ensureActiveTabIsVisible, 100);
                });

                // Xử lý khi chuyển tab
                const tabButtons = document.querySelectorAll('#revenueTabs button[data-bs-toggle="pill"]');
                tabButtons.forEach(button => {
                    button.addEventListener('shown.bs.tab', function(event) {
                        // Resize charts khi chuyển tab
                        setTimeout(() => {
                            if (weekRevenueChart) weekRevenueChart.resize();
                            if (monthRevenueChart) monthRevenueChart.resize();
                            if (roomTypeRevenueChart) roomTypeRevenueChart.resize();
                            if (roomRevenueChart) roomRevenueChart.resize();
                            if (customRevenueChart) customRevenueChart.resize();
                        }, 100);
                    });
                });

                // === Room Filter Functionality (Date Range) ===
                const filterStartDate = document.getElementById('filterStartDate');
                const filterEndDate = document.getElementById('filterEndDate');
                const roomFilterForm = document.getElementById('roomFilterForm');
                
                if (roomFilterForm) {
                    // Validate date range
                    function validateDateRange() {
                        if (filterStartDate && filterEndDate && filterStartDate.value && filterEndDate.value) {
                            if (new Date(filterStartDate.value) > new Date(filterEndDate.value)) {
                                alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc');
                                return false;
                            }
                        }
                        return true;
                    }
                    
                    // When form is submitted, load data via AJAX instead of page reload
                    roomFilterForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Validate date range
                        if (!validateDateRange()) {
                            return;
                        }
                        
                        // Show loading state
                        const submitBtn = this.querySelector('button[type="submit"]');
                        const originalBtnText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lọc...';
                        
                        // Get form data
                        const formData = new FormData(this);
                        const params = new URLSearchParams();
                        for (const [key, value] of formData.entries()) {
                            if (key !== 'tab' && value) {
                                params.append(key, value);
                            }
                        }
                        
                        // Add month and year from current page
                        const currentMonth = {{ $month ?? now()->month }};
                        const currentYear = {{ $year ?? now()->year }};
                        params.append('month', currentMonth);
                        params.append('year', currentYear);
                        
                        // Fetch filtered data
                        fetch(`{{ route('staff.api.room-revenue-filter') }}?${params.toString()}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update chart
                                    updateRoomRevenueChart(data.roomRevenueMonth);
                                    
                                    // Update top rooms list
                                    updateTopRoomsList(data.roomRevenueMonth, data.monthlyRevenue);
                                    
                                    // Update table
                                    updateRoomRevenueTable(data.roomRevenueToday, data.roomRevenueWeek, data.roomRevenueMonth);
                                    
                                    // Update URL without reload
                                    const newUrl = new URL(window.location);
                                    if (formData.get('filter_start_date')) {
                                        newUrl.searchParams.set('filter_start_date', formData.get('filter_start_date'));
                                    } else {
                                        newUrl.searchParams.delete('filter_start_date');
                                    }
                                    if (formData.get('filter_end_date')) {
                                        newUrl.searchParams.set('filter_end_date', formData.get('filter_end_date'));
                                    } else {
                                        newUrl.searchParams.delete('filter_end_date');
                                    }
                                    window.history.pushState({}, '', newUrl);
                                } else {
                                    alert('Có lỗi xảy ra khi lọc dữ liệu');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Có lỗi xảy ra khi lọc dữ liệu');
                            })
                            .finally(() => {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            });
                    });
                }
                
                // Activate room tab if filter parameters are present or tab parameter is set
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('tab') === 'room' || urlParams.get('filter_start_date') || urlParams.get('filter_end_date')) {
                    setTimeout(() => {
                        const roomTab = document.querySelector('#room-tab');
                        if (roomTab) {
                            roomTab.click();
                        }
                    }, 100);
                }

                // === Room Type Filter Functionality (Date Range) ===
                const filterRoomTypeStartDate = document.getElementById('filterRoomTypeStartDate');
                const filterRoomTypeEndDate = document.getElementById('filterRoomTypeEndDate');
                const roomTypeFilterForm = document.getElementById('roomTypeFilterForm');
                
                if (roomTypeFilterForm) {
                    // Validate date range
                    function validateRoomTypeDateRange() {
                        if (filterRoomTypeStartDate && filterRoomTypeEndDate && filterRoomTypeStartDate.value && filterRoomTypeEndDate.value) {
                            if (new Date(filterRoomTypeStartDate.value) > new Date(filterRoomTypeEndDate.value)) {
                                alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc');
                                return false;
                            }
                        }
                        return true;
                    }
                    
                    // When form is submitted, load data via AJAX instead of page reload
                    roomTypeFilterForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Validate date range
                        if (!validateRoomTypeDateRange()) {
                            return;
                        }
                        
                        // Show loading state
                        const submitBtn = this.querySelector('button[type="submit"]');
                        const originalBtnText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lọc...';
                        
                        // Get form data
                        const formData = new FormData(this);
                        const params = new URLSearchParams();
                        for (const [key, value] of formData.entries()) {
                            if (key !== 'tab' && value) {
                                params.append(key, value);
                            }
                        }
                        
                        // Add month and year from current page
                        const currentMonth = {{ $month ?? now()->month }};
                        const currentYear = {{ $year ?? now()->year }};
                        params.append('month', currentMonth);
                        params.append('year', currentYear);
                        
                        // Fetch filtered data
                        fetch(`{{ route('staff.api.roomtype-revenue-filter') }}?${params.toString()}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update chart
                                    updateRoomTypeRevenueChart(data.roomTypeRevenueMonth);
                                    
                                    // Update revenue list
                                    updateRoomTypeRevenueList(data.roomTypeRevenueMonth, data.monthlyRevenue);
                                    
                                    // Update table
                                    updateRoomTypeRevenueTable(data.roomTypeRevenueToday, data.roomTypeRevenueWeek, data.roomTypeRevenueMonth);
                                    
                                    // Update URL without reload
                                    const newUrl = new URL(window.location);
                                    if (formData.get('filter_roomtype_start_date')) {
                                        newUrl.searchParams.set('filter_roomtype_start_date', formData.get('filter_roomtype_start_date'));
                                    } else {
                                        newUrl.searchParams.delete('filter_roomtype_start_date');
                                    }
                                    if (formData.get('filter_roomtype_end_date')) {
                                        newUrl.searchParams.set('filter_roomtype_end_date', formData.get('filter_roomtype_end_date'));
                                    } else {
                                        newUrl.searchParams.delete('filter_roomtype_end_date');
                                    }
                                    window.history.pushState({}, '', newUrl);
                                } else {
                                    alert('Có lỗi xảy ra khi lọc dữ liệu');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Có lỗi xảy ra khi lọc dữ liệu');
                            })
                            .finally(() => {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalBtnText;
                            });
                    });
                }
                
                // Activate room type tab if filter parameters are present or tab parameter is set
                if (urlParams.get('tab') === 'roomtype' || urlParams.get('filter_roomtype_start_date') || urlParams.get('filter_roomtype_end_date')) {
                    setTimeout(() => {
                        const roomTypeTab = document.querySelector('#roomtype-tab');
                        if (roomTypeTab) {
                            roomTypeTab.click();
                        }
                    }, 100);
                }

                // Functions to update room revenue data
                function updateRoomRevenueChart(roomRevenueMonth) {
                    const roomLabels = roomRevenueMonth.map(r => r.ma_phong);
                    const roomRevenueData = roomRevenueMonth.map(r => parseFloat(r.revenue || 0));
                    
                    if (window.roomRevenueChart) {
                        window.roomRevenueChart.data.labels = roomLabels;
                        window.roomRevenueChart.data.datasets[0].data = roomRevenueData;
                        window.roomRevenueChart.update();
                    }
                }

                function updateTopRoomsList(roomRevenueMonth, monthlyRevenue) {
                    // Filter and sort top rooms
                    const topRooms = roomRevenueMonth
                        .filter(room => {
                            if (!room.id || !room.ma_phong || !room.revenue || room.revenue <= 0) {
                                return false;
                            }
                            const maPhong = String(room.ma_phong).trim();
                            const hasNumber = /\d/.test(maPhong);
                            const hasDash = maPhong.includes('-') || maPhong.includes('_');
                            return hasNumber || hasDash;
                        })
                        .sort((a, b) => parseFloat(b.revenue) - parseFloat(a.revenue))
                        .slice(0, 10);
                    
                    // Find the container for top rooms list
                    const container = document.getElementById('topRoomsList');
                    if (!container) return;
                    
                    if (topRooms.length === 0) {
                        container.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-inbox"></i> Không có dữ liệu</div>';
                        return;
                    }
                    
                    container.innerHTML = topRooms.map(room => {
                        const percentage = monthlyRevenue > 0 ? ((parseFloat(room.revenue) / monthlyRevenue) * 100).toFixed(1) : 0;
                        return `
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <div class="fw-semibold text-dark">${room.ma_phong || 'N/A'}</div>
                                    <small class="text-muted">${room.loai_phong || ''}</small><br>
                                    <small class="text-muted">${room.booking_count || 0} đơn</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-success d-block">${new Intl.NumberFormat('vi-VN').format(parseFloat(room.revenue || 0))}đ</strong>
                                    <small class="text-muted">${percentage}%</small>
                                </div>
                            </div>
                        `;
                    }).join('');
                }

                function updateRoomRevenueTable(roomRevenueToday, roomRevenueWeek, roomRevenueMonth) {
                    // Merge all rooms
                    const allRoomsMap = new Map();
                    
                    [...roomRevenueToday, ...roomRevenueWeek, ...roomRevenueMonth].forEach(room => {
                        if (!allRoomsMap.has(room.id)) {
                            allRoomsMap.set(room.id, {
                                id: room.id,
                                ma_phong: room.ma_phong,
                                loai_phong: room.loai_phong,
                                trang_thai: room.trang_thai
                            });
                        }
                    });
                    
                    const allRooms = Array.from(allRoomsMap.values());
                    
                    // Sort by month revenue
                    allRooms.sort((a, b) => {
                        const aMonth = roomRevenueMonth.find(r => r.id === a.id);
                        const bMonth = roomRevenueMonth.find(r => r.id === b.id);
                        return (parseFloat(bMonth?.revenue || 0)) - (parseFloat(aMonth?.revenue || 0));
                    });
                    
                    const tbody = document.getElementById('roomRevenueTableBody');
                    if (!tbody) return;
                    
                    if (allRooms.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Không có dữ liệu</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = allRooms.map(room => {
                        const todayData = roomRevenueToday.find(r => r.id === room.id);
                        const weekData = roomRevenueWeek.find(r => r.id === room.id);
                        const monthData = roomRevenueMonth.find(r => r.id === room.id);
                        
                        const todayRev = parseFloat(todayData?.revenue || 0);
                        const weekRev = parseFloat(weekData?.revenue || 0);
                        const monthRev = parseFloat(monthData?.revenue || 0);
                        const bookingCount = monthData?.booking_count || 0;
                        const roomStatus = monthData?.trang_thai || room.trang_thai || 'trong';
                        
                        let statusBadge = 'bg-secondary';
                        let statusText = roomStatus;
                        if (roomStatus === 'dang_o') {
                            statusBadge = 'bg-danger';
                            statusText = 'Đang ở';
                        } else if (roomStatus === 'trong') {
                            statusBadge = 'bg-success';
                            statusText = 'Trong';
                        } else if (roomStatus === 'bao_tri') {
                            statusBadge = 'bg-warning';
                            statusText = 'Bảo trì';
                        }
                        
                        return `
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">${room.ma_phong}</div>
                                    <small class="badge ${statusBadge} text-white">${statusText}</small>
                                </td>
                                <td><small class="text-muted">${room.loai_phong || 'N/A'}</small></td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">${new Intl.NumberFormat('vi-VN').format(todayRev)}đ</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">${new Intl.NumberFormat('vi-VN').format(weekRev)}đ</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">${new Intl.NumberFormat('vi-VN').format(monthRev)}đ</span>
                                </td>
                                <td class="text-end pe-3"><strong>${bookingCount}</strong></td>
                            </tr>
                        `;
                    }).join('');
                }

                // Functions to update room type revenue data
                function updateRoomTypeRevenueChart(roomTypeRevenueMonth) {
                    const roomTypeLabels = roomTypeRevenueMonth.map(r => r.ten);
                    const roomTypeRevenueData = roomTypeRevenueMonth.map(r => parseFloat(r.revenue || 0));
                    
                    if (window.roomTypeRevenueChart) {
                        window.roomTypeRevenueChart.data.labels = roomTypeLabels;
                        window.roomTypeRevenueChart.data.datasets[0].data = roomTypeRevenueData;
                        window.roomTypeRevenueChart.update();
                    }
                }

                function updateRoomTypeRevenueList(roomTypeRevenueMonth, monthlyRevenue) {
                    const container = document.getElementById('roomTypeRevenueList');
                    if (!container) return;
                    
                    if (roomTypeRevenueMonth.length === 0) {
                        container.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-inbox"></i> Không có dữ liệu</div>';
                        return;
                    }
                    
                    container.innerHTML = roomTypeRevenueMonth.map(roomType => {
                        const percentage = monthlyRevenue > 0 ? ((parseFloat(roomType.revenue) / monthlyRevenue) * 100).toFixed(1) : 0;
                        return `
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <div class="fw-semibold text-dark">${roomType.ten || 'N/A'}</div>
                                    <small class="text-muted">${roomType.booking_count || 0} đơn</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-success d-block">${new Intl.NumberFormat('vi-VN').format(parseFloat(roomType.revenue || 0))}đ</strong>
                                    <small class="text-muted">${percentage}%</small>
                                </div>
                            </div>
                        `;
                    }).join('');
                }

                function updateRoomTypeRevenueTable(roomTypeRevenueToday, roomTypeRevenueWeek, roomTypeRevenueMonth) {
                    // Merge all room types
                    const allRoomTypesMap = new Map();
                    
                    [...roomTypeRevenueToday, ...roomTypeRevenueWeek, ...roomTypeRevenueMonth].forEach(roomType => {
                        if (!allRoomTypesMap.has(roomType.id)) {
                            allRoomTypesMap.set(roomType.id, {
                                id: roomType.id,
                                ten: roomType.ten
                            });
                        }
                    });
                    
                    const allRoomTypes = Array.from(allRoomTypesMap.values());
                    
                    // Sort by month revenue
                    allRoomTypes.sort((a, b) => {
                        const aMonth = roomTypeRevenueMonth.find(r => r.id === a.id);
                        const bMonth = roomTypeRevenueMonth.find(r => r.id === b.id);
                        return (parseFloat(bMonth?.revenue || 0)) - (parseFloat(aMonth?.revenue || 0));
                    });
                    
                    const tbody = document.getElementById('roomTypeRevenueTableBody');
                    if (!tbody) return;
                    
                    if (allRoomTypes.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> Không có dữ liệu</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = allRoomTypes.map(roomType => {
                        const todayData = roomTypeRevenueToday.find(r => r.id === roomType.id);
                        const weekData = roomTypeRevenueWeek.find(r => r.id === roomType.id);
                        const monthData = roomTypeRevenueMonth.find(r => r.id === roomType.id);
                        
                        const todayRev = parseFloat(todayData?.revenue || 0);
                        const weekRev = parseFloat(weekData?.revenue || 0);
                        const monthRev = parseFloat(monthData?.revenue || 0);
                        const bookingCount = monthData?.booking_count || 0;
                        
                        return `
                            <tr>
                                <td class="ps-3 fw-semibold">${roomType.ten || 'N/A'}</td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">${new Intl.NumberFormat('vi-VN').format(todayRev)}đ</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info">${new Intl.NumberFormat('vi-VN').format(weekRev)}đ</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary">${new Intl.NumberFormat('vi-VN').format(monthRev)}đ</span>
                                </td>
                                <td class="text-end pe-3"><strong>${bookingCount}</strong></td>
                            </tr>
                        `;
                    }).join('');
                }

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
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        buttonText: {
                            today: 'Hôm nay',
                            month: 'Tháng',
                            week: 'Tuần'
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

            // Validation cho form lọc
            function validateDateRange() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;

                if (!startDate || !endDate) {
                    alert('Vui lòng chọn cả từ ngày và đến ngày');
                    return false;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    alert('Ngày bắt đầu không được lớn hơn ngày kết thúc');
                    return false;
                }

                const today = new Date();
                today.setHours(23, 59, 59, 999);

                if (new Date(endDate) > today) {
                    alert('Ngày kết thúc không được lớn hơn ngày hiện tại');
                    return false;
                }

                return true;
            }

            // Tự động cập nhật max date cho end_date khi start_date thay đổi
            document.getElementById('start_date')?.addEventListener('change', function() {
                const startDate = this.value;
                const endDateInput = document.getElementById('end_date');
                if (startDate && endDateInput) {
                    endDateInput.min = startDate;
                    if (endDateInput.value && new Date(endDateInput.value) < new Date(startDate)) {
                        endDateInput.value = startDate;
                    }
                }
            });

            // Tự động cập nhật min date cho start_date khi end_date thay đổi
            document.getElementById('end_date')?.addEventListener('change', function() {
                const endDate = this.value;
                const startDateInput = document.getElementById('start_date');
                if (endDate && startDateInput) {
                    startDateInput.max = endDate;
                    if (startDateInput.value && new Date(startDateInput.value) > new Date(endDate)) {
                        startDateInput.value = endDate;
                    }
                }
            });
        </script>
    @endpush

    <style>
        /* KPI Cards */
        .kpi-card {
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .kpi-card:hover::before {
            opacity: 1;
        }

        .kpi-card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2) !important;
        }

        .kpi-icon-wrapper {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .kpi-card:hover .kpi-icon-wrapper {
            transform: scale(1.15) rotate(5deg);
            background: rgba(255, 255, 255, 0.3) !important;
        }

        .kpi-card .card-body h2 {
            transition: all 0.3s ease;
        }

        .kpi-card:hover .card-body h2 {
            transform: scale(1.05);
        }

        .chart-card {
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1) !important;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

        /* Fix layout responsive */
        #revenueTabs {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        #revenueTabs .nav-item {
            flex: 0 1 auto;
            margin: 0;
        }

        #revenueTabs .nav-link {
            white-space: nowrap;
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        /* Ensure card header doesn't overflow */
        .card-header {
            overflow: hidden;
        }


        /* Prevent overflow */
        .container-fluid {
            overflow-x: hidden;
            max-width: 100%;
        }

        .card {
            overflow: hidden;
            max-width: 100%;
        }

        .card-body {
            overflow-x: hidden;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }

        /* Fix tab content overflow */
        .tab-content {
            overflow: hidden;
            width: 100%;
        }

        .tab-pane {
            width: 100%;
            max-width: 100%;
        }

        /* Ensure columns don't overflow */
        .row {
            margin-left: 0;
            margin-right: 0;
            max-width: 100%;
        }

        .row>* {
            padding-left: calc(var(--bs-gutter-x) * 0.5);
            padding-right: calc(var(--bs-gutter-x) * 0.5);
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Fix all columns */
        .col-12,
        .col-lg-8,
        .col-lg-4,
        .col-md-6,
        .col-6 {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Prevent text overflow */
        h5,
        h6,
        .fw-bold,
        .fw-semibold {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Fix chart containers */
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }

        /* Fix display-4 on mobile */
        .display-4 {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            line-height: 1.2;
            word-break: break-word;
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

            #revenueTabs {
                flex-direction: row;
                width: 100%;
                gap: 0.25rem;
            }

            #revenueTabs .nav-item {
                flex: 1 1 calc(50% - 0.25rem);
                min-width: 0;
            }

            #revenueTabs .nav-link {
                width: 100%;
                text-align: center;
                font-size: 0.8rem;
                padding: 0.4rem 0.5rem;
                white-space: normal;
                word-break: break-word;
            }

            #revenueTabs .nav-link i {
                display: block;
                margin-bottom: 0.25rem;
            }

            /* Custom tab layout improvements */
            #custom .display-4 {
                font-size: clamp(1.25rem, 5vw, 2rem) !important;
            }

            #custom .badge {
                font-size: 0.75rem !important;
                padding: 0.375rem 0.75rem !important;
            }

            #custom .card-body {
                padding: 1rem !important;
            }

            #custom canvas {
                max-width: 100% !important;
                height: auto !important;
            }


            .row.g-3 {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }

            .row.g-3>* {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }
    </style>
@endsection
