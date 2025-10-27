@extends('layouts.admin')

@section('title', 'Chi tiết vật dụng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-box me-2"></i>Chi tiết: {{ $vat_dung->ten }}</h2>
        <div>
            <a href="{{ route('admin.vat-dung.edit', ['vat_dung' => $vat_dung->id]) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i>Chỉnh sửa
            </a>
            <a href="{{ route('admin.vat-dung.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 border-end">
                    <h5 class="fw-bold text-dark mb-3">Thông tin vật dụng</h5>
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-semibold" width="160">ID:</td>
                            <td>{{ $vat_dung->id }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Tên:</td>
                            <td>{{ $vat_dung->ten }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Loại:</td>
                            <td>
                                @if ($vat_dung->loai === \App\Models\VatDung::LOAI_DO_AN)
                                    Đồ ăn (tiêu thụ)
                                @else
                                    Đồ dùng (theo dõi)
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Theo dõi từng bản:</td>
                            <td>{{ $vat_dung->tracked_instances ? 'Có' : 'Không' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Giá:</td>
                            <td>{{ $vat_dung->gia !== null ? number_format($vat_dung->gia, 0, ',', '.') . ' ₫' : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Mô tả:</td>
                            <td>{{ $vat_dung->mo_ta ?: 'Không có mô tả' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Trạng thái:</td>
                            <td>
                                <span class="badge px-3 py-2 {{ $vat_dung->active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $vat_dung->active ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Ngày tạo:</td>
                            <td>{{ $vat_dung->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Cập nhật:</td>
                            <td>{{ $vat_dung->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6 text-center">
                    <h5 class="fw-bold text-dark mb-3">Hình ảnh</h5>
                    @if ($vat_dung->icon && Storage::disk('public')->exists($vat_dung->icon))
                        <div class="border rounded p-2 bg-light shadow-sm d-inline-block">
                            <img src="{{ Storage::url($vat_dung->icon) }}" alt="Ảnh vật dụng"
                                class="img-fluid rounded" style="max-height: 260px; object-fit: contain;">
                        </div>
                    @else
                        <p class="text-muted mt-3">Không có hình ảnh</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Loại phòng chứa vật dụng --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Loại phòng có vật dụng này</h5>
        </div>
        <div class="card-body">
            @if ($loaiPhongs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tên loại phòng</th>
                                <th>Giá mặc định</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loaiPhongs as $lp)
                                <tr>
                                    <td>{{ $lp->ten }}</td>
                                    <td>{{ number_format($lp->gia_mac_dinh, 0, ',', '.') }} ₫</td>
                                    <td>{{ $lp->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $loaiPhongs->links() }}</div>
            @else
                <p class="text-muted mb-0">Chưa có loại phòng nào sử dụng vật dụng này.</p>
            @endif
        </div>
    </div>
@endsection
