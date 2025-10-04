@extends('layouts.staff')
@section('content')
    <h2 class="mb-4 text-center">Quản Lý Tình Trạng Phòng</h2>

   
    <div class="mb-3">
        <form method="GET" action="{{ route('staff.rooms') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Mã Phòng</label>
                <input type="text" name="ma_phong" class="form-control" placeholder="Mã phòng" value="{{ request('ma_phong') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng Thái Phòng</label>
                <select name="trang_thai" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="trong" {{ request('trang_thai')=='trong'?'selected':'' }}>Trống</option>
                    <option value="dang_su_dung" {{ request('trang_thai')=='dang_su_dung'?'selected':'' }}>Đang sử dụng</option>
                    <option value="dang_don_dep" {{ request('trang_thai')=='dang_don_dep'?'selected':'' }}>Đang dọn dẹp</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng Thái Booking</label>
                <select name="trang_thai_booking" class="form-select">
                    <option value="">-- Tất cả --</option>
                    <option value="dang_cho" {{ request('trang_thai_booking')=='dang_cho'?'selected':'' }}>Chờ Xác Nhận</option>
                    <option value="da_xac_nhan" {{ request('trang_thai_booking')=='da_xac_nhan'?'selected':'' }}>Đã Xác Nhận</option>
                    <option value="da_gan_phong" {{ request('trang_thai_booking')=='da_gan_phong'?'selected':'' }}>Đã Gán Phòng</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Tìm kiếm</button>
            </div>
        </form>
    </div>


    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã Phòng</th>
                    <th>Tầng</th>
                    <th>Trạng Thái Phòng</th>
                    <th>Trạng Thái Booking</th>
                    <th>Khách Hàng</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rooms as $room)
                    <tr>
                        <td>{{ $room->phong->ma_phong }}</td>
                        <td>{{ $room->phong->tang->ten }}</td>

                      
                        <td>
                            @if($room->trang_thai == 'trong')
                                <span class="badge bg-success">Trống</span>
                            @elseif($room->trang_thai == 'dang_su_dung')
                                <span class="badge bg-primary">Đang sử dụng</span>
                            @elseif($room->trang_thai == 'dang_don_dep')
                                <span class="badge bg-warning text-dark">Đang dọn dẹp</span>
                            @endif
                        </td>

              
                        <td>
                            @if($room->datPhongItem && $room->datPhongItem->datPhong->trang_thai)
                                @switch($room->datPhongItem->datPhong->trang_thai)
                                    @case('dang_cho')
                                        <span class="badge bg-warning text-dark">Chờ Xác Nhận</span>
                                        @break
                                    @case('da_xac_nhan')
                                        <span class="badge bg-primary">Đã Xác Nhận</span>
                                        @break
                                    @case('da_gan_phong')
                                        <span class="badge bg-success">Đã Gán Phòng</span>
                                        @break
                                @endswitch
                            @else
                                <span class="text-muted">Chưa đặt</span>
                            @endif
                        </td>

                        <td>{{ $room->datPhongItem->datPhong->user->name ?? 'Chưa xác định' }}</td>

                        {{-- Nút thao tác --}}
                        <td class="text-center">
                            @if($room->trang_thai == 'trong' && $room->datPhongItem && $room->datPhongItem->datPhong->trang_thai == 'dang_cho')
                                <a href="{{ route('staff.assign-rooms', $room->datPhongItem->datPhong->id) }}" class="btn btn-sm btn-warning me-1">Gán Phòng</a>
                            @elseif($room->trang_thai == 'dang_su_dung')
                                <a  class="btn btn-sm btn-primary me-1">Cập Nhật</a>    
                            @else
                                <span class="text-muted">Không có hành động</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $rooms->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endsection
