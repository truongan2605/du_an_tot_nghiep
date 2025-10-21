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

        <form action="{{ route('admin.phong.store') }}" method="POST" enctype="multipart/form-data" id="phongCreateForm">
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
                        @php
                            $bedtypes = $lp->bedTypes
                                ->map(function ($b) {
                                    return [
                                        'id' => $b->id,
                                        'name' => $b->name,
                                        'capacity' => (int) $b->capacity,
                                        'price' => $b->pivot->price ?? ($b->price ?? 0),
                                        'quantity' => (int) ($b->pivot->quantity ?? 0),
                                    ];
                                })
                                ->values();
                        @endphp

                        <option value="{{ $lp->id }}" data-gia="{{ $lp->gia_mac_dinh ?? 0 }}"
                            data-suc_chua="{{ $lp->suc_chua ?? '' }}" data-so_giuong="{{ $lp->so_giuong ?? '' }}"
                            data-amenities='@json($lp->tienNghis->pluck('id'))'
                            data-bedtypes='{{ json_encode($bedtypes, JSON_UNESCAPED_UNICODE) }}'
                            {{ old('loai_phong_id') == $lp->id ? 'selected' : '' }}>
                            {{ $lp->ten }} — {{ number_format($lp->gia_mac_dinh ?? 0, 0, ',', '.') }} đ
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Sức chứa, số giường, giá mặc định và cấu hình giường sẽ lấy theo loại
                    phòng.</small>
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
                    <label>Giá mặc định</label>
                    <input type="number" id="gia_input" name="gia_mac_dinh" class="form-control"
                        value="{{ old('gia_mac_dinh') }}" readonly>
                    <small class="form-text text-muted">Giá loại phòng; tổng thực tế hiển thị dưới.</small>
                </div>
            </div>

            <h6 class="mt-3">Cấu hình giường</h6>
            <div id="bed_types_container" class="mb-3">
                <em>Chọn loại phòng để xem cấu hình giường.</em>
            </div>

            <h6 class="mt-3">Tiện nghi</h6>
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
            const bedTypesContainer = document.getElementById('bed_types_container');

            function parseNumber(v) {
                if (v === null || v === undefined || v === '') return 0;
                return Number(v);
            }

            function renderBedTypes(list) {
                if (!bedTypesContainer) return;
                if (!Array.isArray(list) || list.length === 0) {
                    bedTypesContainer.innerHTML = '<em>Không có cấu hình giường cho loại phòng này.</em>';
                    return;
                }
                let html =
                    '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Loại giường</th><th class="text-center">Số lượng</th><th class="text-center">Sức chứa/giường</th><th class="text-end">Giá/giường</th></tr></thead><tbody>';
                list.forEach(b => {
                    html += `<tr>
                        <td>${b.name}</td>
                        <td class="text-center">${b.quantity}</td>
                        <td class="text-center">${b.capacity}</td>
                        <td class="text-end">${new Intl.NumberFormat('vi-VN').format(Math.round(b.price || 0))} đ</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                bedTypesContainer.innerHTML = html;
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
                let sumAmenities = 0;
                document.querySelectorAll('.tienNghiCheckbox:checked').forEach(cb => {
                    sumAmenities += parseNumber(cb.dataset.price || 0);
                });

                // tính tiền giường từ option hiện tại (data-bedtypes) nếu có
                let bedSum = 0;
                if (opt) {
                    const bedtypesJson = opt.dataset.bedtypes ?? '[]';
                    try {
                        const bedlist = JSON.parse(bedtypesJson);
                        if (Array.isArray(bedlist)) {
                            bedlist.forEach(b => {
                                const qty = parseNumber(b.quantity || 0);
                                const price = parseNumber(b.price || 0);
                                bedSum += qty * price;
                            });
                        }
                    } catch (e) {
                        // ignore
                    }
                }

                const total = parseNumber(base) + sumAmenities + bedSum;
                totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(Math.round(total)) + ' đ';
                if (gia_input) gia_input.value = Math.round(parseNumber(base));
            }

            function fillFromSelectedOption(opt) {
                if (!opt) {
                    suc_chua_input.value = '';
                    so_giuong_input.value = '';
                    gia_input.value = '';
                    renderBedTypes([]);
                    resetAmenitiesChecks();
                    updateTotal();
                    return;
                }
                const gia = opt.dataset.gia ?? 0;
                const suc = opt.dataset.suc_chua ?? '';
                const giuong = opt.dataset.so_giuong ?? '';
                const amenitiesJson = opt.dataset.amenities ?? '[]';
                const bedtypesJson = opt.dataset.bedtypes ?? '[]';

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

                let bedlist = [];
                try {
                    bedlist = JSON.parse(bedtypesJson);
                } catch (e) {
                    bedlist = [];
                }
                renderBedTypes(bedlist);

                updateTotal();
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
                fillFromSelectedOption(opt);
            });
        })();
    </script>
@endsection
