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

            @php
                $amenityPrices = $tienNghis->pluck('gia', 'id')->toArray();
            @endphp

            <div class="mb-2">
                {{-- Hiển thị mỗi tiện nghi dưới dạng badge; nếu thuộc $allChecked thì là badge nổi bật --}}
                <div id="amenities_display_edit" class="d-flex flex-wrap gap-2">
                    @foreach ($tienNghis as $tn)
                        @php $is = in_array($tn->id, $allChecked); @endphp
                        <span class="badge {{ $is ? 'bg-primary' : 'bg-light text-muted' }}" data-id="{{ $tn->id }}"
                            data-price="{{ $tn->gia ?? 0 }}">
                            <i class="{{ $tn->icon }}"></i> {{ $tn->ten }}
                            <small class="d-inline-block ms-1">({{ number_format($tn->gia, 0, ',', '.') }} đ)</small>
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- Giữ giá trị để submit: tạo hidden input cho từng tiện nghi được chọn --}}
            @foreach ($allChecked as $id)
                <input type="hidden" name="tien_nghi[]" value="{{ $id }}" class="amenity-hidden-input-edit">
            @endforeach


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
            // amenityPrices map từ server
            const amenityPrices = @json($tienNghis->pluck('gia', 'id') ?? []);
            // helper: lấy danh sách amenity id đang "được chọn" trên UI (dựa vào hidden inputs)
            function getSelectedAmenityIds(mode = 'edit') {
                // mode 'edit' -> class .amenity-hidden-input-edit
                // mode 'create' -> .amenity-hidden-input-create
                const selector = mode === 'edit' ? '.amenity-hidden-input-edit' : '.amenity-hidden-input-create';
                const els = Array.from(document.querySelectorAll(selector));
                return els.map(e => parseInt(e.value, 10)).filter(n => !isNaN(n));
            }

            // compute amenity sum from selected ids
            function computeAmenitySum(mode = 'edit') {
                const ids = getSelectedAmenityIds(mode);
                let s = 0;
                ids.forEach(id => {
                    const p = amenityPrices[id] ?? 0;
                    s += Number(p || 0);
                });
                return s;
            }

            // Update total_display: used in both create & edit
            function updateTotalGeneric(mode = 'edit') {
                // Get base from current selected loai_phong option (same selectors as your file)
                const loaiOpt = document.querySelector('#loai_phong_select' + (mode === 'edit' ? '_edit' : '') +
                    ' option:checked');
                const base = loaiOpt ? Number(loaiOpt.dataset.gia || 0) : 0;

                // compute bedSum: reuse your existing bed parsing logic (if any)
                // For simplicity, try to compute from current rendered bed table prices (optional)
                let bedSum = 0;
                try {
                    // if you have a global currentBedList variable, you can compute like earlier
                    if (typeof currentBedList !== 'undefined' && Array.isArray(currentBedList)) {
                        currentBedList.forEach(b => {
                            const qty = Number(b.quantity || 0);
                            const price = Number(b.price || 0);
                            bedSum += qty * price;
                        });
                    } else {
                        // fallback: 0
                        bedSum = 0;
                    }
                } catch (e) {
                    bedSum = 0;
                }

                const amenitySum = computeAmenitySum(mode);
                const total = Math.round(Number(base) + Number(amenitySum) + Number(bedSum));
                const disp = document.getElementById('total_display');
                if (disp) disp.innerText = new Intl.NumberFormat('vi-VN').format(total) + ' đ';
            }

            // When loai_phong select changes we need to set hidden inputs to the default amenities from option.data
            function setHiddenAmenitiesFromOption(opt, mode = 'edit') {
                if (!opt) return;
                let ids = [];
                try {
                    ids = JSON.parse(opt.dataset.amenities || '[]');
                } catch (e) {
                    ids = [];
                }
                // remove existing hidden inputs
                const selector = mode === 'edit' ? '.amenity-hidden-input-edit' : '.amenity-hidden-input-create';
                document.querySelectorAll(selector).forEach(n => n.remove());

                // append new hidden inputs to the form
                const form = document.getElementById(mode === 'edit' ? 'phongEditForm' : 'phongCreateForm');
                if (!form) {
                    // fallback: append to body
                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'tien_nghi[]';
                        input.value = id;
                        input.className = mode === 'edit' ? 'amenity-hidden-input-edit' :
                            'amenity-hidden-input-create';
                        document.body.appendChild(input);
                    });
                } else {
                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'tien_nghi[]';
                        input.value = id;
                        input.className = mode === 'edit' ? 'amenity-hidden-input-edit' :
                            'amenity-hidden-input-create';
                        form.appendChild(input);
                    });
                }

                // update badges UI: add selected class for badges that match ids
                const dispId = mode === 'edit' ? 'amenities_display_edit' : 'amenities_display_create';
                const disp = document.getElementById(dispId);
                if (disp) {
                    disp.querySelectorAll('span[data-id]').forEach(sp => {
                        const id = parseInt(sp.dataset.id, 10);
                        if (ids.indexOf(id) !== -1) {
                            sp.classList.remove('bg-light', 'text-muted');
                            sp.classList.add('bg-primary');
                        } else {
                            sp.classList.remove('bg-primary');
                            sp.classList.add('bg-light', 'text-muted');
                        }
                    });
                }

                // recalc total
                updateTotalGeneric(mode);
            }

            // Hook up on DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                // Wire loai_phong select change to update hidden amenity inputs
                // For edit: selector #loai_phong_select_edit ; create: #loai_phong_select
                const editSelect = document.getElementById('loai_phong_select_edit');
                if (editSelect) {
                    editSelect.addEventListener('change', function() {
                        const opt = editSelect.options[editSelect.selectedIndex];
                        setHiddenAmenitiesFromOption(opt, 'edit');
                    });
                    // initial fill from selected option (so total correct)
                    const opt0 = editSelect.options[editSelect.selectedIndex];
                    if (opt0) setHiddenAmenitiesFromOption(opt0, 'edit');
                }

                const createSelect = document.getElementById('loai_phong_select');
                if (createSelect) {
                    createSelect.addEventListener('change', function() {
                        const opt = createSelect.options[createSelect.selectedIndex];
                        setHiddenAmenitiesFromOption(opt, 'create');
                    });
                    const opt1 = createSelect.options[createSelect.selectedIndex];
                    if (opt1) setHiddenAmenitiesFromOption(opt1, 'create');
                }

                // Also call updateTotalGeneric on load so total_display is correct
                updateTotalGeneric('edit');
                updateTotalGeneric('create');
            });

            // expose methods to global if needed
            window.updateAmenityTotals = updateTotalGeneric;
        })();
    </script>

@endsection
