@extends('layouts.admin')

@section('title', 'Dashboard Nhân Viên')

@section('content')
<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold text-dark">Dashboard Nhân Viên</h2>
            <p class="text-muted small mb-0">Tổng quan hoạt động khách sạn</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary rounded-pill px-3 py-2 fs-7">
                <i class="bi bi-clock me-1"></i> {{ now()->format('d/m/Y H:i') }}
            </span>
        </div>
    </div>

    @php
        $stats = [
             ['label' => 'Tổng Booking', 'value' => $totalBookings ?? 0, 'icon' => 'bi bi-calendar-check', 'bg' => 'bg-primary', 'color' => 'text-white'],
            ['label' => 'Phòng Trống', 'value' => $availableRooms, 'icon' => 'bi bi-house', 'bg' => 'bg-info', 'color' => 'text-white'],
            ['label' => 'Doanh Thu Tuần', 'value' => number_format($weeklyRevenue, 0) . ' VND', 'icon' => 'bi bi-calendar-week', 'bg' => 'bg-success', 'color' => 'text-white'],
            ['label' => 'Doanh Thu Tháng', 'value' => number_format($monthlyRevenue, 0) . ' VND', 'icon' => 'bi bi-calendar-month', 'bg' => 'bg-primary', 'color' => 'text-white'],
            ['label' => 'Tổng Doanh Thu', 'value' => number_format($totalRevenue, 0) . ' VND', 'icon' => 'bi bi-cash-stack', 'bg' => 'bg-dark', 'color' => 'text-white'],
        ];
    @endphp

    <!-- Stats Grid -->
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

    <!-- Biểu đồ doanh thu -->
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line text-success me-2"></i> Doanh thu 7 ngày gần nhất</h6>
        </div>
        <div class="card-body p-3">
            <canvas id="revenueChart" height="280"></canvas>
        </div>
    </div>

    <!-- Hoạt động + Lịch -->
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
                                            @if($activity->trang_thai == 'da_gan_phong') bg-success
                                            @elseif($activity->trang_thai == 'dang_cho') bg-warning text-dark
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Doanh thu (VND)',
                data: @json($revenueData),
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
                tooltip: { callbacks: { label: ctx => ctx.parsed.y.toLocaleString() + ' VND' } }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        events: @json($events),
    });
    calendar.render();
});
</script>

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
