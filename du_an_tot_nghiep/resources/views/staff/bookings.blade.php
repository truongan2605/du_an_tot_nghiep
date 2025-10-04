@extends('layouts.staff')
@section('content')
    <h2 class="text-center mb-4">Tổng Quan Booking</h2>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mã Phòng Đã Gán</th>
                    <th>Khách Hàng</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Nhận</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>
                            @php
                                $roomCodes = [];
                                if ($booking->datPhongItems) {
                                    foreach ($booking->datPhongItems as $item) {
                                        if ($item->phongDaDats) {
                                            foreach ($item->phongDaDats as $phongDaDat) {
                                                if ($phongDaDat->trang_thai === 'da_dat' && $phongDaDat->phong) {
                                                    $roomCodes[] = $phongDaDat->phong->ma_phong;
                                                }
                                            }
                                        }
                                    }
                                }
                            @endphp

                            @if (!empty($roomCodes))
                                @foreach (array_unique($roomCodes) as $code)
                                    <span class="badge bg-success me-1">{{ $code }}</span>
                                @endforeach
                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                <span class="text-warning fw-bold">Chờ Gán</span>
                            @else
                                <span class="text-muted">Chưa gán</span>
                            @endif
                        </td>
                        <td>{{ $booking->nguoiDung->name ?? 'Ẩn danh' }}</td>
                        <td>
                            @switch($booking->trang_thai)
                                @case('dang_cho')
                                    <span class="badge bg-warning text-dark">Chờ XN</span>
                                    @break
                                @case('da_xac_nhan')
                                    <span class="badge bg-primary">Đã XN</span>
                                    @break
                                @case('da_gan_phong')
                                    <span class="badge bg-success">Đã Gán Phòng</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $booking->trang_thai }}</span>
                            @endswitch
                        </td>
                        <td>{{ $booking->ngay_nhan_phong }}</td>
                        <td class="text-center">
                            @if ($booking->trang_thai === 'dang_cho')
                                <a href="{{ route('staff.pending-bookings') }}" class="btn btn-info btn-sm">Chi Tiết</a>
                                <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm ms-2">Xác Nhận</button>
                                </form>
                                <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn hủy booking này?');">
                                        Hủy
                                    </button>
                                </form>
                            @elseif ($booking->trang_thai === 'da_xac_nhan')
                                <a href="{{ route('staff.assign-rooms', $booking->id) }}" class="btn btn-warning btn-sm">Gán Phòng</a>
                                <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm ms-2" onclick="return confirm('Bạn có chắc muốn hủy booking này?');">
                                        Hủy
                                    </button>
                                </form>
                            @elseif ($booking->trang_thai === 'da_gan_phong')
                                <a href="{{ route('staff.rooms') }}" class="btn btn-success btn-sm">Xem Tình Trạng</a>
                            @else
                                <span class="text-muted">Không có hành động</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endsection