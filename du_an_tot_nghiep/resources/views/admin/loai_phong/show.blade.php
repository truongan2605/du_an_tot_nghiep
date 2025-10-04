@extends('layouts.admin')

@section('title', 'Chi tiết Loại Phòng')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Chi tiết Loại Phòng</h2>

    <table class="table table-bordered">
        <tr>
            <th>Mã:</th>
            <td>{{ $loaiphong->ma }}</td>
        </tr>
        <tr>
            <th>Tên:</th>
            <td>{{ $loaiphong->ten }}</td>
        </tr>
        <tr>
            <th>Mô tả:</th>
            <td>{{ $loaiphong->mo_ta ?? 'Không có' }}</td>
        </tr>
        <tr>
            <th>Sức chứa:</th>
            <td>{{ $loaiphong->suc_chua }}</td>
        </tr>
        <tr>
            <th>Số giường:</th>
            <td>{{ $loaiphong->so_giuong }}</td>
        </tr>
        <tr>
            <th>Giá mặc định:</th>
            <td>{{ number_format($loaiphong->gia_mac_dinh, 0, ',', '.') }} VND</td>
        </tr>
        <tr>
            <th>Số lượng thực tế:</th>
            <td>{{ $loaiphong->so_luong_thuc_te }}</td>
        </tr>
        <tr>
            <th>Tiện nghi:</th>
            <td>
                @if($loaiphong->tienNghis->isNotEmpty())
                    <ul>
                        @foreach($loaiphong->tienNghis as $tienNghi)
                            <li>{{ $tienNghi->ten }}</li>
                        @endforeach
                    </ul>
                @else
                    <em>Chưa có tiện nghi</em>
                @endif
            </td>
        </tr>
    </table>

    <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Quay lại</a>
</div>
@endsection
