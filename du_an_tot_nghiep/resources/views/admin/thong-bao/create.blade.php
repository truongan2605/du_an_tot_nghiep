@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-plus-circle me-2 text-success"></i>Tạo thông báo mới
            </h1>
            <p class="text-muted mb-0">Tạo và gửi thông báo đến người dùng hoặc nhóm vai trò</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.thong-bao.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
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
                    <form method="POST" action="{{ route('admin.thong-bao.store') }}">
                        @csrf
                        @include('admin.thong-bao.form')
                        
                        <!-- Action Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Thông báo sẽ được gửi ngay sau khi tạo
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.thong-bao.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Hủy
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-paper-plane me-1"></i>Tạo & Gửi thông báo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Hướng dẫn
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-users me-1"></i>Gửi theo vai trò
                        </h6>
                        <p class="small text-muted mb-0">
                            Chọn vai trò để gửi thông báo hàng loạt đến tất cả người dùng có vai trò đó.
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-user me-1"></i>Gửi cá nhân
                        </h6>
                        <p class="small text-muted mb-0">
                            Chọn người nhận cụ thể từ danh sách người dùng.
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-primary">
                            <i class="fas fa-tag me-1"></i>Templates
                        </h6>
                        <p class="small text-muted mb-0">
                            Chọn template để tự động điền nội dung JSON phù hợp.
                        </p>
                    </div>
                    
                    <div class="alert alert-warning border-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <small>
                            <strong>Lưu ý:</strong> Thông báo sẽ được gửi ngay lập tức sau khi tạo.
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