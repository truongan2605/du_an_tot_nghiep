@extends('layouts.staff')

@section('title', 'Check-in Bookings')

@section('content')
    <div class="container">
        <h1 class="mb-4">Danh Sách Booking Sẵn Sàng Check-in</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã Tham Chiếu</th>
                    <th>Khách Hàng</th>
                    <th>Ngày Nhận Phòng</th>
                    <th>Tổng Tiền</th>
                    <th>Đã Cọc</th>
                    <th>Còn Lại</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->ma_tham_chieu }}</td>
                        <td>{{ $booking->user->name ?? 'Ẩn danh' }}</td>
                        <td>{{ $booking->ngay_nhan_phong->format('d/m/Y') }}</td>   
                        <td>{{ number_format($booking->tong_tien) }} VND</td>
                         <td>{{ number_format($booking->deposit_amount) }} VND</td>

                        <td>
                            @php
                                $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
                                $remaining = $booking->tong_tien - $paid;
                            @endphp
                            {{ number_format($remaining) }} VND
                        </td>
                        <td>
                            @if ($remaining > 0)
                                <form action="{{ route('payment.remaining', $booking->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <select name="phuong_thuc" class="form-select d-inline-block w-auto">
                                        <option value="tien_mat">Tiền mặt</option>
                                        <option value="vnpay">VNPAY</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning">Thanh Toán Còn Lại</button>
                                </form>
                            @else
                                <form action="{{ route('staff.processCheckin') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                    <button type="submit" class="btn btn-success">Check-in</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Không có booking nào sẵn sàng check-in.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection