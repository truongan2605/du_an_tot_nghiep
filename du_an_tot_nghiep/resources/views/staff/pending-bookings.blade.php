@extends('layouts.staff')

@section('content')
    <h2 class="mb-4">Danh Sách Booking Chờ Xác Nhận</h2>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th class="text-center">ID</th>
                    <th>Tên Khách Hàng</th>
                    <th>Loại Phòng</th>
                    <th>Ngày Nhận</th>
                    <th>Ngày Trả</th>
                    <th class="text-center">SL</th>
                    <th class="text-center">Phòng Đã Gán</th>
                    <th>Trạng Thái</th>
                    <th class="text-end">Tổng Tiền</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="text-center">{{ $booking->id }}</td>
                        <td>{{ $booking->nguoiDung->name ?? $booking->customer_name ?? 'Ẩn danh' }}</td>
                        <td>
                            @if ($booking->datPhongItems->isNotEmpty())
                                {{ $booking->datPhongItems->first()->loaiPhong->ten ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $booking->ngay_nhan_phong ? $booking->ngay_nhan_phong->format('d-m-Y H:i') : 'N/A' }}</td>
                        <td>{{ $booking->ngay_tra_phong ? $booking->ngay_tra_phong->format('d-m-Y H:i') : 'N/A' }}</td>
                        <td class="text-center">{{ $booking->datPhongItems->sum('so_luong') ?? 1 }}</td>
                        <td class="text-center fw-bold">
                            @if ($booking->trang_thai === 'da_gan_phong' && $booking->phongDaDats->count() > 0)
                                @foreach ($booking->phongDaDats as $room)
                                    <span class="badge bg-success me-1">{{ $room->phong->ma_phong }}</span>
                                @endforeach
                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                <span class="text-muted">Chưa gán</span>
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($booking->trang_thai === 'dang_cho')
                                <span class="badge bg-warning text-dark">Chờ XN</span>
                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                <span class="badge bg-primary">Đã XN</span>
                            @elseif ($booking->trang_thai === 'da_gan_phong')
                                <span class="badge bg-success">Đã Gán</span>
                            @else
                                <span class="badge bg-secondary">{{ $booking->trang_thai }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">{{ number_format($booking->tong_tien, 0, ',', '.') }} VNĐ</td>
                        <td class="text-center text-nowrap">
                            @if ($booking->trang_thai === 'dang_cho')
                                <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <select name="phong_id" class="form-select form-select-sm me-1" style="width: auto; display: inline-block;">
                                        <option value="">Chọn phòng (tùy chọn)</option>
                                        @foreach ($availableRooms as $room)
                                            <option value="{{ $room->id }}"
                                                {{ old('phong_id') == $room->id ? 'selected' : '' }}
                                                {{ $room->trang_thai === 'da_dat' ? 'disabled' : '' }}>
                                                {{ $room->ma_phong }} ({{ $room->trang_thai === 'trong' ? 'Trống' : 'Đã gán' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Xác nhận Booking và gán phòng?');">
                                        Xác Nhận
                                    </button>
                                </form>
                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                <a href="{{ route('staff.assign-rooms', $booking->id) }}" class="btn btn-warning btn-sm">
                                    Gán Phòng
                                </a>
                            @else
                                <a href="{{ route('staff.rooms') }}" class="btn btn-info btn-sm">Xem Tình Trạng</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">Không có booking nào chờ xác nhận.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center">
        {{ $bookings->links() }}
    </div>
@endsection