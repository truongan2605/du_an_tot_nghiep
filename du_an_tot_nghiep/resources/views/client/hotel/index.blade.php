@extends('layouts.app')

@section('title', 'Danh sách phòng khách sạn')

@section('content')
<div class="container my-5">
    <h2 class="text-center mb-4">Danh sách phòng khách sạn</h2>
    <div class="row">
        @foreach($phongs as $phong)
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Phòng {{ $phong->ma_phong }}</h5>
                    <p class="card-text">
                        <strong>Loại phòng:</strong> {{ $phong->loaiPhong->ten }} <br>
                        <strong>Tầng:</strong> {{ $phong->tang->ten }} <br>
                        <strong>Sức chứa:</strong> {{ $phong->suc_chua }} người <br>
                        <strong>Số giường:</strong> {{ $phong->so_giuong }} <br>
                        <strong>Giá:</strong> {{ number_format($phong->gia_mac_dinh, 0, ',', '.') }} đ <br>
                        <strong>Trạng thái:</strong> 
                        @if($phong->trang_thai === 'trong')
                            <span class="badge bg-success">Trống</span>
                        @elseif($phong->trang_thai === 'dang_o')
                            <span class="badge bg-danger">Đang ở</span>
                        @elseif($phong->trang_thai === 'bao_tri')
                            <span class="badge bg-warning">Bảo trì</span>
                        @else
                            <span class="badge bg-secondary">Không sử dụng</span>
                        @endif
                    </p>
                    <a href="#" class="btn btn-primary w-100">Đặt ngay</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
