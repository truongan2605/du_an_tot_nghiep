@extends('layouts.admin')

@section('title','Sửa phòng')

@section('content')
<div class="container mt-4">
    <h3 class="mb-4">Sửa phòng</h3>

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $err) 
            <li>{{ $err }}</li> 
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('admin.phong.update', $phong->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Mã phòng</label>
            <input type="text" name="ma_phong" class="form-control" value="{{ old('ma_phong', $phong->ma_phong) }}" required>
        </div>

        <div class="mb-3">
            <label>Loại phòng</label>
            <select name="loai_phong_id" class="form-select" required>
                @foreach($loaiPhongs as $lp)
                    <option value="{{ $lp->id }}" {{ $phong->loai_phong_id == $lp->id ? 'selected' : '' }}>
                        {{ $lp->ten }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Tầng</label>
            <select name="tang_id" class="form-select" required>
                @foreach($tangs as $t)
                    <option value="{{ $t->id }}" {{ $phong->tang_id == $t->id ? 'selected' : '' }}>
                        {{ $t->ten }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Sức chứa</label>
                <input type="number" name="suc_chua" class="form-control" value="{{ old('suc_chua', $phong->suc_chua) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Số giường</label>
                <input type="number" name="so_giuong" class="form-control" value="{{ old('so_giuong', $phong->so_giuong) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Giá mặc định</label>
                <input type="number" name="gia_mac_dinh" class="form-control" value="{{ old('gia_mac_dinh', $phong->gia_mac_dinh) }}" required>
            </div>
        </div>

        <h6 class="mt-3">Tiện nghi</h6>
        <div class="d-flex flex-wrap gap-2">
            @php
                $tienNghiLoaiPhong = $phong->loaiPhong->tienNghis->pluck('id')->toArray(); // tiện nghi mặc định theo loại phòng
                $tienNghiPhong = $phong->tienNghis->pluck('id')->toArray(); // tiện nghi thủ công của phòng
                $allChecked = array_unique(array_merge($tienNghiLoaiPhong, $tienNghiPhong));
            @endphp

            @foreach($tienNghis as $tn)
                <div class="form-check form-check-inline">
                    <input 
                        type="checkbox" 
                        name="tien_nghi[]" 
                        value="{{ $tn->id }}" 
                        class="form-check-input"
                        {{ in_array($tn->id, $allChecked) ? 'checked' : '' }}
                    >
                    <label class="form-check-label">
                        <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                    </label>
                </div>
            @endforeach
        </div>
<div class="mb-3">
            <label class="form-label">Ảnh hiện tại</label>
            <div class="d-flex flex-wrap gap-3">
                @foreach($phong->images as $img)
                    <div class="border rounded p-1" style="max-width:200px;">
                        <img src="{{ asset('storage/'.$img->image_path) }}" class="img-fluid" style="object-fit: contain; width:100%; height:auto;">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Thêm ảnh mới</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>

       

        <button class="btn btn-success">Cập nhật</button>
        <a href="{{ route('admin.phong.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection


        {{-- <div class="mb-3">
            <label class="form-label">Ảnh hiện tại</label>
            <div class="d-flex flex-wrap gap-3">
                @foreach($phong->images as $img)
                    <div class="border rounded p-1" style="max-width:200px;">
                        <img src="{{ asset('storage/'.$img->image_path) }}" class="img-fluid" style="object-fit: contain; width:100%; height:auto;">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Thêm ảnh mới</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div> --}}

  
