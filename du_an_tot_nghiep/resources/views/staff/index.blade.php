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
                <div class="card-header bg-light fw-bold">Hoạt Động Gần Đây</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($recentActivities as $activity)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Booking <strong>{{ $activity->ma_tham_chieu }}</strong> ({{ $activity->trang_thai }})</span>
                                <span class="text-muted small">{{ $activity->updated_at }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Không có hoạt động nào.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-light fw-bold">Lịch Sự Kiện</div>
                <div class="card-body">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5 g-3">
        <div class="col-md-4">
            <a href="{{ route('staff.bookings') }}" class="btn btn-primary w-100 shadow-sm">Xác Nhận Booking</a>
        </div>
        <div class="col-md-4">
            <button class="btn btn-secondary w-100 shadow-sm" disabled>Check-in Khách</button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-info w-100 shadow-sm" disabled>Check-out Khách</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            height: 400,
            initialView: 'dayGridMonth',
            events: @json($events)
        });
        calendar.render();
    });
</script>
@endsection
