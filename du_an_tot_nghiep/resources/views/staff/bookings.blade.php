@extends('layouts.staff')

@section('title', 'Danh Sách Booking Chờ Xác Nhận')

@section('content')
    <div class="p-4">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white fw-bold">
                Danh Sách Booking Chờ Xác Nhận
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tên Khách Hàng</th>
                                <th>SĐT</th>
                                <th>Loại Phòng</th>
                                <th>Ngày Nhận</th>
                                <th>Ngày Trả</th>
                                <th>Trạng Thái</th>
                                <th>Mã Tham Chiếu</th>
                                <th>Tổng Tiền</th>
                                <th>Assign Phòng</th>
                                <th class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->user->name ?? 'Khách ẩn danh' }}</td>
                                    <td>{{ $booking->user->so_dien_thoai ?? 'N/A' }}</td>
                                    <td>
                                        @foreach ($booking->datPhongItems as $item)
                                            <span
                                                class="badge bg-info text-dark mb-1">{{ $item->loaiPhong->ten ?? 'N/A' }}</span><br>
                                        @endforeach
                                    </td>
                                    <td>{{ $booking->ngay_nhan_phong }}</td>
                                    <td>{{ $booking->ngay_tra_phong }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ $booking->trang_thai }}
                                        </span>
                                    </td>
                                    <td><span class="fw-bold">{{ $booking->ma_tham_chieu }}</span></td>
                                    <td class="text-success fw-bold">{{ number_format($booking->tong_tien, 0) }} VND</td>
                                    <td>
                                        <select name="phong_id" class="form-select form-select-sm">
                                            <option value="">Không assign</option>
                                            @foreach ($availableRooms as $room)
                                                <option value="{{ $room->id }}">
                                                    Phòng {{ $room->ma_phong }} (Tầng {{ $room->tang->ten }} - Loại
                                                    {{ $room->loaiPhong->ten }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="Thao tác">
                                            @if ($booking->trang_thai === 'dang_cho')
                                                <form action="{{ route('staff.confirm', $booking->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary">Xác Nhận</button>
                                                </form>
                                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                                <a href="{{ route('staff.assign-rooms.form', $booking->id) }}"
                                                    class="btn btn-secondary">Gán Phòng</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>


                <div class="d-flex justify-content-center mt-3">
                    {{ $bookings->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
