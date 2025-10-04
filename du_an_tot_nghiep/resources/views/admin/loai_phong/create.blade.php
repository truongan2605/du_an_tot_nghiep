@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Thêm Loại Phòng</h2>
    <form action="{{ route('admin.loai_phong.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Mã</label>
            <input type="text" name="ma" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Tên</label>
            <input type="text" name="ten" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Mô tả</label>
            <textarea name="mo_ta" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>Sức chứa</label>
            <input type="number" name="suc_chua" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Số giường</label>
            <input type="number" name="so_giuong" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Giá mặc định</label>
            <input type="number" name="gia_mac_dinh" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Số lượng thực tế</label>
            <input type="number" name="so_luong_thuc_te" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Tiện nghi</label><br>
            @foreach($tienNghis as $tn)
                <input type="checkbox" name="tien_nghi[]" value="{{ $tn->id }}"> {{ $tn->ten }} <br>
            @endforeach
        </div>

        <button class="btn btn-success">Lưu</button>
        <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
