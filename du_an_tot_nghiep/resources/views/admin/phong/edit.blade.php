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

        <form action="{{ route('admin.phong.update', $phong->id) }}" method="POST" enctype="multipart/form-data"
            id="phongEditForm">
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
                <select name="loai_phong_id" id="loai_phong_select_edit" class="form-select" required>
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
                            data-active="{{ $lp->active ? '1' : '0' }}"
                            {{ $phong->loai_phong_id == $lp->id ? 'selected' : '' }}>
                            {{ $lp->ten }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Cấu hình giường, sức chứa và số giường tuân theo loại phòng.</small>
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
                        value="{{ old('suc_chua', $phong->suc_chua) }}" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Số giường</label>
                    <input type="number" name="so_giuong" id="so_giuong_edit" class="form-control"
                        value="{{ old('so_giuong', $phong->so_giuong) }}" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Giá (Loại)</label>
                    <input type="number" id="gia_input_edit" class="form-control"
                        value="{{ old('gia_mac_dinh', $phong->gia_mac_dinh) }}" readonly>
                </div>
            </div>

            <div id="bed_types_container_edit" class="mb-3"></div>

            <div class="mb-3">
                <label>Trạng thái</label>
                <select name="trang_thai" class="form-select" required id="trang_thai_select">
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
    @php
        $roomBedList = $phong->bedTypes
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'capacity' => (int) $b->capacity,
                    'price' => (float) ($b->pivot->price ?? ($b->price ?? 0)),
                    'quantity' => (int) ($b->pivot->quantity ?? 0),
                ];
            })
            ->values()
            ->toArray();

        $roomAmenityIds = $phong->tienNghis->pluck('id')->toArray();
    @endphp

    <script>
        (function() {
            const loaiSelect = document.getElementById('loai_phong_select_edit');
            const totalDisplay = document.getElementById('total_display');
            const giaInput = document.getElementById('gia_input_edit');
            const sucInput = document.getElementById('suc_chua_edit');
            const giuongInput = document.getElementById('so_giuong_edit');
            const bedTypesContainer = document.getElementById('bed_types_container_edit');
            const trangThaiSelect = document.getElementById('trang_thai_select');

            const roomBedList = @json($roomBedList);
            const roomAmenityIds = @json($roomAmenityIds);
            const currentRoomLoaiId = @json((int) $phong->loai_phong_id);

            let currentBedList = Array.isArray(roomBedList) ? roomBedList.slice() : [];

            function parseNumber(v) {
                if (v === null || v === undefined || v === '') return 0;
                return Number(v);
            }

            function renderBedTypes(list, container) {
                if (!container) return;
                if (!Array.isArray(list) || list.length === 0) {
                    container.innerHTML = '<em>Không có cấu hình giường cho loại phòng này.</em>';
                    currentBedList = [];
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
                container.innerHTML = html;
                currentBedList = Array.isArray(list) ? list.map(x => ({
                    id: x.id,
                    name: x.name,
                    capacity: parseInt(x.capacity || 0),
                    price: parseFloat(x.price || 0),
                    quantity: parseInt(x.quantity || 0)
                })) : [];
            }

            function computeBedTotal() {
                let s = 0;
                currentBedList.forEach(b => {
                    const qty = parseInt(b.quantity || 0);
                    const p = parseFloat(b.price || 0);
                    if (qty > 0 && p) s += qty * p;
                });
                return s;
            }

            function updateTotal() {
                const opt = loaiSelect.querySelector('option:checked');
                const base = opt ? parseNumber(opt.dataset.gia) : 0;

                let amenitySum = 0;
                document.querySelectorAll('.tienNghiCheckbox:checked').forEach(cb => {
                    amenitySum += parseNumber(cb.dataset.price || 0);
                });

                const bedSum = computeBedTotal();

                const total = base + amenitySum + bedSum;

                if (totalDisplay) totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(Math.round(total)) +
                    ' đ';
                if (giaInput) giaInput.value = Math.round(base);
            }

            function setAmenityCheckboxesByIds(ids, keepRoomExtras = true) {
                document.querySelectorAll('.tienNghiCheckbox').forEach(cb => cb.checked = false);
                if (Array.isArray(ids)) {
                    ids.forEach(id => {
                        const cb = document.getElementById('tienNghi_edit_' + id);
                        if (cb) cb.checked = true;
                    });
                }
                if (keepRoomExtras && Array.isArray(roomAmenityIds)) {
                    roomAmenityIds.forEach(id => {
                        const cb = document.getElementById('tienNghi_edit_' + id);
                        if (cb) cb.checked = true;
                    });
                }
            }

            function fillFromOption(opt) {
                if (!opt) return;
                const gia = opt.dataset.gia ?? 0;
                const suc = opt.dataset.suc_chua ?? '';
                const giuong = opt.dataset.so_giuong ?? '';
                const amenitiesJson = opt.dataset.amenities ?? '[]';
                const bedtypesJson = opt.dataset.bedtypes ?? '[]';

                giaInput.value = parseNumber(gia);
                sucInput.value = suc !== '' ? parseNumber(suc) : '';
                giuongInput.value = giuong !== '' ? parseNumber(giuong) : '';

                let ids = [];
                try {
                    ids = JSON.parse(amenitiesJson);
                } catch (e) {
                    ids = [];
                }

                let bedlist = [];
                try {
                    bedlist = JSON.parse(bedtypesJson);
                } catch (e) {
                    bedlist = [];
                }

                const selectedLoaiId = parseInt(opt.value);
                if (selectedLoaiId === currentRoomLoaiId && Array.isArray(roomBedList) && roomBedList.length > 0) {
                    renderBedTypes(roomBedList, bedTypesContainer);
                } else {
                    renderBedTypes(bedlist, bedTypesContainer);
                }

                setAmenityCheckboxesByIds(ids, true);

                updateTotal();
            }

            loaiSelect.addEventListener('change', function() {
                const opt = loaiSelect.querySelector('option:checked');
                if (!opt) {
                    renderBedTypes([], bedTypesContainer);
                    updateTotal();
                    return;
                }
                fillFromOption(opt);

                const active = opt.dataset.active === undefined || opt.dataset.active === '1';
                trangThaiSelect.disabled = !active;
            });

            document.querySelectorAll('.tienNghiCheckbox').forEach(cb => cb.addEventListener('change', updateTotal));

            document.addEventListener('DOMContentLoaded', function() {
                const opt = loaiSelect.querySelector('option:checked');
                if (opt) {
                    fillFromOption(opt);
                    const active = opt ? (opt.dataset.active === undefined || opt.dataset.active === '1') :
                        true;
                    trangThaiSelect.disabled = !active;
                } else {
                    renderBedTypes(roomBedList, bedTypesContainer);
                    setAmenityCheckboxesByIds([], true);
                    updateTotal();
                }
            });
        })();
    </script>
@endsection
