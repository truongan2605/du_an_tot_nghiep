```blade
@extends('layouts.admin')

@section('title', 'Chi tiết phòng')

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Chi tiết phòng: {{ $phong->ma_phong }}</h4>
            <a href="{{ route('admin.phong.index') }}" class="btn btn-light btn-sm">← Quay lại</a>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- Thông tin chính -->
                <div class="col-md-6">
                    <h5 class="fw-bold">Thông tin chung</h5>
                    <table class="table table-bordered align-middle">
                        <tr>
                            <th>Mã phòng</th>
                            <td>{{ $phong->ma_phong }}</td>
                        </tr>
                        <tr>
                            <th>Loại phòng</th>
                            <td>{{ $phong->loaiPhong?->ten }}</td>
                        </tr>
                        <tr>
                            <th>Tầng</th>
                            <td>{{ $phong->tang?->ten }}</td>
                        </tr>
                        <tr>
                            <th>Sức chứa</th>
                            <td>{{ $phong->suc_chua }} người</td>
                        </tr>
                        <tr>
                            <th>Số giường</th>
                            <td>{{ $phong->so_giuong }}</td>
                        </tr>
                        <tr>
                            <th>Giá mặc định</th>
                            <td>{{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} VNĐ</td>
                        </tr>
                        <tr>
                            <th>Trạng thái</th>
                            <td>
                                @if($phong->trang_thai == 1)
                                    <span class="badge bg-success">Đang hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Ảnh -->
                <div class="col-md-6">
                    <h5 class="fw-bold">Hình ảnh</h5>
                    @if($phong->images->count())
                        <div class="row g-2">
                            @foreach($phong->images as $img)
                                <div class="col-6 col-md-4">
                                    <div class="border rounded shadow-sm">
                                        <img src="{{ asset('storage/'.$img->image_path) }}" 
                                             class="img-fluid rounded" 
                                             style="object-fit: contain; max-height: 180px; width: 100%;">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p><em>Chưa có ảnh</em></p>
                    @endif
                </div>
            </div>

            <!-- Tiện nghi -->
            <div class="mt-4">
                <h5 class="fw-bold">Tiện nghi</h5>
                <div class="row">
                    <!-- Tiện nghi mặc định -->
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">Tiện nghi mặc định (Loại phòng)</div>
                            <div class="card-body">
                                @if($phong->loaiPhong && $phong->loaiPhong->tienNghis->count())
                                    <ul class="list-unstyled">
                                        @foreach($phong->loaiPhong->tienNghis as $tn)
                                            <li>✔ {{ $tn->ten }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p><em>Không có tiện nghi mặc định</em></p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Tiện nghi bổ sung -->
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">Tiện nghi bổ sung (Phòng)</div>
                            <div class="card-body">
                                @if($phong->tienNghis->count())
                                    <ul class="list-unstyled">
                                        @foreach($phong->tienNghis as $tn)
                                            <li>➕ {{ $tn->ten }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p><em>Chưa có tiện nghi bổ sung</em></p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<div class="mt-3">
    <h5>Tổng giá phòng: 
        <span class="text-success fw-bold">
            {{ number_format($phong->tong_gia, 0, ',', '.') }} VNĐ
        </span>
    </h5>
</div>
        </div> <!-- card-body -->
    </div>
</div>
@endsection
```
