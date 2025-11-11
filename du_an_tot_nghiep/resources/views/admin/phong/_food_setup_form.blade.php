@php
    // $phong, $datPhong (nullable), $doAnList (Collection), $existingReservations (Collection keyed by vat_dung_id)
@endphp

<form id="food-setup-form" action="{{ route('admin.phong.food-reserve', ['phong' => $phong->id]) }}" method="POST"
    novalidate>
    @csrf
    <input type="hidden" name="dat_phong_id" value="{{ $datPhong->id ?? '' }}">

    <div id="food-setup-alerts"></div>

    <table class="table">
        <thead>
            <tr>
                <th></th>
                <th>Tên</th>
                <th>Giá mặc định</th>
                <th style="width:140px;">Số lượng</th>
                <th class="text-center" style="width:120px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($doAnList as $vd)
                @php
                    $existing = $existingReservations[$vd->id] ?? null;
                    $checked = (bool) $existing;
                    $qtyValue = $existing ? $existing->quantity : '';
                    $unitPrice = $existing ? $existing->unit_price ?? $vd->gia : $vd->gia ?? 0;
                @endphp
                <tr data-vd-id="{{ $vd->id }}" class="food-row">
                    <td class="align-middle">
                        <input type="checkbox" class="food-check form-check-input" data-id="{{ $vd->id }}"
                            {{ $checked ? 'checked' : '' }}>
                    </td>
                    <td class="align-middle">{{ $vd->ten }}</td>
                    <td class="align-middle">{{ $vd->gia ? number_format($vd->gia, 0, ',', '.') : '-' }}</td>
                    <td class="align-middle">
                        <input type="number" min="0" class="form-control qty-input"
                            data-id="{{ $vd->id }}" value="{{ old('items.' . $vd->id . '.quantity', $qtyValue) }}"
                            {{ $checked ? '' : 'disabled' }}>
                        <input type="hidden" class="vd-id-hidden" data-id="{{ $vd->id }}"
                            value="{{ $vd->id }}">
                        <input type="hidden" class="unit-price-hidden" data-id="{{ $vd->id }}"
                            value="{{ old('items.' . $vd->id . '.unit_price', $unitPrice) }}">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-success btn-item-save me-1"
                            data-id="{{ $vd->id }}" title="Lưu nhanh">
                            <i class="fas fa-save"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-item-delete"
                            data-id="{{ $vd->id }}" title="Xóa nhanh">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
        <div>
            @if ($existingReservations->isNotEmpty())
                <small class="text-muted">Các mục đã đặt trước được đánh dấu. Bỏ chọn để gỡ đặt trước.</small>
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

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('food-setup-form');
                const alerts = document.getElementById('food-setup-alerts');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '{{ csrf_token() }}';

                function enableNames(id) {
                    const qty = document.querySelector('.qty-input[data-id="' + id + '"]');
                    const hiddenVd = document.querySelector('.vd-id-hidden[data-id="' + id + '"]');
                    const hiddenPrice = document.querySelector('.unit-price-hidden[data-id="' + id + '"]');

                    if (qty) {
                        qty.name = 'items[' + id + '][quantity]';
                        qty.disabled = false;
                    }
                    if (hiddenVd) hiddenVd.name = 'items[' + id + '][vat_dung_id]';
                    if (hiddenPrice) hiddenPrice.name = 'items[' + id + '][unit_price]';
                }

                function disableNames(id) {
                    const qty = document.querySelector('.qty-input[data-id="' + id + '"]');
                    const hiddenVd = document.querySelector('.vd-id-hidden[data-id="' + id + '"]');
                    const hiddenPrice = document.querySelector('.unit-price-hidden[data-id="' + id + '"]');

                    if (qty) {
                        qty.removeAttribute('name');
                        qty.value = '';
                        qty.disabled = true;
                    }
                    if (hiddenVd) hiddenVd.removeAttribute('name');
                    if (hiddenPrice) hiddenPrice.removeAttribute('name');
                }

                document.querySelectorAll('.food-row').forEach(row => {
                    const id = row.dataset.vdId;
                    const chk = row.querySelector('.food-check');
                    const qty = row.querySelector('.qty-input');
                    if (chk && chk.checked) {
                        if (qty) {
                            qty.disabled = false;
                            if (!qty.value) qty.value = 1;
                        }
                        enableNames(id);
                    } else {
                        disableNames(id);
                    }

                    if (chk) {
                        chk.addEventListener('change', function() {
                            if (this.checked) {
                                const id = this.dataset.id;
                                const q = document.querySelector('.qty-input[data-id="' + id + '"]');
                                if (q) {
                                    q.disabled = false;
                                    if (!q.value) q.value = 1;
                                }
                                enableNames(id);
                            } else {
                                const id = this.dataset.id;
                                disableNames(id);
                            }
                        });
                    }
                });

                form.addEventListener('submit', function(e) {
                    document.querySelectorAll('.food-row').forEach(row => {
                        const id = row.dataset.vdId;
                        const chk = row.querySelector('.food-check');
                        const qty = row.querySelector('.qty-input');
                        if (!chk || !chk.checked) {
                            disableNames(id);
                            return;
                        }
                        const qv = parseInt(qty?.value || '0', 10);
                        if (!qv || qv <= 0) {
                            disableNames(id);
                        } else {
                            enableNames(id);
                        }
                    });
                });

                function showAlert(type, msg) {
                    alerts.innerHTML = '<div class="alert alert-' + type +
                        ' alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        msg + '</div>';
                }

                async function ajaxFetch(url, method = 'POST', body = null) {
                    const headers = {
                        'X-CSRF-TOKEN': csrfToken
                    };
                    let opts = {
                        method,
                        headers
                    };
                    if (body && !(body instanceof FormData)) {
                        headers['Content-Type'] = 'application/json';
                        opts.body = JSON.stringify(body);
                    } else if (body instanceof FormData) {
                        opts.body = body;
                    }
                    try {
                        const res = await fetch(url, opts);
                        const text = await res.text();
                        return {
                            ok: res.ok,
                            status: res.status,
                            text,
                            res
                        };
                    } catch (err) {
                        return {
                            ok: false,
                            status: 0,
                            text: err.message
                        };
                    }
                }

                document.querySelectorAll('.btn-item-save').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const id = this.dataset.id;
                        const chk = document.querySelector('.food-check[data-id="' + id + '"]');
                        const qtyEl = document.querySelector('.qty-input[data-id="' + id + '"]');
                        const vdHidden = document.querySelector('.vd-id-hidden[data-id="' + id +
                            '"]');
                        const priceHidden = document.querySelector('.unit-price-hidden[data-id="' +
                            id + '"]');

                        const qty = qtyEl ? parseInt(qtyEl.value || '0', 10) : 0;
                        if (!chk || !chk.checked || qty <= 0) {
                            await tryDeleteSingle(id);
                            return;
                        }

                        const storeUrl =
                            "{{ url('/admin/phong') }}/{{ $phong->id }}/food-reserve-item";
                        const data = {
                            dat_phong_id: '{{ $datPhong->id ?? '' }}',
                            items: {}
                        };
                        data.items[id] = {
                            vat_dung_id: id,
                            quantity: qty,
                            unit_price: priceHidden ? parseFloat(priceHidden.value || '0') : 0
                        };

                        const r = await ajaxFetch(storeUrl, 'POST', data);
                        if (r.ok) {
                            showAlert('success', 'Lưu món thành công.');
                            setTimeout(() => location.reload(), 600);
                        } else {
                            form.querySelector('#save-food-setup').click();
                        }
                    });
                });

                async function tryDeleteSingle(id) {
                    const deleteUrl = "{{ url('/admin/phong') }}/{{ $phong->id }}/food-reserve-item/" + id;
                    const r = await ajaxFetch(deleteUrl, 'DELETE', null);
                    if (r.ok) {
                        showAlert('success', 'Xóa món thành công (single AJAX).');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        const chk = document.querySelector('.food-check[data-id="' + id + '"]');
                        const qtyEl = document.querySelector('.qty-input[data-id="' + id + '"]');
                        if (chk) chk.checked = false;
                        if (qtyEl) {
                            qtyEl.value = '';
                            qtyEl.disabled = true;
                        }
                        form.querySelector('#save-food-setup').click();
                    }
                }

                document.querySelectorAll('.btn-item-delete').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        if (!confirm('Bạn chắc chắn muốn gỡ món này khỏi phòng?')) return;
                        tryDeleteSingle(id);
                    });
                });

            });
        </script>
    @endpush
@endonce
