@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        @if (! $showAllowed)
            <div class="alert alert-warning">Không thể setup đồ ăn cho booking/phòng này do trạng thái không hợp lệ.</div>
        @else
            <form id="food-setup-form" action="{{ route('admin.phong.food-reserve', ['phong' => $phong->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="dat_phong_id" value="{{ $datPhong->id ?? '' }}">

                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Tên</th>
                            <th>Giá mặc định</th>
                            <th style="width:140px;">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($doAnList as $vd)
                            @php
                                $existing = $existingReservations[$vd->id] ?? null;
                                $checked = (bool) $existing;
                                $qtyValue = $existing ? $existing->quantity : '';
                                $unitPrice = $existing ? ($existing->unit_price ?? $vd->gia) : ($vd->gia ?? 0);
                            @endphp
                            <tr>
                                <td class="align-middle">
                                    <input type="checkbox" class="food-check" data-id="{{ $vd->id }}" {{ $checked ? 'checked' : '' }}>
                                </td>
                                <td class="align-middle">{{ $vd->ten }}</td>
                                <td class="align-middle">{{ $vd->gia ? number_format($vd->gia,0,',','.') : '-' }}</td>
                                <td class="align-middle">
                                    <!-- Note: names for these inputs will be added/removed by JS only when checked -->
                                    <input type="number" min="1"
                                           class="form-control qty-input"
                                           data-id="{{ $vd->id }}"
                                           value="{{ old('items.'.$vd->id.'.quantity', $qtyValue) }}"
                                           {{ $checked ? '' : 'disabled' }}>
                                    <!-- Hidden elements without names initially; JS will add name attributes when checked -->
                                    <input type="hidden" class="vd-id-hidden" data-id="{{ $vd->id }}" value="{{ $vd->id }}">
                                    <input type="hidden" class="unit-price-hidden" data-id="{{ $vd->id }}" value="{{ old('items.'.$vd->id.'.unit_price', $unitPrice) }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @if($existingReservations->isNotEmpty())
                            <small class="text-muted">Các mục đã đặt trước được đánh dấu. Bỏ chọn để gỡ đặt trước (nếu chưa billed).</small>
                        @else
                            <small class="text-muted">Chọn đồ ăn và số lượng rồi click Lưu setup.</small>
                        @endif
                    </div>
                    <div class="d-flex">
                        <a href="{{ route('admin.phong.index') }}" class="btn btn-secondary me-2">Hủy</a>
                        <button class="btn btn-primary" id="save-food-setup">Lưu setup</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

@section('scripts')
<script>
(function(){
    // helper to set/remove name attributes for an item id
    function enableItemNames(id) {
        const qty = document.querySelector('.qty-input[data-id="'+id+'"]');
        const hiddenVd = document.querySelector('.vd-id-hidden[data-id="'+id+'"]');
        const hiddenPrice = document.querySelector('.unit-price-hidden[data-id="'+id+'"]');

        if (qty) qty.name = 'items['+id+'][quantity]';
        if (hiddenVd) hiddenVd.name = 'items['+id+'][vat_dung_id]';
        if (hiddenPrice) hiddenPrice.name = 'items['+id+'][unit_price]';
    }
    function disableItemNames(id) {
        const qty = document.querySelector('.qty-input[data-id="'+id+'"]');
        const hiddenVd = document.querySelector('.vd-id-hidden[data-id="'+id+'"]');
        const hiddenPrice = document.querySelector('.unit-price-hidden[data-id="'+id+'"]');

        if (qty) { qty.removeAttribute('name'); qty.value = ''; qty.disabled = true; }
        if (hiddenVd) hiddenVd.removeAttribute('name');
        if (hiddenPrice) hiddenPrice.removeAttribute('name');
    }

    // initialize: for each checkbox, set names if checked, otherwise ensure disabled and no names
    document.querySelectorAll('.food-check').forEach(chk => {
        const id = chk.dataset.id;
        // find qty input
        const qty = document.querySelector('.qty-input[data-id="'+id+'"]');
        // if checkbox is checked on load, enable names (and ensure qty not disabled)
        if (chk.checked) {
            if (qty) qty.disabled = false;
            enableItemNames(id);
            if (!qty.value) qty.value = 1;
        } else {
            disableItemNames(id);
        }

        chk.addEventListener('change', function(e){
            if (this.checked) {
                // user checked -> enable qty and hidden inputs
                const id = this.dataset.id;
                const qty = document.querySelector('.qty-input[data-id="'+id+'"]');
                if (qty) { qty.disabled = false; if (!qty.value) qty.value = 1; }
                enableItemNames(id);
            } else {
                const id = this.dataset.id;
                disableItemNames(id);
            }
        });
    });

    // as a safety before submit: ensure only checked items have names (should already be)
    document.getElementById('food-setup-form').addEventListener('submit', function(){
        document.querySelectorAll('.food-check').forEach(chk => {
            const id = chk.dataset.id;
            if (!chk.checked) {
                disableItemNames(id);
            } else {
                enableItemNames(id);
            }
        });
    });
})();
</script>
@endsection

@endsection
