@extends('layouts.admin')

@section('title', 'Thêm phòng')
@section('content')
    <div class="container">
        <h3>Thêm phòng</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.phong.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label>Mã phòng</label>
                <input type="text" name="ma_phong" class="form-control" value="{{ old('ma_phong') }}" required>
            </div>

            <div class="mb-3">
                <label>Tên phòng</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                    placeholder="VD: Deluxe-1">
            </div>

            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn về phòng...">{{ old('mo_ta') }}</textarea>
            </div>

            <div class="mb-3">
                <label>Loại phòng</label>
                <select id="loai_phong_select" name="loai_phong_id" class="form-select" required style="color:black;">
                    <option value="">-- Chọn --</option>
                    @foreach ($loaiPhongs as $lp)
                        <option value="{{ $lp->id }}" data-gia="{{ $lp->gia_mac_dinh ?? 0 }}"
                            data-suc_chua="{{ $lp->suc_chua ?? '' }}" data-so_giuong="{{ $lp->so_giuong ?? '' }}"
                            data-amenities='@json($lp->tienNghis->pluck('id'))'
                            {{ old('loai_phong_id') == $lp->id ? 'selected' : '' }}>
                            {{ $lp->ten }} — {{ number_format($lp->gia_mac_dinh ?? 0, 0, ',', '.') }} đ
                        </option>
                    @endforeach

                </select>
                <small class="text-muted">Sức chứa, số giường, giá mặc định sẽ lấy từ loại phòng.</small>
            </div>

            <div class="mb-3">
                <label>Tầng</label>
                <select name="tang_id" class="form-select" required>
                    <option value="">-- Chọn --</option>
                    @foreach ($tangs as $t)
                        <option value="{{ $t->id }}" {{ old('tang_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->ten }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label>Sức chứa</label>
                    <input type="number" name="suc_chua" id="suc_chua_input" class="form-control"
                        value="{{ old('suc_chua') }}" required readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số giường</label>
                    <input type="number" name="so_giuong" id="so_giuong_input" class="form-control"
                        value="{{ old('so_giuong') }}" required readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giá mặc định (Loại)</label>
                    <input type="number" id="gia_input" class="form-control" value="{{ old('gia_mac_dinh') }}" readonly>
                    <small class="form-text text-muted">Giá loại phòng; tổng thực tế ở dưới.</small>
                </div>
            </div>

            <h6 class="mt-3">Dịch vụ</h6>
            <div class="d-flex flex-wrap gap-2 mb-3" id="tienNghiList">
                @foreach ($tienNghis as $tn)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="tien_nghi[]" value="{{ $tn->id }}"
                            class="form-check-input tienNghiCheckbox" id="tienNghi_{{ $tn->id }}"
                            data-price="{{ $tn->gia }}"
                            {{ in_array($tn->id, old('tien_nghi', [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="tienNghi_{{ $tn->id }}">
                            <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                            ({{ number_format($tn->gia, 0, ',', '.') }} đ)
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mb-2">
                <strong>Tổng tạm tính: </strong>
                <span id="total_display">{{ number_format(old('gia_mac_dinh', 0), 0, ',', '.') }} đ</span>
            </div>

            <div class="mb-3">
                <label>Ảnh phòng (chọn nhiều)</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>

            <button class="btn btn-success">Lưu</button>
            <a href="{{ route('admin.phong.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const select = document.getElementById('loai_phong_select');
            const suc_chua_input = document.getElementById('suc_chua_input');
            const so_giuong_input = document.getElementById('so_giuong_input');
            const gia_input = document.getElementById('gia_input');
            const totalDisplay = document.getElementById('total_display');

            function parseNumber(v) {
                if (v === null || v === undefined || v === '') return 0;
                return Number(v);
            }

            function resetAmenitiesChecks() {
                document.querySelectorAll('.tienNghiCheckbox').forEach(cb => cb.checked = false);
            }

            function tickAmenitiesByIds(ids) {
                resetAmenitiesChecks();
                if (!Array.isArray(ids)) return;
                ids.forEach(id => {
                    const cb = document.getElementById('tienNghi_' + id);
                    if (cb) cb.checked = true;
                });
                updateTotal();
            }

            function updateTotal() {
                const opt = select.querySelector('option:checked');
                const base = opt ? parseNumber(opt.dataset.gia) : 0;
                let sum = 0;
                document.querySelectorAll('.tienNghiCheckbox:checked').forEach(cb => {
                    sum += parseNumber(cb.dataset.price || 0);
                });
                const total = base + sum;
                totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + ' đ';
            }

            function fillFromSelectedOption(opt) {
                if (!opt) return;
                const gia = opt.dataset.gia ?? 0;
                const suc = opt.dataset.suc_chua ?? '';
                const giuong = opt.dataset.so_giuong ?? '';
                const amenitiesJson = opt.dataset.amenities ?? '[]';

                gia_input.value = parseNumber(gia);
                suc_chua_input.value = suc !== '' ? parseNumber(suc) : '';
                so_giuong_input.value = giuong !== '' ? parseNumber(giuong) : '';

                let ids = [];
                try {
                    ids = JSON.parse(amenitiesJson);
                } catch (e) {
                    ids = [];
                }

                tickAmenitiesByIds(ids);
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.tienNghiCheckbox').forEach(cb => {
                    cb.addEventListener('change', updateTotal);
                });

                const selectedOpt = select.querySelector('option:checked');
                if (selectedOpt && selectedOpt.value) {
                    fillFromSelectedOption(selectedOpt);
                } else {
                    updateTotal();
                }
            });

            select.addEventListener('change', function() {
                const val = this.value;
                const opt = this.querySelector('option[value="' + val + '"]');
                if (!opt) {
                    gia_input.value = '';
                    suc_chua_input.value = '';
                    so_giuong_input.value = '';
                    resetAmenitiesChecks();
                    updateTotal();
                    return;
                }
                fillFromSelectedOption(opt);
            });
        })();
    </script>
@endsection
