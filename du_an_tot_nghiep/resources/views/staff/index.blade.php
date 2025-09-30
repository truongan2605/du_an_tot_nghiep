@extends('layouts.staff')

@section('title', 'Dashboard Nhân Viên')

@section('content')
    <div class="p-4">
        <h2 class="text-2xl font-bold mb-4">Dashboard Nhân Viên</h2>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Booking Chờ Xác Nhận</h5>
                        <p class="card-text display-4">{{ $pendingBookings }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Check-in Hôm Nay</h5>
                        <p class="card-text display-4">{{ $todayCheckins }}</p>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('staff.bookings') }}" class="btn btn-primary mt-3">Xem Danh Sách Booking</a>
    </div>
@endsection