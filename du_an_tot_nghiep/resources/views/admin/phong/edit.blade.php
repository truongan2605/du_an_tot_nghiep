@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Sửa phòng</h1>

    {{-- Form update phòng --}}
    <form action="{{ route('admin.phong.update', $phong->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Mã phòng</label>
            <input type="text" name="ma_phong" class="form-control" value="{{ old('ma_phong', $phong->ma_phong) }}">
        </div>

        <div class="mb-3">
            <label>Loại phòng</label>
            <select name="loai_phong_id" class="form-control" style="color: black;">
                @foreach($loaiPhongs as $loai)
                    <option value="{{ $loai->id }}" {{ $phong->loai_phong_id == $loai->id ? 'selected' : '' }}>
                        {{ $loai->ten }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Tầng</label>
            <select name="tang_id" class="form-control text-dark">
                @foreach($tangs as $tang)
                    <option value="{{ $tang->id }}" {{ $phong->tang_id == $tang->id ? 'selected' : '' }}>
                        {{ $tang->ten }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Sức chứa</label>
            <input type="number" name="suc_chua" class="form-control" value="{{ old('suc_chua', $phong->suc_chua) }}">
        </div>

        <div class="mb-3">
            <label>Số giường</label>
            <input type="number" name="so_giuong" class="form-control" value="{{ old('so_giuong', $phong->so_giuong) }}">
        </div>

        <div class="mb-3">
            <label>Giá mặc định</label>
            <input type="number" step="0.01" name="gia_mac_dinh" class="form-control" value="{{ old('gia_mac_dinh', $phong->gia_mac_dinh) }}">
        </div>

        <div class="mb-3">
    <label class="form-label">Tiện nghi</label><br>
    @foreach($tienNghis as $tn)
        <div class="form-check form-check-inline">
            <input 
                type="checkbox" 
                name="tien_nghi[]" 
                value="{{ $tn->id }}" 
                class="form-check-input"
                @if(isset($phong) && $phong->tienNghis->contains($tn->id)) checked @endif
            >
            <label class="form-check-label">
                <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
            </label>
        </div>
    @endforeach
</div>


        <div class="mb-3">
            <label>Thêm ảnh mới (có thể chọn nhiều)</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>

        <button type="submit" class="btn btn-primary">Cập nhật</button>
    </form>

    <hr>

    {{-- Ảnh hiện có --}}
    <h4>Ảnh hiện tại</h4>
    <div class="d-flex flex-wrap">
        @forelse($phong->images as $img)
            <div class="text-center me-3 mb-3" style="width: 150px;">
                <img src="{{ asset('storage/' . $img->image_path) }}" class="img-thumbnail mb-2" style="height:100px; object-fit:cover;">

                {{-- Form xoá ảnh TÁCH RIÊNG, không nằm trong form update --}}
                <form action="{{ route('admin.phong.image.destroy', $img->id) }}" method="POST" onsubmit="return confirm('Xóa ảnh này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                </form>
            </div>
        @empty
            <p class="text-muted">Chưa có ảnh nào</p>
        @endforelse
    </div>
</div>
@endsection
