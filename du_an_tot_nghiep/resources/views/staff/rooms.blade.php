@extends('layouts.staff')

@section('title', 'Quản Lý Phòng')

@section('content')
<div class="p-4">
    <h2 class="text-center mb-4 fw-bold">Quản Lý Tình Trạng Phòng</h2>

    {{-- Form lọc --}}
    <div class="mb-4 p-3 bg-white rounded-3 shadow-sm">
        <form method="GET" action="{{ route('staff.rooms') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Mã Phòng</label>
                <input type="text" name="ma_phong" class="form-control rounded-2" placeholder="Mã phòng"
                    value="{{ request('ma_phong') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Trạng Thái Phòng</label>
                <select name="trang_thai" class="form-select rounded-2">
                    <option value="">-- Tất cả --</option>
                    <option value="trong" {{ request('trang_thai') == 'trong' ? 'selected' : '' }}>Trống</option>
                    <option value="dang_su_dung" {{ request('trang_thai') == 'dang_su_dung' ? 'selected' : '' }}>Đang sử dụng</option>
                    <option value="dang_don_dep" {{ request('trang_thai') == 'dang_don_dep' ? 'selected' : '' }}>Đang dọn dẹp</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Trạng Thái Booking</label>
                <select name="trang_thai_booking" class="form-select rounded-2">
                    <option value="">-- Tất cả --</option>
                    <option value="dang_cho" {{ request('trang_thai_booking') == 'dang_cho' ? 'selected' : '' }}>Chờ Xác Nhận</option>
                    <option value="da_xac_nhan" {{ request('trang_thai_booking') == 'da_xac_nhan' ? 'selected' : '' }}>Đã Xác Nhận</option>
                    <option value="da_gan_phong" {{ request('trang_thai_booking') == 'da_gan_phong' ? 'selected' : '' }}>Đã Gán Phòng</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary rounded-2">Tìm kiếm</button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-responsive shadow-sm rounded-3 bg-white p-3">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Mã Phòng</th>
                    <th>Tầng</th>
                    <th>Trạng Thái Phòng</th>
                    <th>Trạng Thái Booking</th>
                    <th>Khách Hàng</th>
                    <th>Mã Tham Chiếu</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr @if ($latestRoom && $room->id === $latestRoom->id) class="table-warning" @endif>
                        <td>{{ $room->phong->ma_phong }}</td>
                        <td>{{ $room->phong->tang->ten }}</td>
                        <td>
                            @php
                                $statusRoom = [
                                    'trong' => ['label'=>'Trống','class'=>'bg-success text-white'],
                                    'dang_su_dung' => ['label'=>'Đang sử dụng','class'=>'bg-primary text-white'],
                                    'dang_don_dep' => ['label'=>'Đang dọn dẹp','class'=>'bg-warning text-dark'],
                                ];
                            @endphp
                            <span class="badge rounded-pill {{ $statusRoom[$room->trang_thai]['class'] ?? 'bg-secondary' }}">
                                {{ $statusRoom[$room->trang_thai]['label'] ?? 'Không xác định' }}
                            </span>
                        </td>
                        <td>
                            @if ($room->datPhongItem && $room->datPhongItem->datPhong->trang_thai)
                                @php
                                    $statusBooking = [
                                        'dang_cho' => ['label'=>'Chờ Xác Nhận','class'=>'bg-warning text-dark'],
                                        'da_xac_nhan' => ['label'=>'Đã Xác Nhận','class'=>'bg-primary text-white'],
                                        'da_gan_phong' => ['label'=>'Đã Gán Phòng','class'=>'bg-success text-white'],
                                    ];
                                    $bk = $room->datPhongItem->datPhong->trang_thai;
                                @endphp
                                <span class="badge rounded-pill {{ $statusBooking[$bk]['class'] ?? 'bg-secondary' }}">
                                    {{ $statusBooking[$bk]['label'] ?? $bk }}
                                </span>
                            @else
                                <span class="text-muted">Chưa đặt</span>
                            @endif
                        </td>
                        <td>{{ $room->datPhongItem->datPhong->user->name ?? 'Chưa xác định' }}</td>
                        <td>{{ $room->datPhongItem->datPhong->ma_tham_chieu ?? 'Chưa có' }}</td>
                        <td class="text-center text-nowrap">
                            @if ($room->trang_thai == 'trong' && $room->datPhongItem && $room->datPhongItem->datPhong->trang_thai == 'dang_cho')
                                <a href="{{ route('staff.assign-rooms', $room->datPhongItem->datPhong->id) }}"
                                   class="btn btn-warning btn-sm me-1">Gán Phòng</a>
                            @elseif($room->trang_thai == 'dang_su_dung')
                                <a class="btn btn-primary btn-sm me-1">Cập Nhật</a>
                            @else
                                <span class="text-muted">Không có hành động</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Không có phòng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    
    <div class="d-flex justify-content-center mt-3">
        {{ $rooms->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>

<style>
.table-responsive {
    background: #fff;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.08);
}
.table-hover tbody tr:hover {
    background-color: #f1f5f9;
    transition: background-color 0.2s;
}
.badge {
    font-size: 0.85rem;
}
</style>
@endsection
