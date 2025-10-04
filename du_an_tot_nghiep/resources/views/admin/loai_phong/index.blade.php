@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Danh sách Loại Phòng</h2>
    <a href="{{ route('admin.loai_phong.create') }}" class="btn btn-primary mb-3">+ Thêm loại phòng</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Tên</th>
                <th>Mô tả</th>
                <th>Sức chứa</th>
                <th>Số giường</th>
                <th>Giá mặc định</th>
                <th>Số lượng thực tế</th>
                <th>Tiện nghi</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loaiPhongs as $lp)
            <tr>
                <td>{{ $lp->ma }}</td>
                <td>{{ $lp->ten }}</td>
                <td>{{ $lp->mo_ta }}</td>
                <td>{{ $lp->suc_chua }}</td>
                <td>{{ $lp->so_giuong }}</td>
                <td>{{ number_format($lp->gia_mac_dinh, 0) }}</td>
                <td>{{ $lp->so_luong_thuc_te }}</td>
                <td>
                    @foreach($lp->tienNghis as $tn)
                        <span class="badge bg-success">{{ $tn->ten }}</span>
                    @endforeach
                </td>
                <td>
                    <a href="{{ route('admin.loai_phong.show', $lp->id) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('admin.loai_phong.edit', $lp->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                    <form action="{{ route('admin.loai_phong.destroy', $lp->id) }}" method="POST" style="display:inline-block">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Xóa loại phòng này?')">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
