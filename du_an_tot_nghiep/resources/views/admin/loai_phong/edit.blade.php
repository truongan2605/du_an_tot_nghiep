@extends('layouts.admin')

@section('content')

<form action="{{ route('admin.loai_phong.update', $loaiphong->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>Mã loại phòng</label>
        <input type="text" name="ma" class="form-control" value="{{ old('ma', $loaiphong->ma) }}" required>
    </div>

    <div class="mb-3">
        <label>Tên loại phòng</label>
        <input type="text" name="ten" class="form-control" value="{{ old('ten', $loaiphong->ten) }}" required>
    </div>

    <div class="mb-3">
        <label>Mô tả</label>
        <textarea name="mo_ta" class="form-control">{{ old('mo_ta', $loaiphong->mo_ta) }}</textarea>
    </div>

    <div class="mb-3">
        <label>Sức chứa</label>
        <input type="number" name="suc_chua" class="form-control" value="{{ old('suc_chua', $loaiphong->suc_chua) }}" required>
    </div>

    <div class="mb-3">
        <label>Số giường</label>
        <input type="number" name="so_giuong" class="form-control" value="{{ old('so_giuong', $loaiphong->so_giuong) }}" required>
    </div>

    <div class="mb-3">
        <label>Giá mặc định</label>
        <input type="number" step="0.01" name="gia_mac_dinh" class="form-control" value="{{ old('gia_mac_dinh', $loaiphong->gia_mac_dinh) }}" required>
    </div>

    <div class="mb-3">
        <label>Số lượng thực tế</label>
        <input type="number" name="so_luong_thuc_te" class="form-control" value="{{ old('so_luong_thuc_te', $loaiphong->so_luong_thuc_te) }}" required>
    </div>

    <div class="mb-3">
        <label>Tiện nghi</label>
        <div class="d-flex flex-wrap">
            @foreach($tienNghis as $tn)
                <div class="form-check me-3">
                    <input 
                        type="checkbox" 
                        name="tien_nghi_ids[]" 
                        value="{{ $tn->id }}" 
                        class="form-check-input"
                        {{ in_array($tn->id, old('tien_nghi_ids', $loaiphong->tienNghis->pluck('id')->toArray())) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ $tn->ten }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Cập nhật</button>
</form>
@endsection