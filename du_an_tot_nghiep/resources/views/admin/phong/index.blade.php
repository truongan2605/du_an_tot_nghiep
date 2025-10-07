@extends('layouts.admin')

@section('title','Danh sách phòng')
@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Danh sách phòng</h3>
        <a href="{{ route('admin.phong.create') }}" class="btn btn-primary">+ Thêm phòng</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Mã phòng</th>
                <th>Tên phòng</th>
                <th>Mô tả</th>
                <th>Loại</th>
                <th>Tầng</th>
                <th>Giá</th>
                <th>Ảnh</th>
                
                <th>Tổng giá phòng</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($phongs as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->ma_phong }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->mo_ta }}</td>
                <td>{{ $p->loaiPhong->ten ?? '-' }}</td>
                <td>{{ $p->tang->ten ?? '-' }}</td>
                <td>{{ number_format($p->gia_mac_dinh,0,',','.') }} đ</td>
                <td style="width:120px;">
                    @if($p->images->isNotEmpty())
                        <img src="{{ asset('storage/' . $p->images->first()->image_path) }}" width="100" alt="Ảnh phòng">
                    @else
                        <span class="text-muted">Chưa có ảnh</span>
                    @endif
                </td>
                 <td>{{ number_format($p->tong_gia, 0, ',', '.') }} VNĐ</td>
                <td> 
                    <a href="{{ route('admin.phong.show', $p->id) }}" class="btn btn-info btn-sm">Xem</a>
                    <a href="{{ route('admin.phong.edit', $p->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                    <form action="{{ route('admin.phong.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa phòng?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
