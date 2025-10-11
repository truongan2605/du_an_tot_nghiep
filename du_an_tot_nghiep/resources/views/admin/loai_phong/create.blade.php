@extends('layouts.admin')

@section('content')
<div class="container">
    <h2 class="mb-4">Thêm Loại Phòng</h2>
    <form action="{{ route('admin.loai_phong.store') }}" method="POST">
        @csrf

        {{-- Thông tin cơ bản --}}
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Mã</label>
                <input type="text" name="ma" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Tên</label>
                <input type="text" name="ten" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label>Mô tả</label>
            <textarea name="mo_ta" class="form-control" rows="3"></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Sức chứa</label>
                <input type="number" name="suc_chua" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Số giường</label>
                <input type="number" name="so_giuong" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Giá mặc định</label>
                <input type="number" name="gia_mac_dinh" class="form-control" required>
            </div>
        </div>

        <input type="hidden" name="so_luong_thuc_te" value="0">

        {{-- Hai cột: tiện nghi - vật dụng --}}
        <div class="row mt-4">
            {{-- Tiện nghi --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">
                        Chọn Tiện Nghi
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        @if($tienNghis->count() > 0)
                            @foreach($tienNghis as $tn)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           name="tien_nghi[]" value="{{ $tn->id }}" 
                                           id="tienNghi{{ $tn->id }}">
                                    <label class="form-check-label" for="tienNghi{{ $tn->id }}">
                                        {{ $tn->ten }}
                                    </label>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">Chưa có tiện nghi nào.</p>
                        @endif
                    </div>
                </div>
            </div>

      {{-- Vật dụng --}}
<div class="col-md-6">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white fw-bold">
            Chọn Vật Dụng
        </div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            @if($vatDungs->count() > 0)
                <div class="row">
                    @foreach($vatDungs as $item)
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="vat_dung[]" 
                                    value="{{ $item->id }}" 
                                    id="vatDung{{ $item->id }}"
                                    {{ in_array($item->id, old('vat_dung', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="vatDung{{ $item->id }}">
                                    {{ $item->ten }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">Chưa có vật dụng nào.</p>
            @endif
        </div>
    </div>
</div>


        {{-- Nút lưu --}}
        <div class="mt-4 d-flex justify-content-end">
            <button class="btn btn-success me-2" type="submit">
                <i class="bi bi-save"></i> Lưu
            </button>
            <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">
                Hủy
            </a>
        </div>

    </form>
</div>
@endsection
