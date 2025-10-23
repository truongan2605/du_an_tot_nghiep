@extends('layouts.admin')

@section('content')
    <div class="container">
        <h2>Thêm Loại Phòng</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.loai_phong.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label>Mã</label>
                <input type="text" name="ma" class="form-control" value="{{ old('ma') }}" required>
            </div>

            <div class="mb-3">
                <label>Tên</label>
                <input type="text" name="ten" class="form-control" value="{{ old('ten') }}" required>
            </div>

            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="mo_ta" class="form-control">{{ old('mo_ta') }}</textarea>
            </div>

            <div class="mb-3">
                <label>Giá mặc định (VND / night)</label>
                <input type="number" step="0.01" name="gia_mac_dinh" class="form-control"
                    value="{{ old('gia_mac_dinh', 0) }}" required>
            </div>

            <div class="mb-3" hidden>
                <label>Số lượng thực tế</label>
                <input type="number" name="so_luong_thuc_te" class="form-control" value="{{ old('so_luong_thuc_te', 0) }}">
                <div class="form-text">Sẽ được cập nhật tự động dựa trên cấu hình giường nếu bạn chọn các loại giường bên
                    dưới.</div>
            </div>

            <div class="mb-3">
                <label>Tiện nghi</label><br>
                @foreach ($tienNghis as $tn)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="tien_nghi[]" value="{{ $tn->id }}" class="form-check-input"
                            {{ in_array($tn->id, old('tien_nghi', [])) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ $tn->ten }}</label>
                    </div>
                @endforeach
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
    name="vat_dungs[]" 
    value="{{ $item->id }}" 
    id="vatDung{{ $item->id }}"
    {{ in_array($item->id, old('vat_dungs', [])) ? 'checked' : '' }}>
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
            <hr>
            <h5>Bed types (Cấu hình giường cho loại phòng)</h5>
            <p class="text-muted small">Chọn số lượng cho mỗi loại giường. <strong>Suc_chua</strong> và
                <strong>so_giuong</strong> sẽ được tính tự động dựa trên cấu hình này.</p>

            @foreach ($bedTypes as $bt)
                @php
                    $qty = old("bed_types.$bt->id.quantity", 0);
                    $price = old("bed_types.$bt->id.price", $bt->price);
                @endphp
                <div class="row mb-2 align-items-center">
                    <div class="col-md-4">
                        <strong>{{ $bt->name }}</strong>
                        <div class="small text-muted">capacity: {{ $bt->capacity }} / default price:
                            {{ number_format($bt->price, 0, ',', '.') }} đ</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Quantity</label>
                        <input type="number" name="bed_types[{{ $bt->id }}][quantity]" min="0"
                            class="form-control" value="{{ $qty }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Price per bed (optional override)</label>
                        <input type="number" step="0.01" name="bed_types[{{ $bt->id }}][price]"
                            class="form-control" value="{{ $price }}">
                    </div>
                </div>
            @endforeach

            <div class="mt-3">
                <button class="btn btn-success" type="submit">Lưu</button>
                <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection
