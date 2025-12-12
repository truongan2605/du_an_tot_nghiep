@extends('layouts.staff')

@section('content')
<div class="container">
    <h2 class="mb-4">Gán Phòng cho Booking {{ $booking->id }}</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Thông tin Booking
        </div>
        <div class="card-body">
            <p><strong>Khách hàng:</strong> {{ $booking->nguoiDung->name ?? 'Ẩn danh' }}</p>
            <p><strong>Ngày nhận:</strong> {{ $booking->ngay_nhan_phong }}</p>
            <p><strong>Ngày trả:</strong> {{ $booking->ngay_tra_phong }}</p>
            <p><strong>Trạng thái:</strong>
                @switch($booking->trang_thai)
                    @case('dang_cho') <span class="badge bg-warning text-dark">Chờ xác nhận</span> @break
                    @case('da_xac_nhan') <span class="badge bg-primary">Đã xác nhận</span> @break
                    @case('da_gan_phong') <span class="badge bg-success">Đã gán phòng</span> @break
                    @default <span class="badge bg-secondary">{{ $booking->trang_thai }}</span>
                @endswitch
            </p>
        </div>
    </div>

    <form action="{{ route('staff.assign-rooms', $booking->id) }}" method="POST">
        @csrf
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item ID</th>
                    <th>Loại phòng</th>
                    <th>Số lượng</th>
                    <th>Mã phòng chọn</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->loaiPhong->ten ?? 'Không xác định' }}</td>
                        <td>{{ $item->so_luong }}</td>
                        <td>
                            <select name="assignments[{{ $loop->index }}][phong_id]" class="form-select" required>
                                <option value="">-- Chọn phòng --</option>
                                @foreach($availableRooms as $room)
                                    <option value="{{ $room->id }}">
                                        {{ $room->ma_phong }} ({{ $room->trang_thai === 'da_dat' ? 'Đã đặt' : 'Trống' }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="assignments[{{ $loop->index }}][dat_phong_item_id]" value="{{ $item->id }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-between mt-3">
            <a href="{{ route('staff.pending-bookings') }}" class="btn btn-secondary">Quay lại</a>
            <button type="submit" class="btn btn-success">Gán phòng</button>
        </div>
    </form>
</div>
@endsection
