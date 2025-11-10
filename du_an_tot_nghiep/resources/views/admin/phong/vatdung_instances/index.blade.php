@extends('layouts.admin')

@section('title', 'Quản lý bản thể — Phòng ' . ($phong->ma_phong ?? ''))

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Bản thể vật dụng — Phòng: {{ $phong->ma_phong }}</h4>
            <a href="{{ route('admin.phong.show', $phong->id) }}" class="btn btn-light btn-sm">← Quay lại phòng</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row gy-3">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">Tạo bản thể mới</div>
                    <div class="card-body">
                        @php
                            $allowedVatDungs = $phong->loaiPhong
                                ? $phong->loaiPhong->vatDungs->filter(
                                    fn($v) => ($v->loai ?? '') === \App\Models\VatDung::LOAI_DO_DUNG,
                                )
                                : collect();
                            $activeBooking = $phong->activeDatPhong();
                        @endphp

                        <form method="POST" action="{{ route('admin.phong.vatdung.instances.store', $phong->id) }}">
                            @csrf

                            <div class="mb-2">
                                <label class="form-label">Vật dụng (chỉ vật dụng của Loại phòng này)</label>
                                <select name="vat_dung_id" class="form-select" required>
                                    <option value="">-- Chọn vật dụng --</option>
                                    @foreach ($allowedVatDungs as $vd)
                                        <option value="{{ $vd->id }}">{{ $vd->ten }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Số lượng</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" />
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Serial (nếu cần)</label>
                                <input type="text" name="serial" class="form-control" />
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" />
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-secondary" href="{{ route('admin.phong.show', $phong->id) }}">Hủy</a>
                                <button class="btn btn-primary" {{ $activeBooking ? 'disabled' : '' }}>
                                    Tạo bản thể
                                </button>
                            </div>
                        </form>

                        <div class="mt-2"><small class="text-muted">Lưu ý: chỉ tạo bản thể cho vật dụng kiểu <strong>đồ
                                    dùng</strong></small></div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">Danh sách bản thể ({{ $instances->count() }})</div>
                    <div class="card-body p-0">
                        @if ($instances->isEmpty())
                            <div class="p-3"><em>Chưa có bản thể.</em></div>
                        @else
                            @php
                                $statusLabels = [
                                    'present' => 'Nguyên vẹn',
                                    'damaged' => 'Hỏng',
                                    'missing' => 'Mất',
                                    'lost' => 'Mất (vĩnh viễn)',
                                    'archived' => 'Lưu trữ',
                                ];

                                $statusBadge = [
                                    'present' => 'success',
                                    'damaged' => 'warning',
                                    'missing' => 'danger',
                                    'lost' => 'danger',
                                    'archived' => 'secondary',
                                ];
                            @endphp

                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vật dụng</th>
                                        <th>Serial</th>
                                        <th>Trạng thái</th>
                                        <th>Ghi chú</th>
                                        <th>Đánh dấu trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instances as $ins)
                                        <tr>
                                            <td>{{ $ins->id }}</td>
                                            <td>{{ $ins->vatDung->ten ?? '-' }}</td>
                                            <td>{{ $ins->serial ?? '-' }}</td>
                                            <td>
                                                @php $st = $ins->status; @endphp
                                                <span class="badge bg-{{ $statusBadge[$st] ?? 'secondary' }}">
                                                    {{ $statusLabels[$st] ?? $st }}
                                                </span>
                                            </td>
                                            <td>{{ $ins->note }}</td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="status-actions">
                                                    {{-- Present --}}
                                                    <form
                                                        action="{{ route('admin.phong.vatdung.instances.update-status', $ins->id) }}"
                                                        method="POST" style="display:inline-block;">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="present">
                                                        <button class="btn btn-sm btn-outline-success ms-1"
                                                            type="submit">Nguyên vẹn</button>
                                                    </form>

                                                    {{-- Damaged --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-warning ms-1 btn-open-incident-modal"
                                                        data-instance-id="{{ $ins->id }}"
                                                        data-vat-dung-id="{{ $ins->vat_dung_id }}" data-status="damaged"
                                                        data-default-fee="{{ optional($ins->vatDung)->gia ?? 0 }}"
                                                        {{ $activeBooking ? '' : 'disabled title=“Không có booking — không thể tính tiền”' }}>
                                                        Hỏng
                                                    </button>

                                                    {{-- Missing & Charge --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger ms-1 btn-open-incident-modal"
                                                        data-instance-id="{{ $ins->id }}"
                                                        data-vat-dung-id="{{ $ins->vat_dung_id }}" data-status="missing"
                                                        data-default-fee="{{ optional($ins->vatDung)->gia ?? 0 }}"
                                                        {{ $activeBooking ? '' : 'disabled title=“Không có booking — không thể tính tiền”' }}>
                                                        Mất
                                                    </button>

                                                    {{-- Edit instance --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary ms-1 btn-open-edit-instance"
                                                        data-id="{{ $ins->id }}" data-serial="{{ e($ins->serial) }}"
                                                        data-note="{{ e($ins->note) }}" data-status="{{ $ins->status }}">
                                                        Sửa
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Edit Instance Modal --}}
                            <div class="modal fade" id="editInstanceModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form id="editInstanceForm" method="POST" action="">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Chỉnh bản thể</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2">
                                                    <label class="form-label">Serial</label>
                                                    <input type="text" name="serial" id="edit_serial"
                                                        class="form-control" />
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Ghi chú</label>
                                                    <textarea name="note" id="edit_note" class="form-control"></textarea>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Trạng thái</label>
                                                    <select name="status" id="edit_status" class="form-select">
                                                        <option value="present">Nguyên vẹn</option>
                                                        <option value="damaged">Hỏng</option>
                                                        <option value="missing">Mất</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-primary">Lưu</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- INCIDENT Modal --}}
                            <div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form id="incidentForm" method="POST"
                                            action="{{ route('admin.phong.incidents.store') }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Ghi nhận sự cố & tính tiền</h5>
                                                <button type="button" class="btn-close"
                                                    data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="phong_vat_dung_instance_id"
                                                    id="modal_instance_id" />
                                                <input type="hidden" name="vat_dung_id" id="modal_vat_dung_id" />
                                                <input type="hidden" name="phong_id" value="{{ $phong->id }}" />
                                                <input type="hidden" name="dat_phong_id" id="modal_dat_phong_id"
                                                    value="{{ $activeBooking->id ?? '' }}" />

                                                <div class="mb-2">
                                                    <label class="form-label">Loại sự cố</label>
                                                    <select name="mark_instance_status" id="modal_status"
                                                        class="form-select">
                                                        <option value="damaged">Hỏng</option>
                                                        <option value="missing">Mất</option>
                                                        <option value="lost">Mất (vĩnh viễn)</option>
                                                    </select>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="form-label">Số tiền (sẽ được thêm vào hoá đơn booking nếu
                                                        có)</label>
                                                    <input type="number" step="0.01" name="fee" id="modal_fee"
                                                        class="form-control" />
                                                </div>

                                                <div class="mb-2">
                                                    <label class="form-label">Mô tả / Ghi chú</label>
                                                    <textarea name="description" id="modal_description" class="form-control"></textarea>
                                                </div>

                                                @unless ($activeBooking)
                                                    <div class="alert alert-warning small">Phòng hiện không có booking hợp lệ — các nút tính tiền bị khoá. Bạn vẫn có thể chỉnh trạng thái bằng nút <strong>Edit</strong>.</div>
                                                @endunless
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-danger">Ghi nhận & Tính
                                                    tiền</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const incidentModalEl = document.getElementById('incidentModal');
    const incidentBsModal = incidentModalEl ? new bootstrap.Modal(incidentModalEl) : null;

    document.querySelectorAll('.btn-open-incident-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const instanceId = this.dataset.instanceId;
            const vatDungId = this.dataset.vatDungId;
            const status = this.dataset.status ?? 'damaged';
            const defaultFee = this.dataset.defaultFee ?? 0;

            // populate modal fields
            const instInput = document.getElementById('modal_instance_id');
            const vatInput = document.getElementById('modal_vat_dung_id');
            const statusInput = document.getElementById('modal_status');
            const feeInput = document.getElementById('modal_fee');
            const desc = document.getElementById('modal_description');

            if (instInput) instInput.value = instanceId;
            if (vatInput) vatInput.value = vatDungId;
            if (statusInput) statusInput.value = status;
            if (feeInput) feeInput.value = defaultFee;
            if (desc) desc.value = '';

            if (incidentBsModal) incidentBsModal.show();
        });
    });

    const editModalEl = document.getElementById('editInstanceModal');
    const editBsModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

    const updateUrlTemplate = "{{ route('admin.phong.vatdung.instances.update', ['instance' => '__ID__']) }}";

    document.querySelectorAll('.btn-open-edit-instance').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const serial = this.dataset.serial ?? '';
            const note = this.dataset.note ?? '';
            const status = this.dataset.status ?? 'present';

            document.getElementById('edit_serial').value = serial;
            document.getElementById('edit_note').value = note;
            document.getElementById('edit_status').value = status;

            const form = document.getElementById('editInstanceForm');
            if (form) {
                form.action = updateUrlTemplate.replace('__ID__', id);
            }

            if (editBsModal) editBsModal.show();
        });
    });
});
</script>
@endpush
