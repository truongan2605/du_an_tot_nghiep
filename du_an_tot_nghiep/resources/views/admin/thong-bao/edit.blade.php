@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-edit me-2 text-warning"></i>Chỉnh sửa thông báo #{{ $thongBao->id }}
            </h1>
            <p class="text-muted mb-0">Cập nhật thông tin và cài đặt thông báo</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.thong-bao.show', $thongBao) }}" class="btn btn-info">
                <i class="fas fa-eye me-1"></i>Xem chi tiết
            </a>
            <a href="{{ route('admin.thong-bao.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Thông tin thông báo
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.thong-bao.update', $thongBao) }}">
                        @csrf
                        @method('PUT')
                        @include('admin.thong-bao.form')
                        
                        <!-- Action Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Cập nhật thông báo sẽ không gửi lại email
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.thong-bao.show', $thongBao) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Hủy
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save me-1"></i>Cập nhật thông báo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Current Info Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-user me-1"></i>Người nhận
                        </h6>
                        <p class="small text-muted mb-0">
                            {{ optional($thongBao->nguoiNhan)->name ?? 'N/A' }}
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-broadcast-tower me-1"></i>Kênh
                        </h6>
                        <p class="small text-muted mb-0">
                            @switch($thongBao->kenh)
                                @case('email')
                                    <i class="fas fa-envelope me-1"></i>Email
                                    @break
                                @case('sms')
                                    <i class="fas fa-sms me-1"></i>SMS
                                    @break
                                @case('in_app')
                                    <i class="fas fa-bell me-1"></i>In-app
                                    @break
                                @default
                                    {{ $thongBao->kenh }}
                            @endswitch
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-flag me-1"></i>Trạng thái
                        </h6>
                        <p class="small text-muted mb-0">
                            @switch($thongBao->trang_thai)
                                @case('pending')
                                    <span class="badge bg-warning">Chờ xử lý</span>
                                    @break
                                @case('sent')
                                    <span class="badge bg-success">Đã gửi</span>
                                    @break
                                @case('failed')
                                    <span class="badge bg-danger">Thất bại</span>
                                    @break
                                @case('read')
                                    <span class="badge bg-info">Đã đọc</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $thongBao->trang_thai }}</span>
                            @endswitch
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-calendar me-1"></i>Ngày tạo
                        </h6>
                        <p class="small text-muted mb-0">
                            {{ $thongBao->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    
                    <div class="alert alert-info border-0">
                        <i class="fas fa-lightbulb me-1"></i>
                        <small>
                            <strong>Lưu ý:</strong> Chỉnh sửa thông báo sẽ không gửi lại email. 
                            Để gửi lại, hãy sử dụng nút "Gửi lại" trong trang chi tiết.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-left: 0.25rem;
}

.btn-group .btn:first-child {
    margin-left: 0;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-left: 0;
        margin-bottom: 0.25rem;
    }
    
    .btn-group .btn:last-child {
        margin-bottom: 0;
    }
}
</style>
@endpush