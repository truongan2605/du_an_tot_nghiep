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
                        <form method="POST" action="{{ route('admin.phong.vatdung.instances.store', $phong->id) }}">
                            @csrf

                            <div class="mb-2">
                                <label class="form-label">Vật dụng</label>
                                <select name="vat_dung_id" class="form-select" required>
                                    <option value="">-- Chọn vật dụng --</option>
                                    @foreach (\App\Models\VatDung::where('active', 1)->where('loai', \App\Models\VatDung::LOAI_DO_DUNG)->orderBy('ten')->get() as $vd)
                                        <option value="{{ $vd->id }}">{{ $vd->ten }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- quantity --}}
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
                                <button class="btn btn-primary">Tạo bản thể</button>
                            </div>
                        </form>


                        <div class="mt-2"><small class="text-muted">Lưu ý: chỉ tạo bản thể cho vật dụng kiểu <strong>đồ
                                    dùng</strong> và phải bật "theo dõi bản" nếu muốn quản lý từng bản.</small></div>
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
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vật dụng</th>
                                        <th>Serial</th>
                                        <th>Trạng thái</th>
                                        <th>Ghi chú</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instances as $ins)
                                        <!-- Thay vào trong foreach $instances -->
                                        <div class="btn-group" role="group" aria-label="status-actions">
                                            <!-- Set Present -->
                                            <form
                                                action="{{ route('admin.phong.vatdung.instances.update-status', $ins->id) }}"
                                                method="POST" style="display:inline-block;">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="present">
                                                <button class="btn btn-sm btn-outline-success"
                                                    type="submit">Present</button>
                                            </form>

                                            <!-- Damaged: show small fee input + submit -->
                                            <form
                                                action="{{ route('admin.phong.vatdung.instances.update-status', $ins->id) }}"
                                                method="POST" style="display:inline-block;" class="ms-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="damaged">
                                                <input type="hidden" name="create_consumption" value="0">
                                                <input type="number" step="0.01" name="incident_fee" placeholder="Fee"
                                                    class="form-control form-control-sm d-inline-block"
                                                    style="width:110px" />
                                                <button class="btn btn-sm btn-outline-warning ms-1"
                                                    type="submit">Damaged</button>
                                            </form>

                                            <!-- Missing: fee + option to auto charge -->
                                            <form
                                                action="{{ route('admin.phong.vatdung.instances.update-status', $ins->id) }}"
                                                method="POST" style="display:inline-block;" class="ms-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="missing">
                                                <label class="form-check-label visually-hidden"
                                                    for="charge_{{ $ins->id }}"></label>
                                                <input type="number" step="0.01" name="incident_fee" placeholder="Fee"
                                                    class="form-control form-control-sm d-inline-block"
                                                    style="width:110px" />
                                                <input type="hidden" name="create_consumption" value="1" />
                                                <button class="btn btn-sm btn-outline-danger ms-1" type="submit">Missing &
                                                    Charge</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
