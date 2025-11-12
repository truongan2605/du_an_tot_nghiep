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
            ['label'=>'Booking Chờ Xác Nhận','value'=>$pendingBookings,'icon'=>'bi bi-clock','bg'=>'bg-primary','color'=>'text-white'],
            ['label'=>'Check-in Hôm Nay','value'=>$todayCheckins,'icon'=>'bi bi-box-arrow-in-right','bg'=>'bg-success','color'=>'text-white'],
            ['label'=>'Doanh Thu Hôm Nay','value'=>number_format($todayRevenue,0).' VND','icon'=>'bi bi-currency-dollar','bg'=>'bg-warning text-dark','color'=>'text-dark'],
            ['label'=>'Doanh Thu Tuần Này','value'=>number_format($weeklyRevenue,0).' VND','icon'=>'bi bi-calendar-week','bg'=>'bg-info','color'=>'text-white'],
            ['label'=>'Doanh Thu Tháng Này','value'=>number_format($monthlyRevenue,0).' VND','icon'=>'bi bi-calendar-month','bg'=>'bg-success','color'=>'text-white'], 
            ['label'=>'Tổng Doanh Thu','value'=>number_format($totalRevenue,0).' VND','icon'=>'bi bi-cash-stack','bg'=>'bg-success','color'=>'text-white'],
            ['label'=>'Phòng Trống','value'=>$availableRooms,'icon'=>'bi bi-house','bg'=>'bg-warning text-dark','color'=>'text-dark'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach($stats as $s)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden {{ $s['bg'] }} {{ $s['color'] }}">
                    <div class="card-body py-3 px-0">
                        <div class="d-flex align-items-center px-3">
                            <div class="bg-white bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="{{ $s['icon'] }} fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small fw-semibold opacity-75">{{ $s['label'] }}</p>
                                <h5 class="fw-bold mb-0">{{ $s['value'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm rounded-3 border-0 h-100">
                <div class="card-header bg-light fw-semibold border-0 py-3">
                    <i class="bi bi-graph-up text-primary me-2"></i> Biểu đồ Check-in/Check-out
                </div>
                <div class="card-body p-3">
                    <canvas id="checkinChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm rounded-3 border-0 h-100">
                <div class="card-header bg-light fw-semibold border-0 py-3 d-flex align-items-center">
                    <i class="bi bi-bell text-warning me-2"></i> Hoạt Động Gần Đây
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        @forelse ($recentActivities as $activity)
                            <div class="list-group-item px-3 py-3 border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <a href="#" class="fw-semibold text-decoration-none small">{{ $activity->ma_tham_chieu }}</a>
                                        <span class="badge 
                                            @if($activity->trang_thai == 'dang_su_dung') bg-success
                                            @elseif($activity->trang_thai == 'dang_cho') bg-warning
                                            @elseif($activity->trang_thai == 'dang_cho_xac_nhan') bg-info
                                            @elseif($activity->trang_thai == 'da_huy') bg-danger
                                            @else bg-secondary
                                            @endif
                                            ms-2 rounded-pill px-2 py-1 small">
                                            {{ $activity->trang_thai }}
                                        </span>
                                    </div>
                                    <small class="text-muted ms-2">{{ $activity->updated_at->format('H:i d/m') }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item text-center text-muted py-4">Không có hoạt động nào.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm rounded-3 border-0 h-100">
                <div class="card-header bg-primary text-white fw-semibold border-0 py-3 d-flex align-items-center">
                    <i class="bi bi-calendar-event me-2"></i> Lịch Sự Kiện
                </div>
                <div class="card-body p-3">
                    <div id="calendar" class="bg-white rounded shadow-sm"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex flex-column gap-3">
            <a href="{{ route('staff.bookings') }}" class="btn btn-primary d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3 flex-grow-1">
                <i class="bi bi-plus-circle me-2 fs-5"></i> Tạo Booking
            </a>
            <a href="#" class="btn btn-success d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3 flex-grow-1">
                <i class="bi bi-box-arrow-in-right me-2 fs-5"></i> Check-in
            </a>
            <a href="#" class="btn btn-warning d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3 flex-grow-1">
                <i class="bi bi-box-arrow-left me-2 fs-5"></i> Check-out
            </a>
            <a href="{{ route('staff.reports') }}" class="btn btn-info text-white d-flex align-items-center justify-content-center shadow-sm rounded-3 py-3 flex-grow-1">
                <i class="bi bi-graph-up me-2 fs-5"></i> Báo Cáo
            </a>
        </div>
    </div>
</div>

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
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: 'Check-out',
                    data: @json($checkoutData),
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { font: { size: 12 } }
                }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                },
                y: { 
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        height: 'auto',
        initialView: 'dayGridMonth',
        events: @json($events),
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        eventDidMount: function(info) {
            new bootstrap.Tooltip(info.el, {
                title: info.event.extendedProps.description || '',
                placement: 'top',
                trigger: 'hover',
                container: 'body'
            });
        }
    });
    calendar.render();
});
</script>

<style>
.card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
.list-group-item { border-color: rgba(0,0,0,0.05); }
#calendar { font-size: 0.875rem; }
.fc-toolbar-title { font-size: 1rem !important; font-weight: 600; }
.fc-button { font-size: 0.75rem !important; padding: 0.25rem 0.5rem !important; }
</style>
@endsection