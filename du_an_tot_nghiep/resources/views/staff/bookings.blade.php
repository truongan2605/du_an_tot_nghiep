@extends('layouts.staff')

@section('title', 'Danh Sách Booking Chờ Xác Nhận')

@section('content')
    <div class="p-4">
        <h2>Danh Sách Booking Chờ Xác Nhận</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Mã Tham Chiếu</th>
                    <th>Ngày Nhận Phòng</th>
                    <th>Tổng Tiền</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->ma_tham_chieu }}</td>
                        <td>{{ $booking->ngay_nhan_phong }}</td>
                        <td>{{ number_format($booking->tong_tien, 0) }} VND</td>
                        <td>
                            <form action="{{ route('staff.confirm', $booking->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">Xác Nhận</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection