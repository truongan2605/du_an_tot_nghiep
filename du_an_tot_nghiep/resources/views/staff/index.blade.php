@extends('layouts.staff')

@section('title', 'Dashboard Nhân Viên')

@section('content')
<div class="p-4">
    <h2 class="mb-4 fw-bold text-dark">Dashboard Nhân Viên</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title mb-2">Booking Chờ Xác Nhận</h6>
                    <h3 class="fw-bold">{{ $pendingBookings }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title mb-2">Check-in Hôm Nay</h6>
                    <h3 class="fw-bold">{{ $todayCheckins }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title mb-2">Doanh Thu Hôm Nay</h6>
                    <h3 class="fw-bold">{{ number_format($todayRevenue, 0) }} VND</h3>
                </div>
            </div>
        </div>
    </div>

   
    <div class="row mt-5 g-4">
       
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-light fw-bold d-flex align-items-center">
                    <i class="bi bi-pin-angle-fill text-danger me-2"></i> Hoạt Động Gần Đây
                </div>
                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentActivities as $activity)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="#" class="fw-bold text-primary text-decoration-none">
                                        {{ $activity->ma_tham_chieu }}
                                    </a>
                                    <span class="badge bg-warning text-dark ms-2">
                                        {{ $activity->trang_thai }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    {{ $activity->updated_at->format('d/m/Y H:i') }}
                                </small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Không có hoạt động nào.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white fw-bold d-flex align-items-center rounded-top">
                    <i class="bi bi-calendar-event me-2"></i> Lịch Sự Kiện
                </div>
                <div class="card-body p-3 bg-light">
                    <div id="calendar" class="p-2 bg-white rounded shadow-sm"></div>
                </div>
            </div>
        </div>
    </div>

   
    <div class="row mt-4 g-3">
        <div class="col-md-4">
            <a href="{{ route('staff.bookings') }}" 
               class="btn btn-primary w-100 d-flex align-items-center justify-content-center shadow-sm">
                <i class="bi bi-check2-circle me-2"></i> Xác Nhận Booking
            </a>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center shadow-sm" disabled>
                <i class="bi bi-box-arrow-in-right me-2"></i> Check-in Khách
            </button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center shadow-sm" disabled>
                <i class="bi bi-box-arrow-left me-2"></i> Check-out Khách
            </button>
        </div>
    </div>
</div>


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
@endsection
