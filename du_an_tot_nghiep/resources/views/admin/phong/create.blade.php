@extends('layouts.admin')

@section('title','Thêm phòng')
@section('content')
<div class="container">
    <h3>Thêm phòng</h3>

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('admin.phong.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- các field cơ bản -->
        <div class="mb-3"><label>Mã phòng</label><input type="text" name="ma_phong" class="form-control" value="{{ old('ma_phong') }}" required></div>

        <div class="mb-3">
            <label>Loại phòng</label>
            <select name="loai_phong_id" class="form-select">
                <option value="">-- Chọn --</option>
                @foreach($loaiPhongs as $lp)
                    <option value="{{ $lp->id }}" {{ old('loai_phong_id') == $lp->id ? 'selected' : '' }}>{{ $lp->ten }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Tầng</label>
            <select name="tang_id" class="form-select">
                <option value="">-- Chọn --</option>
                @foreach($tangs as $t) <option value="{{ $t->id }}" {{ old('tang_id') == $t->id ? 'selected' : '' }}>{{ $t->ten }}</option> @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3"><label>Sức chứa</label><input type="number" name="suc_chua" class="form-control" value="{{ old('suc_chua',2) }}" required></div>
            <div class="col-md-4 mb-3"><label>Số giường</label><input type="number" name="so_giuong" class="form-control" value="{{ old('so_giuong',1) }}" required></div>
            <div class="col-md-4 mb-3"><label>Giá mặc định</label><input type="number" name="gia_mac_dinh" class="form-control" value="{{ old('gia_mac_dinh') }}" required></div>
        </div>

        <div class="mb-3">
            <form action="{{ route('admin.phong.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <!-- ... các input khác ... -->

    <div class="mb-3">
        <label>Ảnh phòng (chọn nhiều)</label>
        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
    </div>

    <button class="btn btn-success">Lưu</button>
</form>

        </div>

        <button class="btn btn-success">Lưu</button>
        <a href="{{ route('admin.phong.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
