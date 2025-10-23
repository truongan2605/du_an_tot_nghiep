@extends('layouts.admin')

@section('content')
    <div class="container">
        <h2>Sửa Loại Phòng</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.loai_phong.update', $loaiphong->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Mã loại phòng</label>
                <input type="text" name="ma" class="form-control" value="{{ old('ma', $loaiphong->ma) }}" required>
            </div>

            <div class="mb-3">
                <label>Tên loại phòng</label>
                <input type="text" name="ten" class="form-control" value="{{ old('ten', $loaiphong->ten) }}"
                    required>
            </div>

            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="mo_ta" class="form-control">{{ old('mo_ta', $loaiphong->mo_ta) }}</textarea>
            </div>

            <div class="mb-3">
                <label>Giá mặc định (VND / night)</label>
                <input type="number" step="0.01" name="gia_mac_dinh" class="form-control"
                    value="{{ old('gia_mac_dinh', $loaiphong->gia_mac_dinh) }}" required>
            </div>

            <div class="mb-3" hidden>
                <label>Số lượng thực tế</label>
                <input type="number" name="so_luong_thuc_te" class="form-control"
                    value="{{ old('so_luong_thuc_te', $loaiphong->so_luong_thuc_te) }}">
                <div class="form-text">Sẽ được cập nhật tự động nếu bạn thay đổi cấu hình giường bên dưới.</div>
            </div>

            <div class="mb-3">
                <label>Tiện nghi</label>
                <div class="d-flex flex-wrap">
                    @foreach ($tienNghis as $tn)
                        <div class="form-check me-3">
                            <input type="checkbox" name="tien_nghi[]" value="{{ $tn->id }}" class="form-check-input"
                                {{ in_array($tn->id, old('tien_nghi', $loaiphong->tienNghis->pluck('id')->toArray())) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ $tn->ten }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <hr>
            <h5>Bed types (Cấu hình giường cho loại phòng)</h5>
            <p class="text-muted small">Điền số lượng cho mỗi loại giường. Nếu để 0 thì loại giường đó sẽ không được gắn vào
                loại phòng.</p>

            @foreach ($bedTypes as $bt)
                @php
                    $pivot = $loaiphong->bedTypes->firstWhere('id', $bt->id);
                    $qty = old("bed_types.$bt->id.quantity", $pivot ? $pivot->pivot->quantity : 0);
                    $price = old("bed_types.$bt->id.price", $pivot ? $pivot->pivot->price : $bt->price);
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
                <button type="submit" class="btn btn-primary">Cập nhật</button>
                <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection
