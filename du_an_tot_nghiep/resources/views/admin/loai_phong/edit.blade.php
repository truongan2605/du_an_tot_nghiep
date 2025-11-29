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
                <label>Giá mặc định (VND / đêm)</label>
                <input type="number" step="0.01" name="gia_mac_dinh" class="form-control"
                    value="{{ old('gia_mac_dinh', $loaiphong->gia_mac_dinh) }}" required>
            </div>

            <div class="mb-3" hidden>
                <label>Số lượng thực tế</label>
                <input type="number" name="so_luong_thuc_te" class="form-control"
                    value="{{ old('so_luong_thuc_te', $loaiphong->so_luong_thuc_te) }}">
                <div class="form-text">Sẽ được cập nhật tự động nếu bạn thay đổi cấu hình giường bên dưới.</div>
            </div>

            {{-- Tiện nghi --}}
            <div class="mb-3">
                <label class="form-label">Dịch vụ</label>
                <div class="card">
                    <div class="card-body" style="max-height:260px; overflow-y:auto;">
                        @php
                            $selectedTienNghi = old(
                                'tien_nghi_ids',
                                isset($loaiphong) ? $loaiphong->tienNghis->pluck('id')->toArray() : [],
                            );
                        @endphp

                        @if ($tienNghis->count() > 0)
                            <div class="row">
                                @foreach ($tienNghis as $tn)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="tien_nghi_ids[]"
                                                id="tn{{ $tn->id }}" value="{{ $tn->id }}"
                                                {{ in_array($tn->id, (array) $selectedTienNghi) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="tn{{ $tn->id }}">
                                                <strong>{{ $tn->ten }}</strong>
                                                @if (isset($tn->gia))
                                                    <small
                                                        class="text-muted ms-2">({{ number_format($tn->gia, 0, ',', '.') }}
                                                        đ)</small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">Chưa có dịch vụ nào.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Vật dụng  --}}
            <div class="mb-3">
                <label class="form-label">Vật dụng (Đồ dùng)</label>
                <div class="card">
                    <div class="card-body" style="max-height:260px; overflow-y:auto;">
                        @php
                            $selectedVatDungs = old(
                                'vat_dung_ids',
                                isset($loaiphong) ? $loaiphong->vatDungs->pluck('id')->toArray() : [],
                            );
                        @endphp

                        @if ($vatDungs->count() > 0)
                            <div class="row">
                                @foreach ($vatDungs as $vd)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="vat_dung_ids[]"
                                                id="vd{{ $vd->id }}" value="{{ $vd->id }}"
                                                {{ in_array($vd->id, (array) $selectedVatDungs) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="vd{{ $vd->id }}">
                                                <strong>{{ $vd->ten }}</strong>
                                                @if (isset($vd->gia))
                                                    <small class="text-muted ms-2">({{ number_format($vd->gia, 0, ',', '.') }}
                                                        đ)</small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">Chưa có vật dụng (đồ dùng).</p>
                        @endif
                    </div>
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
                        <div class="small text-muted">số lượng: {{ $bt->capacity }} / giá mặc định:
                            {{ number_format($bt->price, 0, ',', '.') }} đ</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Chất lượng</label>
                        <input type="number" name="bed_types[{{ $bt->id }}][quantity]" min="0"
                            class="form-control" value="{{ $qty }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">Giá mỗi giường (ghi đè tùy chọn)</label>
                        <input type="number" step="0.01" name="bed_types[{{ $bt->id }}][price]"
                            class="form-control" value="{{ $price }}">
                    </div>
                </div>
            @endforeach
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Hủy</a>
    </div>
    </form>
    </div>
@endsection
