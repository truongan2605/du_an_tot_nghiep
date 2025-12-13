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
                <input type="text" id="gia_mac_dinh" name="gia_mac_dinh" class="form-control"
                    value="{{ old('gia_mac_dinh') ?? number_format($loaiphong->gia_mac_dinh ?? 0, 0, ',', '.') }}"
                    oninput="formatMoney(this)">
            </div>

            <div class="mb-3" hidden>
                <label>Số lượng thực tế</label>
                <input type="number" name="so_luong_thuc_te" class="form-control"
                    value="{{ old('so_luong_thuc_te', $loaiphong->so_luong_thuc_te) }}">
                <div class="form-text">Sẽ được cập nhật tự động nếu bạn thay đổi cấu hình giường bên dưới.</div>
            </div>

            {{-- Dịch vụ & Tiện nghi - GIÁ RIÊNG CHO LOẠI PHÒNG --}}
            <div class="mb-4">
                <label class="form-label fw-bold text-primary fs-5">Dịch vụ & Tiện nghi</label>
                <div class="card border shadow-sm">
                    <div class="card-body" style="max-height: 420px; overflow-y: auto;">
                        @if ($tienNghis->count() > 0)
                            <div class="row">
                                @foreach ($tienNghis as $tn)
                                    @php
                                        $checked = $loaiphong->tienNghis->contains($tn->id);
                                        $pivotPrice = $loaiphong->tienNghis->find($tn->id)?->pivot?->price ?? null;
                                    @endphp

                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input tn-checkbox" type="checkbox"
                                                name="tien_nghi_ids[]" value="{{ $tn->id }}"
                                                id="tn{{ $tn->id }}" {{ $checked ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="tn{{ $tn->id }}">
                                                {{ $tn->ten }}
                                            </label>
                                        </div>

                                        <div class="ms-4 mt-2 price-section"
                                            style="display: {{ $checked ? 'block' : 'none' }}">
                                            <small class="text-muted d-block">Giá mặc định:
                                                {{ number_format($tn->gia, 0, ',', '.') }} đ</small>
                                            <div class="input-group input-group-sm mt-1">
                                                <span class="input-group-text">Giá riêng (trống = dùng mặc định)</span>
                                                <input type="text" class="form-control text-end tn-price-input"
                                                    value="{{ $pivotPrice !== null ? number_format($pivotPrice, 0, ',', '.') : '' }}"
                                                    placeholder="{{ number_format($tn->gia, 0, ',', '.') }}"
                                                    data-default="{{ $tn->gia }}" data-id="{{ $tn->id }}"
                                                    autocomplete="off">
                                                <!-- Hidden input để Laravel nhận đúng số -->
                                                <input type="hidden" name="tien_nghi_prices[{{ $tn->id }}]"
                                                    value="{{ $pivotPrice ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">Chưa có dịch vụ nào được kích hoạt.</p>
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
                                                    <small
                                                        class="text-muted ms-2">({{ number_format($vd->gia, 0, ',', '.') }}
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
                    // Luôn hiển thị giá (ghi đè hoặc mặc định), không để trống
                    $displayPrice = number_format($price, 0, ',', '.');
                @endphp

                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <strong>{{ $bt->name }}</strong>
                        <div class="small text-muted">
                            sức chứa {{ $bt->capacity }} người / giá mặc định:
                            {{ number_format($bt->price, 0, ',', '.') }} đ
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Số lượng</label>
                        <input type="number" name="bed_types[{{ $bt->id }}][quantity]" min="0"
                            class="form-control form-control-sm" value="{{ $qty }}">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small">Giá mỗi giường (ghi đè tùy chọn)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">VND</span>
                            <input type="text" class="form-control text-end bed-price-format"
                                value="{{ $displayPrice }}" oninput="formatMoney(this, 9)"
                                data-raw-name="bed_types[{{ $bt->id }}][price]" data-default="{{ $bt->price }}">
                            <input type="hidden" name="bed_types[{{ $bt->id }}][price]"
                                value="{{ $price }}">
                        </div>
                    </div>
                </div>
            @endforeach

            <script>
                // Hàm format tiền chung – dùng cho tất cả các ô tiền trong form
                function formatMoney(input, maxDigits = 9) {
                    let v = input.value.replace(/\D/g, ''); // Chỉ giữ số

                    // Giới hạn tối đa 9 chữ số
                    if (v.length > maxDigits) {
                        v = v.substring(0, maxDigits);
                    }

                    // Nếu rỗng → dùng giá mặc định
                    if (v === '' && input.dataset.default !== undefined) {
                        v = input.dataset.default;
                    }

                    // Format đẹp: 1200000 → 1.200.000
                    input.value = v === '' ? '' : Number(v).toLocaleString('vi-VN');

                    // Cập nhật hidden input (nếu có)
                    if (input.dataset.rawName) {
                        const hidden = document.querySelector(`input[name="${input.dataset.rawName}"]`);
                        if (hidden) hidden.value = v;
                    }
                }

                // Khi trang load: format lại tất cả ô đã có giá trị (hỗ trợ old() khi validate lỗi)
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('#gia_mac_dinh, .bed-price-format, .tn-price-format').forEach(input => {
                        if (input.value.trim() !== '') {
                            formatMoney(input);
                        }
                    });
                });

                // Trước khi submit: đảm bảo tất cả đều đúng (đặc biệt khi người dùng xóa hết)
                document.querySelector('form').addEventListener('submit', function() {
                    document.querySelectorAll('#gia_mac_dinh, .bed-price-format, .tn-price-format').forEach(input => {
                        formatMoney(input);
                    });
                });

                // Fix lỗi nhảy số + xóa sai vị trí + không có chữ "đ"
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.tn-price-input').forEach(input => {
                        let isComposing = false; // Fix lỗi IME tiếng Việt

                        // Khi bắt đầu gõ tiếng Việt (nhập ư, ơ, ă...)
                        input.addEventListener('compositionstart', () => isComposing = true);
                        input.addEventListener('compositionend', () => isComposing = false);

                        input.addEventListener('input', function(e) {
                            if (isComposing) return;

                            let cursorPos = this.selectionStart;
                            let oldValue = this.value;
                            let raw = oldValue.replace(/\D/g, ''); // Chỉ giữ số

                            // Giới hạn 9 chữ số
                            if (raw.length > 9) raw = raw.substring(0, 9);

                            let newValue = raw === '' ? '' : Number(raw).toLocaleString('vi-VN');

                            this.value = newValue;

                            // Tính lại vị trí con trỏ (rất quan trọng!)
                            let digitsBeforeCursor = (oldValue.substring(0, cursorPos).match(/\d/g) || [])
                                .length;
                            let newCursorPos = 0;
                            let formattedSoFar = '';

                            for (let char of newValue) {
                                if (/\d/.test(char)) {
                                    if (digitsBeforeCursor > 0) {
                                        digitsBeforeCursor--;
                                        newCursorPos++;
                                    } else {
                                        break;
                                    }
                                }
                                newCursorPos++;
                            }

                            this.setSelectionRange(newCursorPos, newCursorPos);

                            // Cập nhật hidden input
                            const hidden = document.querySelector(
                                `input[name="tien_nghi_prices[${this.dataset.id}]"]`);
                            if (hidden) {
                                hidden.value = raw || '';
                            }
                        });

                        // Khi focus: nếu trống thì hiện placeholder, không hiện giá mặc định trong ô
                        input.addEventListener('focus', function() {
                            if (this.value === number_format(input.dataset.default, 0, ',', '.')) {
                                this.value = '';
                            }
                        });

                        // Khi rời ô: nếu trống thì để trống (không tự fill giá mặc định)
                        input.addEventListener('blur', function() {
                            if (this.value.trim() === '') {
                                const hidden = document.querySelector(
                                    `input[name="tien_nghi_prices[${this.dataset.id}]"]`);
                                if (hidden) hidden.value = '';
                            }
                        });
                    });
                });
            </script>
    </div> {{-- kết thúc container --}}

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-secondary">Hủy</a>
    </div>
    </form>
@endsection
