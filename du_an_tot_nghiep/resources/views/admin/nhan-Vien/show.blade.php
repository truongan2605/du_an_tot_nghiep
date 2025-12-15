@extends('layouts.admin')

@section('title', 'Chi Tiết Nhân Viên ')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">
        <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
            <!-- Header with gradient and balanced layout -->
            <div class="card-header bg-gradient position-relative overflow-hidden px-4 py-4 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3 p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-user-tie text-white fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold lh-1">{{ $nhan_vien->name }}</h4>
                        <p class="mb-0 small opacity-90">Mã NV: {{ $nhan_vien->id ?? 'N/A' }}</p>
                    </div>
                </div>
                <span class="badge fs-6 px-3 py-2 {{ $nhan_vien->is_disabled ? 'bg-danger' : 'bg-success' }} shadow-sm">
                    <i class="fas {{ $nhan_vien->is_disabled ? 'fa-ban me-1' : 'fa-check-circle me-1' }}"></i>
                    {{ $nhan_vien->is_disabled ? 'Đã vô hiệu hóa' : 'Hoạt động' }}
                </span>
            </div>

            <!-- Body with icon-enhanced details -->
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Personal Info Column -->
                    <div class="col-md-6 border-end border-light-subtle">
                        <div class="p-4">
                            <h6 class="fw-semibold text-uppercase text-muted small mb-3">Thông Tin Cá Nhân</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-envelope text-primary"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Email</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ $nhan_vien->email }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-phone text-success"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Số Điện Thoại</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ $nhan_vien->so_dien_thoai ?? 'Chưa cập nhật' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Department Info Column -->
                    <div class="col-md-6">
                        <div class="p-4">
                            <h6 class="fw-semibold text-uppercase text-muted small mb-3">Thông Tin Công Việc</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-building text-info"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Phòng Ban</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ $nhan_vien->phong_ban ?? 'Chưa phân công' }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-calendar-alt text-warning"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Ngày Tạo</small>
                                    <p class="mb-0 fw-semibold lh-sm text-muted">{{ $nhan_vien->created_at?->format('d/m/Y') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer with balanced actions -->
            <div class="card-footer bg-transparent border-0 px-4 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>Cập nhật lần cuối: {{ $nhan_vien->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.nhan-vien.edit', $nhan_vien) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Sửa
                        </a>
                        <a href="{{ route('admin.nhan-vien.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection