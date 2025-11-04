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

            <h6 class="mt-3">Tiện nghi (theo loại phòng — không thể chỉnh ở cấp phòng)</h6>
            <div class="mb-2">
                {{-- Render checkbox nhưng disabled — JS sẽ check/uncheck theo loai phòng.
                     Nút checkbox disabled nên sẽ không submit => tạo hidden inputs tương ứng --}}
                <div id="amenities_checkbox_list" class="d-flex flex-wrap gap-2">
                    @foreach ($tienNghis as $tn)
                        @php
                            // mark old selected only to keep UI when validation fails; JS will override according to selected loai
                            $oldSelected = old('tien_nghi', []);
                            $checked = in_array($tn->id, (array)$oldSelected);
                        @endphp
                        <label class="form-check form-check-inline amenity-label" data-amenity-id="{{ $tn->id }}">
                            <input type="checkbox" class="form-check-input amenity-checkbox" value="{{ $tn->id }}"
                                id="tienNghi_{{ $tn->id }}" {{ $checked ? 'checked' : '' }} disabled>
                            <span class="form-check-label">
                                <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                                <small class="d-inline-block ms-1">({{ number_format($tn->gia,0,',','.') }} đ)</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Hidden inputs container: JS sẽ fill các input[name="tien_nghi[]"] tương ứng với tiện nghi thuộc loại phòng --}}
            <div id="hidden_amenities_container">
                {{-- nếu có old('tien_nghi') (ví dụ lỗi), giữ lại để không mất dữ liệu --}}
                @if (old('tien_nghi'))
                    @foreach (old('tien_nghi') as $id)
                        <input type="hidden" name="tien_nghi[]" value="{{ $id }}" class="amenity-hidden-input-create">
                    @endforeach
                @endif
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

@section('styles')
    <style>
        /* không bắt mắt, chỉ làm mờ / bôi mờ những tiện nghi không thuộc loại phòng */
        .amenity-label.not-in-type {
            opacity: 0.55;
            filter: grayscale(40%);
        }
        .amenity-label.in-type {
            font-weight: 600;
        }
    </style>
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
            const amenitiesCheckboxList = document.getElementById('amenities_checkbox_list');
            const hiddenAmenitiesContainer = document.getElementById('hidden_amenities_container');

            // amenity price map for totals (blade provides $tienNghis)
            const amenityPrices = @json($tienNghis->pluck('gia', 'id')->toArray());

            function parseNumber(v) {
                if (v === null || v === undefined || v === '') return 0;
                const n = Number(String(v).replace(/\s+/g, ''));
                return isNaN(n) ? 0 : n;
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

            function computeBedSumFromOption(opt) {
                if (!opt) return 0;
                const bedtypesJson = opt.dataset.bedtypes ?? '[]';
                try {
                    const bedlist = JSON.parse(bedtypesJson);
                    if (Array.isArray(bedlist)) {
                        return bedlist.reduce((s, b) => {
                            const qty = parseNumber(b.quantity || 0);
                            const price = parseNumber(b.price || 0);
                            return s + qty * price;
                        }, 0);
                    }
                } catch (e) {
                    return 0;
                }
                return 0;
            }

            function computeAmenitySum(amenityIds) {
                let s = 0;
                (Array.isArray(amenityIds) ? amenityIds : []).forEach(id => {
                    s += parseNumber(amenityPrices[id] || 0);
                });
                return s;
            }

            function updateTotal() {
                const opt = select.querySelector('option:checked');
                const base = opt ? parseNumber(opt.dataset.gia) : 0;
                const bedSum = computeBedSumFromOption(opt);
                // amenity ids read from hidden inputs we created
                const hiddenEls = Array.from(document.querySelectorAll('.amenity-hidden-input-create'));
                const amenityIds = hiddenEls.map(e => parseInt(e.value, 10)).filter(n => !isNaN(n));
                const amenitySum = computeAmenitySum(amenityIds);
                const total = Math.round(base + bedSum + amenitySum);
                if (totalDisplay) totalDisplay.innerText = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
                if (gia_input) gia_input.value = Math.round(parseNumber(opt ? opt.dataset.gia : 0));
            }

            // set hidden inputs to match `amenityIds` (remove existing ones)
            function setHiddenAmenityInputs(amenityIds) {
                // clear
                hiddenAmenitiesContainer.querySelectorAll('.amenity-hidden-input-create').forEach(n => n.remove());
                const form = document.getElementById('phongCreateForm') || document.body;
                (Array.isArray(amenityIds) ? amenityIds : []).forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tien_nghi[]';
                    input.value = id;
                    input.className = 'amenity-hidden-input-create';
                    hiddenAmenitiesContainer.appendChild(input);
                });
            }

            // mark checkboxes UI: in-type => checked + in-type class; not-in-type => unchecked + not-in-type class
            function refreshAmenityCheckboxUI(inTypeIds) {
                const labels = Array.from(amenitiesCheckboxList.querySelectorAll('.amenity-label'));
                labels.forEach(lbl => {
                    const id = parseInt(lbl.dataset.amenityId, 10);
                    const chk = lbl.querySelector('.amenity-checkbox');
                    if (inTypeIds.includes(id)) {
                        chk.checked = true;
                        // mark style
                        lbl.classList.add('in-type');
                        lbl.classList.remove('not-in-type');
                    } else {
                        chk.checked = false;
                        lbl.classList.remove('in-type');
                        lbl.classList.add('not-in-type');
                    }
                    // all disabled to prevent any manual edits
                    chk.disabled = true;
                });
            }

            function fillFromSelectedOption(opt) {
                if (!opt) {
                    suc_chua_input.value = '';
                    so_giuong_input.value = '';
                    gia_input.value = '';
                    renderBedTypes([]);
                    // clear hidden inputs
                    setHiddenAmenityInputs([]);
                    refreshAmenityCheckboxUI([]);
                    updateTotal();
                    return;
                }
                const gia = opt.dataset.gia ?? 0;
                const suc = opt.dataset.suc_chua ?? '';
                const giuong = opt.dataset.so_giuong ?? '';
                const bedtypesJson = opt.dataset.bedtypes ?? '[]';
                const amenitiesJson = opt.dataset.amenities ?? '[]';

                gia_input.value = parseNumber(gia);
                suc_chua_input.value = suc !== '' ? parseNumber(suc) : '';
                so_giuong_input.value = giuong !== '' ? parseNumber(giuong) : '';

                // render bed types
                try {
                    const bedlist = JSON.parse(bedtypesJson);
                    if (Array.isArray(bedlist)) renderBedTypes(bedlist);
                    else renderBedTypes([]);
                } catch (e) {
                    renderBedTypes([]);
                }

                // parse amenity ids
                let amenityIds = [];
                try {
                    amenityIds = JSON.parse(amenitiesJson || '[]');
                    amenityIds = (Array.isArray(amenityIds) ? amenityIds.map(n => parseInt(n, 10)).filter(n => !isNaN(n)) : []);
                } catch (e) {
                    amenityIds = [];
                }

                // set hidden inputs (these will be submitted)
                setHiddenAmenityInputs(amenityIds);

                // update UI checkboxes to reflect type membership
                refreshAmenityCheckboxUI(amenityIds);

                updateTotal();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const selectedOpt = select.querySelector('option:checked');
                if (selectedOpt && selectedOpt.value) {
                    fillFromSelectedOption(selectedOpt);
                } else {
                    // if no selected option but old hidden inputs exist (eg form error), reflect them
                    const oldHidden = Array.from(document.querySelectorAll('.amenity-hidden-input-create')).map(x => parseInt(x.value, 10)).filter(n => !isNaN(n));
                    if (oldHidden.length) {
                        refreshAmenityCheckboxUI(oldHidden);
                        updateTotal();
                    } else {
                        updateTotal();
                    }
                }

                select.addEventListener('change', function() {
                    const opt = select.options[select.selectedIndex];
                    fillFromSelectedOption(opt);
                });
            });
        })();
    </script>
@endsection
