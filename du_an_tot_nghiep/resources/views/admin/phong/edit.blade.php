@extends('layouts.admin')

@section('title', 'Sửa phòng')

@section('content')
    <div class="container mt-4">
        <h3 class="mb-4">Sửa phòng</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
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
                <input type="text" name="ma_phong" class="form-control" value="{{ old('ma_phong', $phong->ma_phong) }}"
                    required>
            </div>

            <div class="mb-3">
                <label>Tên phòng</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $phong->name) }}"
                    placeholder="VD: Deluxe-1">
            </div>

            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="mo_ta" class="form-control" rows="3" placeholder="Mô tả ngắn về phòng...">{{ old('mo_ta', $phong->mo_ta) }}</textarea>
            </div>


            <div class="mb-3">
                <label>Loại phòng</label>
                <select name="loai_phong_id" class="form-select" required id="loai_phong_select_edit">
                    @foreach ($loaiPhongs as $lp)
                        <option value="{{ $lp->id }}" data-gia="{{ $lp->gia_mac_dinh ?? 0 }}"
                            data-suc_chua="{{ $lp->suc_chua ?? '' }}" data-so_giuong="{{ $lp->so_giuong ?? '' }}"
                            data-amenities='@json($lp->tienNghis->pluck('id'))' data-active="{{ $lp->active ? '1' : '0' }}"
                            {{ $phong->loai_phong_id == $lp->id ? 'selected' : '' }}>
                            {{ $lp->ten }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label>Tầng</label>
                <select name="tang_id" class="form-select" required>
                    @foreach ($tangs as $t)
                        <option value="{{ $t->id }}" {{ $phong->tang_id == $t->id ? 'selected' : '' }}>
                            {{ $t->ten }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row g-2">
                <div class="col-md-4 mb-3">
                    <label>Sức chứa</label>
                    <input type="number" name="suc_chua" id="suc_chua_edit" class="form-control"
                        value="{{ old('suc_chua', $phong->suc_chua) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số giường</label>
                    <input type="number" name="so_giuong" id="so_giuong_edit" class="form-control"
                        value="{{ old('so_giuong', $phong->so_giuong) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giá (có thể override bằng nút bên)</label>
                    <input type="number" name="gia_mac_dinh" id="gia_input_edit" class="form-control"
                        value="{{ old('gia_mac_dinh', $phong->gia_mac_dinh) }}" readonly>
                </div>
                <div class="mb-3" style="margin-left: 66% ">
                    <button type="button" id="toggle_override_btn" class="btn btn-outline-primary btn-sm">
                        Chỉnh giá thủ công
                    </button>
                    <input type="hidden" name="override_price" id="override_price" value="{{ old('override_price', 0) }}">
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label>Trạng thái</label>
                <select name="trang_thai" class="form-select" required>
                    @php
                        $states = [
                            'khong_su_dung' => 'Không sử dụng',
                            'trong' => 'Trống',
                            'dang_o' => 'Đang ở',
                            'bao_tri' => 'Bảo trì',
                        ];
                    @endphp
                    @foreach ($states as $key => $label)
                        <option value="{{ $key }}"
                            {{ old('trang_thai', $phong->trang_thai) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <h6 class="mt-3">Tiện nghi</h6>
            <div class="d-flex flex-wrap gap-2 mb-2">
                @php
                    $tienNghiLoaiPhong = $phong->loaiPhong->tienNghis->pluck('id')->toArray();
                    $tienNghiPhong = $phong->tienNghis->pluck('id')->toArray();
                    $allChecked = array_unique(array_merge($tienNghiLoaiPhong, $tienNghiPhong));
                @endphp

                @foreach ($tienNghis as $tn)
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="tien_nghi[]" value="{{ $tn->id }}"
                            class="form-check-input tienNghiCheckbox" data-price="{{ $tn->gia }}"
                            id="tienNghi_edit_{{ $tn->id }}" {{ in_array($tn->id, $allChecked) ? 'checked' : '' }}>
                        <label class="form-check-label" for="tienNghi_edit_{{ $tn->id }}">
                            <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                            ({{ number_format($tn->gia, 0, ',', '.') }} đ)
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="mb-2">
                <strong>Tổng tạm tính: </strong>
                <span id="total_display">{{ number_format($phong->tong_gia, 0, ',', '.') }} đ</span>
            </div>

            <div class="mb-3">
                <label class="form-label">Ảnh hiện tại</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach ($phong->images as $img)
                        <div class="border rounded p-1" style="max-width:200px;">
                            <img src="{{ asset('storage/' . $img->image_path) }}" class="img-fluid"
                                style="object-fit: contain; width:100%; height:auto;">
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

@section('scripts')
    <script>
        (function() {
            const loaiSelect = document.getElementById('loai_phong_select_edit');
            const totalDisplay = document.getElementById('total_display');
            const giaInput = document.getElementById('gia_input_edit');
            const overrideInput = document.getElementById('override_price');
            const toggleBtn = document.getElementById('toggle_override_btn');
            const trangThaiSelect = document.querySelector('select[name="trang_thai"]');

            function parseNumber(v) {
                if (v === null || v === undefined || v === '') return 0;
                return Number(v);
            }

            function isSelectedLoaiActive() {
                if (!loaiSelect) return true;
                const opt = loaiSelect.querySelector('option:checked');
                if (!opt) return true;
                return opt.dataset.active === undefined || opt.dataset.active === '1';
            }

            function setTrangThaiDisabled(disabled) {
                if (!trangThaiSelect) return;
                trangThaiSelect.disabled = disabled;
                if (disabled) {
                    trangThaiSelect.setAttribute('data-warn',
                        'Loại phòng đang bị vô hiệu — không thể thay đổi trạng thái');
                } else {
                    trangThaiSelect.removeAttribute('data-warn');
                }
            }

            function handleLoaiChange() {
                const active = isSelectedLoaiActive();
                setTrangThaiDisabled(!active);

                updateTotal();
            }

            function getBaseFromSelected() {
                if (!loaiSelect) return 0;
                const opt = loaiSelect.querySelector('option:checked');
                return opt ? parseNumber(opt.dataset.gia || 0) : 0;
            }

            function updateTotal() {
                const base = getBaseFromSelected();
                let sum = 0;
                document.querySelectorAll('.tienNghiCheckbox:checked').forEach(cb => {
                    sum += parseNumber(cb.dataset.price || 0);
                });
                const total = base + sum;
                if (totalDisplay) {
                    totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + ' đ';
                }

                const isOverride = overrideInput && overrideInput.value === '1';
                if (!isOverride && giaInput) {
                    giaInput.value = Math.round(total);
                }
            }

            function setOverride(on) {
                if (!overrideInput || !toggleBtn || !giaInput) return;
                if (on) {
                    overrideInput.value = '1';
                    toggleBtn.classList.remove('btn-outline-primary');
                    toggleBtn.classList.add('btn-outline-danger');
                    toggleBtn.innerText = 'Hủy chỉnh giá';
                    giaInput.readOnly = false;
                    const displayed = totalDisplay ? totalDisplay.innerText.replace(/[^\d]/g, '') : '';
                    if (displayed !== '' && Number(giaInput.value) !== Number(displayed)) {
                        giaInput.value = Number(displayed) || giaInput.value;
                    }
                    giaInput.focus();
                    giaInput.select();
                } else {
                    overrideInput.value = '0';
                    toggleBtn.classList.remove('btn-outline-danger');
                    toggleBtn.classList.add('btn-outline-primary');
                    toggleBtn.innerText = 'Chỉnh giá thủ công';
                    giaInput.readOnly = true;
                    updateTotal();
                }
            }

            document.querySelectorAll('.tienNghiCheckbox').forEach(cb => cb.addEventListener('change', updateTotal));

            if (loaiSelect) {
                loaiSelect.addEventListener('change', function() {
                    const opt = loaiSelect.querySelector('option:checked');
                    if (!opt) {
                        updateTotal();
                        return;
                    }

                    const amenitiesJson = opt.dataset.amenities ?? '[]';
                    let ids = [];
                    try {
                        ids = JSON.parse(amenitiesJson);
                    } catch (e) {
                        ids = [];
                    }
                    ids.forEach(id => {
                        const cb = document.getElementById('tienNghi_edit_' + id);
                        if (cb) cb.checked = true;
                    });

                    const suc = opt.dataset.suc_chua ?? '';
                    const giuong = opt.dataset.so_giuong ?? '';
                    const sucInput = document.getElementById('suc_chua_edit');
                    const giuongInput = document.getElementById('so_giuong_edit');
                    if (sucInput && suc !== '') sucInput.value = suc;
                    if (giuongInput && giuong !== '') giuongInput.value = giuong;

                    handleLoaiChange();
                });
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isOverride = overrideInput && overrideInput.value === '1';
                    setOverride(!isOverride);
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                const isOverrideInit = (overrideInput && overrideInput.value === '1') ||
                    {{ old('override_price', 0) ? 'true' : 'false' }};
                setOverride(!!isOverrideInit);
                handleLoaiChange();
                updateTotal();
            });
        })();
    </script>
@endsection
