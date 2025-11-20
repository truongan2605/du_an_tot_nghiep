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
            ['label' => 'Tổng Booking', 'value' => $totalBookings ?? 0, 'icon' => 'bi bi-calendar-check', 'bg' => 'bg-primary', 'color' => 'text-white'],
            ['label' => 'Phòng Trống', 'value' => $availableRooms, 'icon' => 'bi bi-house', 'bg' => 'bg-info', 'color' => 'text-white'],
            ['label' => 'Doanh Thu Tuần', 'value' => number_format($weeklyRevenue ?? 0, 0) . ' VND', 'icon' => 'bi bi-calendar-week', 'bg' => 'bg-success', 'color' => 'text-white'],
            ['label' => 'Doanh Thu Tháng', 'value' => number_format($monthlyRevenue ?? 0, 0) . ' VND', 'icon' => 'bi bi-calendar-month', 'bg' => 'bg-primary', 'color' => 'text-white'],
            ['label' => 'Tổng Doanh Thu', 'value' => number_format($totalRevenue ?? 0, 0) . ' VND', 'icon' => 'bi bi-cash-stack', 'bg' => 'bg-dark', 'color' => 'text-white'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($stats as $s)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 rounded-4 shadow-sm overflow-hidden {{ $s['bg'] }} {{ $s['color'] }} stats-card">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="icon-circle me-3">
                            <i class="{{ $s['icon'] }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1 text-end">
                            <p class="mb-0 small fw-medium opacity-80 text-truncate">{{ $s['label'] }}</p>
                            <h5 class="mb-0 fw-bold fs-5">{{ $s['value'] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
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
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-event me-2"></i> Lịch Đặt Phòng</h6>
                </div>
                <div class="card-body p-3">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">

<script>
window.initDashboardChartsAndCalendar = function() {
    if (window.revenueChart instanceof Chart) {
        window.revenueChart.destroy();
    }
    if (window.dashboardCalendar instanceof FullCalendar.Calendar) {
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

    const calendarEl = document.getElementById('calendar');
    if (calendarEl && !calendarEl._calendar) {
        const events = @json($events ?? []);
        window.dashboardCalendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'vi',
            events: events,
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false }
        });
        window.dashboardCalendar.render();
        calendarEl._calendar = true;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    window.initDashboardChartsAndCalendar();
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
</style>
@endsection