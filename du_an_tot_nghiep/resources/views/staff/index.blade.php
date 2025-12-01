@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid px-3 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 fw-bold text-dark">Dashboard</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-primary rounded-pill px-3 py-2">Cập nhật: {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    @php
        $stats = [
            // Row 1: Core Metrics
            ['label' => 'Tổng Booking', 'value' => $totalBookings ?? 0, 'icon' => 'bi bi-calendar-check', 'bg' => 'bg-primary', 'color' => 'text-white'],
            ['label' => 'Phòng Trống', 'value' => $availableRooms, 'icon' => 'bi bi-house', 'bg' => 'bg-info', 'color' => 'text-white'],
            ['label' => 'Đã Hủy', 'value' => $cancelledBookings ?? 0, 'icon' => 'bi bi-x-circle', 'bg' => 'bg-danger', 'color' => 'text-white'],
            
            // Row 2: Today's Revenue Focus
            ['label' => 'DT Hôm Nay', 'value' => number_format($todayRevenue ?? 0, 0) . 'đ', 'icon' => 'bi bi-cash-coin', 'bg' => 'bg-success', 'color' => 'text-white'],
            ['label' => 'Hoàn Hôm Nay', 'value' => number_format($todayRefund ?? 0, 0) . 'đ', 'icon' => 'bi bi-arrow-return-left', 'bg' => 'bg-warning', 'color' => 'text-dark'],
            ['label' => 'Net Hôm Nay', 'value' => number_format($todayNetRevenue ?? 0, 0) . 'đ', 'icon' => 'bi bi-wallet2', 'bg' => 'bg-dark', 'color' => 'text-white'],
        ];
    @endphp

    {{-- Main Stats Cards --}}
    <div class="row g-3 mb-4">
        @foreach($stats as $s)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card h-100 border-0 rounded-4 shadow-sm overflow-hidden {{ $s['bg'] }} {{ $s['color'] }} stats-card">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="icon-circle me-3">
                            <i class="{{ $s['icon'] }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1 text-end">
                            <p class="mb-0 small fw-medium opacity-80 text-truncate">{{ $s['label'] }}</p>
                            <h5 class="mb-0 fw-bold fs-6">{{ $s['value'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Date Range Filter --}}
    <div class="card shadow-sm rounded-4 border-0 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('staff.index') }}" id="dateFilterForm">
                <div class="row align-items-end g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold mb-2">
                            <i class="bi bi-calendar-range me-1 text-primary"></i>Lọc theo khoảng ngày
                        </label>
                        <input type="text" 
                               id="dateRangePicker" 
                               class="form-control form-control-lg" 
                               placeholder="Chọn từ ngày - đến ngày..."
                               value="{{ request('start_date') && request('end_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') . ' đến ' . \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') : '' }}"
                               readonly>
                        <input type="hidden" name="start_date" id="startDate" value="{{ request('start_date') }}">
                        <input type="hidden" name="end_date" id="endDate" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-search me-1"></i>Lọc
                        </button>
                    </div>
                    @if(request('start_date'))
                    <div class="col-md-2">
                        <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary w-100 btn-lg">
                            <i class="bi bi-x-lg me-1"></i>Xóa bộ lọc
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Revenue Breakdown Table --}}
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-bar-chart-line text-success me-2"></i>Chi Tiết Doanh Thu
            </h6>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#revenueBreakdown">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="revenueBreakdown">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Thời Gian</th>
                                <th class="text-end">Doanh Thu</th>
                                <th class="text-end">Hoàn Tiền</th>
                                <th class="text-end pe-3">Net Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-3 fw-medium">Hôm nay</td>
                                <td class="text-end text-success">{{ number_format($todayRevenue ?? 0, 0) }}đ</td>
                                <td class="text-end text-warning">{{ number_format($todayRefund ?? 0, 0) }}đ</td>
                                <td class="text-end fw-bold pe-3">{{ number_format($todayNetRevenue ?? 0, 0) }}đ</td>
                            </tr>
                            <tr>
                                <td class="ps-3 fw-medium">Tuần này</td>
                                <td class="text-end text-success">{{ number_format($weeklyRevenue ?? 0, 0) }}đ</td>
                                <td class="text-end text-warning">{{ number_format($weeklyRefund ?? 0, 0) }}đ</td>
                                <td class="text-end fw-bold pe-3">{{ number_format($weeklyNetRevenue ?? 0, 0) }}đ</td>
                            </tr>
                            <tr>
                                <td class="ps-3 fw-medium">Tháng này</td>
                                <td class="text-end text-success">{{ number_format($monthlyRevenue ?? 0, 0) }}đ</td>
                                <td class="text-end text-warning">{{ number_format($monthlyRefund ?? 0, 0) }}đ</td>
                                <td class="text-end fw-bold pe-3">{{ number_format($monthlyNetRevenue ?? 0, 0) }}đ</td>
                            </tr>
                            @if($customRevenue !== null)
                            <tr class="table-info">
                                <td class="ps-3 fw-medium">
                                    <i class="bi bi-calendar-range me-1"></i>{{ $customRangeLabel }}
                                </td>
                                <td class="text-end text-success">{{ number_format($customRevenue ?? 0, 0) }}đ</td>
                                <td class="text-end text-warning">{{ number_format($customRefund ?? 0, 0) }}đ</td>
                                <td class="text-end fw-bold pe-3">{{ number_format($customNetRevenue ?? 0, 0) }}đ</td>
                            </tr>
                            @endif
                            <tr class="table-active">
                                <td class="ps-3 fw-bold">Tổng Cộng</td>
                                <td class="text-end text-success fw-bold">{{ number_format($totalRevenue ?? 0, 0) }}đ</td>
                                <td class="text-end text-warning fw-bold">{{ number_format($totalRefund ?? 0, 0) }}đ</td>
                                <td class="text-end fw-bold pe-3 text-primary">{{ number_format($totalNetRevenue ?? 0, 0) }}đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Room Analytics Overview Section --}}
    @if(count($roomTypeStats ?? []) > 0)
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-graph-up text-primary me-2"></i>Thống Kê Phòng Tháng Này
            </h6>
            <a href="{{ route('staff.analytics.rooms') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-right-circle me-1"></i>Xem chi tiết
            </a>
        </div>
        <div class="card-body p-3">
            {{-- Mini Stats Cards --}}
            <div class="row g-2 mb-3">
                @foreach($roomTypeStats as $roomType)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold text-dark mb-0">{{ $roomType->ten }}</h6>
                                    <span class="badge bg-secondary rounded-pill small">{{ $roomType->total_rooms }} phòng</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="small text-muted">Bookings</div>
                                        <h5 class="mb-0 fw-bold">{{ $roomType->total_bookings }}</h5>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Tỷ lệ</div>
                                        <h5 class="mb-0 fw-bold 
                                            @if($roomType->occupancy_rate >= 70) text-success
                                            @elseif($roomType->occupancy_rate >= 40) text-warning
                                            @else text-danger
                                            @endif">
                                            {{ $roomType->occupancy_rate }}%
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mini Bar Chart --}}
            @if(count($analyticsChartLabels ?? []) > 0)
            <div style="position: relative; height: 200px;">
                <canvas id="roomAnalyticsChart"></canvas>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line text-success me-2"></i> Doanh thu 7 ngày gần nhất</h6>
        </div>
        <div class="card-body p-3">
            <canvas id="revenueChart" height="280"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-4 border-0 h-100">
                <div class="card-header bg-light border-0 py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bell text-warning me-2"></i> Hoạt Động Gần Đây</h6>
                </div>
                <div class="card-body p-0 overflow-auto" style="max-height: 300px;">
                    <div class="list-group list-group-flush">
                        @forelse ($recentActivities as $activity)
                            <div class="list-group-item px-3 py-3 border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="fw-medium small d-block">{{ $activity->ma_tham_chieu }}</span>
                                        <span class="badge 
                                            @if($activity->trang_thai == 'dang_su_dung') bg-success
                                            @elseif($activity->trang_thai == 'dang_cho') bg-warning
                                            @elseif($activity->trang_thai == 'dang_cho_xac_nhan') bg-info
                                            @elseif($activity->trang_thai == 'da_huy') bg-danger
                                            @else bg-secondary
                                            @endif fs-7 px-2 py-1">
                                            {{ Str::ucfirst(str_replace('_', ' ', $activity->trang_thai)) }}
                                        </span>
                                    </div>
                                    <small class="text-muted text-nowrap">{{ $activity->updated_at->format('H:i') }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">Không có hoạt động nào.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-calendar-check me-2"></i>Hoạt Động Hôm Nay ({{ now()->format('d/m/Y') }})
                    </h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.checkin') }}" class="btn btn-sm btn-light">
                            <i class="bi bi-box-arrow-in-right"></i> Tất cả
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        {{-- Check-ins Today --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semibold text-success">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Check-in Hôm Nay
                                </h6>
                                <span class="badge bg-success rounded-pill">{{ $todayCheckins->count() }}</span>
                            </div>
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                                @forelse($todayCheckins as $booking)
                                    <a href="{{ route('staff.bookings.show', $booking->id) }}" 
                                       class="list-group-item list-group-item-action px-2 py-2 border-0 bg-light mb-1 rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small text-dark">{{ $booking->ma_tham_chieu }}</div>
                                                <div class="small text-muted">
                                                    @if($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-open"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.so_phong')->join(', ') }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if($booking->checked_in_at)
                                                    <span class="badge bg-success">✓ Done</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">⏳ Pending</span>
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
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-semibold text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i>Check-out Hôm Nay
                                </h6>
                                <span class="badge bg-danger rounded-pill">{{ $todayCheckouts->count() }}</span>
                            </div>
                            <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                                @forelse($todayCheckouts as $booking)
                                    {{-- <a href="{{ route('staff.checkout.form', $booking->id) }}"
                                       class="list-group-item list-group-item-action px-2 py-2 border-0 bg-light mb-1 rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small text-dark">{{ $booking->ma_tham_chieu }}</div>
                                                <div class="small text-muted">
                                                    @if($booking->datPhongItems->isNotEmpty())
                                                        <i class="bi bi-door-closed"></i>
                                                        {{ $booking->datPhongItems->pluck('phong.so_phong')->join(', ') }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                @if($booking->checked_out_at)
                                                    <span class="badge bg-success">✓ Done</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">⏳ Pending</span>
                                                @endif
                                            </div>
                                        </div>
                                    </a> --}}
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
    </div>

    {{-- Enhanced Beautiful Calendar --}}
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-gradient-primary text-white py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-calendar3 me-2"></i>Lịch Đặt Phòng Tháng Này
                    </h6>
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

    {{-- Booking Detail Modal --}}
    <div class="modal fade" id="bookingDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-info-circle me-2"></i>Chi Tiết Đặt Phòng
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <a href="#" id="viewBookingBtn" class="btn btn-primary">
                        <i class="bi bi-eye"></i> Xem Chi Tiết
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">

{{-- Flatpickr for Date Range Picker --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

<script>
window.initDashboardChartsAndCalendar = function() {
    if (window.revenueChart instanceof Chart) {
        window.revenueChart.destroy();
    }
    if (window.dashboardCalendar) {
        window.dashboardCalendar.destroy();
    }

    const revenueLabels = @json($chartLabels ?? []);
    const revenueData   = @json($revenueData ?? []);
    const chartCanvas   = document.getElementById('revenueChart');

    if (chartCanvas && revenueLabels.length && revenueData.length) {
        const ctx = chartCanvas.getContext('2d');
        window.revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu (VND)',
                    data: revenueData,
                    backgroundColor: 'rgba(25, 135, 84, 0.2)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: 'rgba(25,135,84,1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.parsed.y.toLocaleString('vi-VN') + ' VND'
                        }
                    }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    } else if (chartCanvas) {
        chartCanvas.parentElement.innerHTML = '<div class="text-center text-muted py-5">Không có dữ liệu doanh thu 7 ngày gần nhất</div>';
    }

    // Room Analytics Mini Chart
    const analyticsLabels = @json($analyticsChartLabels ?? []);
    const analyticsData = @json($analyticsChartData ?? []);
    const analyticsCanvas = document.getElementById('roomAnalyticsChart');
    
    if (analyticsCanvas && analyticsLabels.length && analyticsData.length) {
        const analyticsCtx = analyticsCanvas.getContext('2d');
        new Chart(analyticsCtx, {
            type: 'bar',
            data: {
                labels: analyticsLabels,
                datasets: [{
                    label: 'Số lượng booking',
                    data: analyticsData,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Bookings: ' + ctx.parsed.y
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // Enhanced FullCalendar with rich features
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const events = @json($events ?? []);
        
        // Process events with better display
        const processedEvents = events.map(event => {
            const title = event.title || '';
            const parts = title.split('|');
            const bookingRef = parts[0]?.trim() || '';
            const description = parts[1]?.trim() || '';
            
            // Extract status
            let backgroundColor = '#6c757d';
            let status = 'Khác';
            
            if (description.includes('dang_su_dung') || description.includes('Đang sử dụng')) {
                backgroundColor = '#198754';
                status = 'Đang sử dụng';
            } else if (description.includes('da_xac_nhan') || description.includes('Đã xác nhận')) {
                backgroundColor = '#0d6efd';
                status = 'Đã xác nhận';
            } else if (description.includes('da_gan_phong') || description.includes('Đã gán phòng')) {
                backgroundColor = '#20c997';
                status = 'Đã gán phòng';
            }
            
            // Extract customer name
            const customerMatch = description.match(/Khách:\s*([^|]+)/);
            const customerName = customerMatch ? customerMatch[1].trim() : 'Không rõ';
            
            return {
                id: bookingRef,
                title: bookingRef,
                start: event.start,
                end: event.end,
                backgroundColor: backgroundColor,
                borderColor: 'transparent',
                textColor: '#ffffff',
                extendedProps: {
                    bookingRef: bookingRef,
                    customer: customerName,
                    status: status,
                    description: description
                }
            };
        });
        
        window.dashboardCalendar = new FullCalendar.Calendar(calendarEl, {
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
            displayEventTime: false,
            
            // Click event to view detail
            eventClick: function(info) {
                const bookingRef = info.event.extendedProps.bookingRef;
                const customer = info.event.extendedProps.customer;
                const status = info.event.extendedProps.status;
                
                // Show modal with booking info
                const modal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
                const content = document.getElementById('bookingDetailContent');
                
                content.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <small class="text-muted">Mã đặt phòng</small>
                                    <h5 class="mb-0">${bookingRef}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <small class="text-muted">Khách hàng</small>
                                    <h6 class="mb-0">${customer}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <small class="text-muted">Check-in</small>
                                    <div>${new Date(info.event.start).toLocaleDateString('vi-VN')}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <small class="text-muted">Check-out</small>
                                    <div>${new Date(info.event.end).toLocaleDateString('vi-VN')}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <small class="text-muted">Trạng thái</small>
                                    <div>
                                        <span class="badge" style="background-color: ${info.event.backgroundColor}">
                                            ${status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Set view detail button link (you can customize this)
                document.getElementById('viewBookingBtn').href = `/staff/bookings?search=${bookingRef}`;
                
                modal.show();
            },
            
            // Hover tooltip
            eventDidMount: function(info) {
                const customer = info.event.extendedProps.customer;
                const status = info.event.extendedProps.status;
                
                info.el.setAttribute('data-bs-toggle', 'tooltip');
                info.el.setAttribute('data-bs-placement', 'top');
                info.el.setAttribute('title', `${customer} - ${status}`);
                
                new bootstrap.Tooltip(info.el);
            },
            
            // Today highlight
            dayCellClassNames: function(arg) {
                if (arg.isToday) {
                    return ['bg-light-primary'];
                }
            },
            
            // Click empty day (optional: create booking)
            dateClick: function(info) {
                // Optional: Navigate to create booking page with pre-filled date
                // window.location.href = `/staff/bookings/create?date=${info.dateStr}`;
            }
        });
        
        window.dashboardCalendar.render();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.initDashboardChartsAndCalendar();
    
    // Initialize Flatpickr Date Range Picker
    flatpickr("#dateRangePicker", {
        mode: "range",
        dateFormat: "d/m/Y",
        locale: "vn",
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Format: YYYY-MM-DD for backend
                document.getElementById('startDate').value = selectedDates[0].toISOString().split('T')[0];
                document.getElementById('endDate').value = selectedDates[1].toISOString().split('T')[0];
            }
        }
    });
});
</script>
@endpush

<style>
.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12) !important;
}
.icon-circle {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Enhanced Calendar Styling */
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
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
}

.fc .fc-button-primary:hover {
    background-color: #764ba2;
    border-color: #764ba2;
}

.fc .fc-button-primary:not(:disabled).fc-button-active {
    background-color: #764ba2;
    border-color: #764ba2;
}

.fc .fc-daygrid-day-top {
    padding: 4px;
}

.fc .fc-daygrid-day-number {
    font-weight: 600;
    color: #475569;
}

.fc .fc-day-today {
    background-color: rgba(102, 126, 234, 0.1) !important;
}

.fc .fc-event {
    border-radius: 6px;
    padding: 2px 4px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 2px;
    cursor: pointer;
    transition: all 0.2s;
}

.fc .fc-event:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.fc .fc-daygrid-event-dot {
    display: none;
}

.bg-light-primary {
    background-color: rgba(102, 126, 234, 0.05);
}
</style>
@endsection
```