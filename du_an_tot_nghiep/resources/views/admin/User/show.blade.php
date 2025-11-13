@extends('layouts.admin')

@section('title', 'Chi Tiết Khách Hàng - {{ $user->name }}')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">
        <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
            <!-- Header with gradient and balanced layout -->
            <div class="card-header bg-gradient position-relative overflow-hidden px-4 py-4 text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3 p-2 bg-white bg-opacity-20 rounded-circle">
                        <i class="fas fa-user-circle text-white fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold lh-1">{{ $user->name }}</h4>
                        <p class="mb-0 small opacity-90">Mã KH: {{ $user->id }}</p>
                    </div>
                </div>
                <span class="badge fs-6 px-3 py-2 {{ $user->is_active ? 'bg-success' : 'bg-secondary' }} shadow-sm">
                    <i class="fas {{ $user->is_active ? 'fa-check-circle me-1' : 'fa-pause-circle me-1' }}"></i>
                    {{ $user->is_active ? 'Hoạt động' : 'Không hoạt động' }}
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
                                    <p class="mb-0 fw-semibold lh-sm">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-phone text-success"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Số Điện Thoại</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ $user->so_dien_thoai ?? 'Chưa cập nhật' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Info Column -->
                    <div class="col-md-6">
                        <div class="p-4">
                            <h6 class="fw-semibold text-uppercase text-muted small mb-3">Thông Tin Tài Khoản</h6>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-briefcase text-info"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Vai Trò</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ ucfirst(str_replace('_', ' ', $user->vai_tro ?? 'N/A')) }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-building text-warning"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Phòng Ban</small>
                                    <p class="mb-0 fw-semibold lh-sm">{{ $user->phong_ban ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-{{ $user->email_verified_at ? 'success' : 'danger' }} bg-opacity-10 rounded-circle p-2 me-3">
                                    <i class="fas fa-{{ $user->email_verified_at ? 'check' : 'times' }}-circle text-{{ $user->email_verified_at ? 'success' : 'danger' }}"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Email Đã Xác Thực</small>
                                    <p class="mb-0 fw-semibold lh-sm text-{{ $user->email_verified_at ? 'success' : 'danger' }}">{{ $user->email_verified_at ? 'Có' : 'Không' }}</p>
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
                        <i class="fas fa-info-circle me-1"></i>Cập nhật lần cuối: {{ $user->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.user.edit', $user) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>Sửa
                        </a>
                        <a href="{{ route('admin.user.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection