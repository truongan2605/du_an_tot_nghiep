@extends('layouts.staff')

@section('title', 'Dashboard Nhân Viên')

@section('content')
<div class="p-4">

    {{-- Header --}}
    <h2 class="text-center mb-5 fw-bold text-dark">Dashboard Nhân Viên</h2>

    {{-- Thống kê chính --}}
    <div class="row g-4">
        @php
            $stats = [
                ['label'=>'Booking Chờ Xác Nhận','value'=>$pendingBookings,'icon'=>'bi bi-clock','bg'=>'bg-gradient-primary'],
                ['label'=>'Check-in Hôm Nay','value'=>$todayCheckins,'icon'=>'bi bi-box-arrow-in-right','bg'=>'bg-gradient-success'],
                ['label'=>'Doanh Thu Hôm Nay','value'=>number_format($todayRevenue,0).' VND','icon'=>'bi bi-currency-dollar','bg'=>'bg-gradient-warning'],
                ['label'=>'Phòng Trống','value'=>$availableRooms,'icon'=>'bi bi-house','bg'=>'bg-gradient-info']
            ];
        @endphp

        @foreach($stats as $s)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 {{ $s['bg'] }} text-white rounded-3">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="{{ $s['icon'] }} fs-2"></i>
                        <div>
                            <h6 class="mb-1 fw-semibold">{{ $s['label'] }}</h6>
                            <h3 class="fw-bold mb-0">{{ $s['value'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Biểu đồ & Hoạt động --}}
    <div class="row mt-5 g-4">
        {{-- Biểu đồ Check-in/Check-out --}}
        <div class="col-lg-6">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-white fw-bold">Biểu đồ Check-in/Check-out</div>
                <div class="card-body p-3" style="height:350px">
                    <canvas id="checkinChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Hoạt động gần đây --}}
        <div class="col-lg-6">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-white fw-bold d-flex align-items-center">
                    <i class="bi bi-bell-fill text-warning me-2"></i> Hoạt Động Gần Đây
                </div>
                <div class="card-body p-0" style="max-height:350px; overflow-y:auto;">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentActivities as $activity)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="#" class="fw-semibold text-primary text-decoration-none">
                                        {{ $activity->ma_tham_chieu }}
                                    </a>
                                    <span class="badge 
                                        @if($activity->trang_thai == 'da_xac_nhan') bg-success
                                        @elseif($activity->trang_thai == 'dang_cho') bg-warning
                                        @elseif($activity->trang_thai == 'dang_cho_xac_nhan') bg-info
                                        @elseif($activity->trang_thai == 'da_huy') bg-danger
                                        @else bg-secondary
                                        @endif
                                        ms-2 rounded-pill">
                                        {{ $activity->trang_thai }}
                                    </span>
                                </div>
                                <small class="text-muted">{{ $activity->updated_at->format('H:i d/m/Y') }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Không có hoạt động nào.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Lịch & Quick Actions --}}
    <div class="row mt-5 g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm rounded-3 border-0">
                <div class="card-header bg-gradient-primary text-white fw-bold d-flex align-items-center">
                    <i class="bi bi-calendar-event me-2"></i> Lịch Sự Kiện
                </div>
                <div class="card-body p-3">
                    <div id="calendar" class="p-2 rounded bg-white shadow-sm"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 d-flex flex-column gap-3">
            <a href="{{ route('staff.bookings') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3">
                <i class="bi bi-plus-circle me-2 fs-5"></i> Tạo Booking
            </a>
            <a href="{{ route('staff.checkin') }}" class="btn btn-success w-100 d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3">
                <i class="bi bi-box-arrow-in-right me-2 fs-5"></i> Check-in
            </a>
            <a href="{{ route('staff.checkout') }}" class="btn btn-warning w-100 d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3">
                <i class="bi bi-box-arrow-left me-2 fs-5"></i> Check-out
            </a>
            <a href="{{ route('staff.reports') }}" class="btn btn-info w-100 d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3">
                <i class="bi bi-graph-up me-2 fs-5"></i> Báo Cáo
            </a>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('checkinChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Check-in',
                    data: @json($checkinData),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderRadius: 4
                },
                {
                    label: 'Check-out',
                    data: @json($checkoutData),
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true }
            }
        }
    });
});
</script>

{{-- FullCalendar --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        height: 350,
        initialView: 'dayGridMonth',
        events: @json($events),
        eventDidMount: function(info) {
            new bootstrap.Tooltip(info.el, {
                title: info.event.extendedProps.description,
                placement: 'top',
                trigger: 'hover',
                container: 'body'
            });
        }
    });
    calendar.render();
});
</script>

{{-- Custom Gradients --}}
<style>
.bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #6610f2); }
.bg-gradient-success { background: linear-gradient(45deg, #198754, #20c997); }
.bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #fd7e14); }
.bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0d6efd); }
.card-header { font-size: 1rem; font-weight: 600; }
</style>
@endsection
