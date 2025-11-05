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
                    <label>Giá mặc định của loại phòng</label>
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

            <div class="mb-2">
                <div id="amenities_checkbox_list_edit" class="d-flex flex-wrap gap-2">
                    @php
                        $roomAmenityIds = $phong->tienNghis->pluck('id')->toArray();
                        $typeAmenityIds = $phong->loaiPhong?->tienNghis->pluck('id')->toArray() ?? [];
                    @endphp

                    @foreach ($tienNghis as $tn)
                        @php
                            $inType = in_array($tn->id, $typeAmenityIds);
                            $inRoom = in_array($tn->id, $roomAmenityIds);
                        @endphp
                        <label
                            class="form-check form-check-inline amenity-label-edit {{ $inType ? 'in-type' : ($inRoom ? 'extra' : 'not-in-type') }}"
                            data-amenity-id="{{ $tn->id }}">
                            <input type="checkbox" class="form-check-input amenity-checkbox-edit"
                                value="{{ $tn->id }}" id="tienNghi_edit_{{ $tn->id }}"
                                {{ $inType || $inRoom ? 'checked' : '' }} disabled>
                            <span class="form-check-label ms-1">
                                <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                                <small class="d-inline-block ms-1">({{ number_format($tn->gia, 0, ',', '.') }} đ)</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            @php
                $allCheckedServer = $allChecked ?? $roomAmenityIds;
            @endphp

            <div id="hidden_amenities_container_edit">
                @if (old('tien_nghi'))
                    @foreach (old('tien_nghi') as $id)
                        <input type="hidden" name="tien_nghi[]" value="{{ $id }}"
                            class="amenity-hidden-input-edit">
                    @endforeach
                @else
                    @foreach ($allCheckedServer as $id)
                        <input type="hidden" name="tien_nghi[]" value="{{ $id }}"
                            class="amenity-hidden-input-edit">
                    @endforeach
                @endif
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

@section('styles')
    <style>
        .amenity-label-edit.not-in-type {
            opacity: 0.55;
            filter: grayscale(40%);
        }

        .amenity-label-edit.in-type {
            font-weight: 600;
            background: rgba(13, 110, 253, 0.06);
            padding: .25rem .5rem;
            border-radius: .5rem;
        }

        .amenity-label-edit.extra {
            font-weight: 600;
            background: rgba(255, 193, 7, 0.06);
            padding: .25rem .5rem;
            border-radius: .5rem;
        }
    </style>
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
            const editSelect = document.getElementById('loai_phong_select_edit');
            const giaInput = document.getElementById('gia_input_edit');
            const bedTypesContainer = document.getElementById('bed_types_container_edit');
            const amenitiesDisplay = document.getElementById('amenities_display_edit');
            const form = document.getElementById('phongEditForm');
            const totalDisp = document.getElementById('total_display');

            const initialRoomAmenityIds = @json($roomAmenityIds || []);

            // helper
            function toInt(v) {
                const n = parseInt(v, 10);
                return isNaN(n) ? 0 : n;
            }

            function toFloat(v) {
                if (v === null || v === undefined) return 0;
                // remove commas or non-number chars
                const s = String(v).replace(/[^\d\.\-]/g, '');
                const f = parseFloat(s);
                return isNaN(f) ? 0 : f;
            }

            function parseAmenityIdsFromOption(opt) {
                if (!opt) return [];
                try {
                    const raw = opt.dataset.amenities ?? '[]';
                    const arr = JSON.parse(raw);
                    if (!Array.isArray(arr)) return [];
                    return arr.map(x => toInt(x)).filter(x => x > 0);
                } catch (e) {
                    return [];
                }
            }

            function parseBedlistFromOption(opt) {
                if (!opt) return [];
                try {
                    const raw = opt.dataset.bedtypes ?? '[]';
                    const arr = JSON.parse(raw);
                    return Array.isArray(arr) ? arr : [];
                } catch (e) {
                    return [];
                }
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

            function setHiddenAmenityInputs(ids) {
                // remove old
                document.querySelectorAll('.amenity-hidden-input-edit').forEach(n => n.remove());
                const target = form || document.body;
                (ids || []).forEach(id => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'tien_nghi[]';
                    inp.value = id;
                    inp.className = 'amenity-hidden-input-edit';
                    target.appendChild(inp);
                });
            }

            function getHiddenAmenityIds() {
                return Array.from(document.querySelectorAll('.amenity-hidden-input-edit'))
                    .map(i => toInt(i.value))
                    .filter(n => n > 0);
            }

            function computeBedSum(opt) {
                const list = parseBedlistFromOption(opt);
                let s = 0;
                (list || []).forEach(b => {
                    const qty = toInt(b.quantity || 0);
                    const price = toFloat(b.price || 0);
                    s += qty * price;
                });
                return s;
            }

            function badgePriceById(id) {
                if (!amenitiesDisplay) return 0;
                const sel = amenitiesDisplay.querySelector('[data-id="' + id + '"]');
                if (!sel) return 0;
                return toFloat(sel.dataset.price || sel.getAttribute('data-price') || 0);
            }

            function unionArrays(a, b) {
                return Array.from(new Set([...(a || []), ...(b || [])])).map(x => toInt(x)).filter(x => x > 0);
            }

            function updateTotalDisplay() {
                try {
                    const opt = editSelect ? editSelect.options[editSelect.selectedIndex] : null;
                    const base = opt ? toFloat(opt.dataset.gia || 0) : 0;
                    if (giaInput && opt) giaInput.value = Math.round(base);

                    const bedSum = computeBedSum(opt);

                    const typeIds = parseAmenityIdsFromOption(opt); // from loai_phong
                    const hiddenIds = getHiddenAmenityIds(); // from hidden inputs
                    const unionIds = unionArrays(typeIds, unionArrays(hiddenIds, initialRoomAmenityIds));

                    console.debug('DEBUG amenity calc:', {
                        typeIds,
                        hiddenIds,
                        initialRoomAmenityIds,
                        unionIds
                    });

                    let amenitySum = 0;
                    unionIds.forEach(id => {
                        const p = badgePriceById(id);
                        amenitySum += p;
                    });

                    const total = Math.round(base + bedSum + amenitySum);
                    if (totalDisp) totalDisp.innerText = new Intl.NumberFormat('vi-VN').format(total) + ' đ';

                    if (amenitiesDisplay) {
                        const badges = amenitiesDisplay.querySelectorAll('span[data-id]');
                        badges.forEach(b => {
                            const id = toInt(b.dataset.id || b.getAttribute('data-id'));
                            b.classList.remove('bg-primary', 'bg-light', 'text-muted');
                            if (typeIds.includes(id)) {
                                b.classList.add('bg-primary');
                            } else if (unionIds.includes(id)) {
                                b.classList.add('bg-light');
                            } else {
                                b.classList.add('bg-light', 'text-muted');
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error updateTotalDisplay:', e);
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                try {
                    const opt0 = editSelect ? editSelect.options[editSelect.selectedIndex] : null;
                    renderBedTypes(parseBedlistFromOption(opt0));

                    const existHidden = getHiddenAmenityIds();
                    if (!existHidden.length) {
                        setHiddenAmenityInputs(initialRoomAmenityIds);
                    }

                    updateTotalDisplay();

                    if (editSelect) {
                        editSelect.addEventListener('change', function() {
                            const opt = editSelect.options[editSelect.selectedIndex];
                            const typeIds = parseAmenityIdsFromOption(opt);
                            renderBedTypes(parseBedlistFromOption(opt));
                            // overwrite hidden inputs with option amenities
                            setHiddenAmenityInputs(typeIds);
                            updateTotalDisplay();
                        });
                    }

                    const observer = new MutationObserver(() => {
                        updateTotalDisplay();
                    });
                    observer.observe(form || document.body, {
                        childList: true,
                        subtree: true
                    });

                    window.__roomAmenityDebug = {
                        updateTotalDisplay,
                        getHiddenAmenityIds,
                        parseAmenityIdsFromOption,
                    };
                } catch (e) {
                    console.error('INIT error (amenities script):', e);
                }
            });
        })();
    </script>
@endsection
