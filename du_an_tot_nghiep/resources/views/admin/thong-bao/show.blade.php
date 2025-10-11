@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-bell me-2 text-primary"></i>Chi tiết thông báo #{{ $thongBao->id }}
            </h1>
            <p class="text-muted mb-0">Xem thông tin chi tiết và trạng thái thông báo</p>
        </div>
        <div class="btn-group">
            @if($thongBao->trang_thai === 'failed')
                <form action="{{ route('admin.thong-bao.resend', $thongBao) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Gửi lại thông báo này?')">
                        <i class="fas fa-paper-plane me-1"></i>Gửi lại
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.thong-bao.edit', $thongBao) }}" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>Chỉnh sửa
            </a>
            <a href="{{ route('admin.thong-bao.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Status Overview -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    @switch($thongBao->trang_thai)
                                        @case('pending')
                                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-clock text-white fa-2x"></i>
                                            </div>
                                            @break
                                        @case('sent')
                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-check text-white fa-2x"></i>
                                            </div>
                                            @break
                                        @case('failed')
                                            <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-times text-white fa-2x"></i>
                                            </div>
                                            @break
                                        @case('read')
                                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-eye text-white fa-2x"></i>
                                            </div>
                                            @break
                                        @default
                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="fas fa-question text-white fa-2x"></i>
                                            </div>
                                    @endswitch
                                </div>
                                <div>
                                    <h4 class="mb-1">
                                        @switch($thongBao->trang_thai)
                                            @case('pending')
                                                <span class="text-warning">Chờ xử lý</span>
                                                @break
                                            @case('sent')
                                                <span class="text-success">Đã gửi thành công</span>
                                                @break
                                            @case('failed')
                                                <span class="text-danger">Gửi thất bại</span>
                                                @break
                                            @case('read')
                                                <span class="text-info">Đã đọc</span>
                                                @break
                                            @default
                                                <span class="text-secondary">{{ $thongBao->trang_thai }}</span>
                                        @endswitch
                                    </h4>
                                    <p class="text-muted mb-0">
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
                                        • {{ $thongBao->ten_template }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-muted small">
                                <div><i class="fas fa-calendar me-1"></i>{{ $thongBao->created_at->format('d/m/Y') }}</div>
                                <div><i class="fas fa-clock me-1"></i>{{ $thongBao->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipient Information -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Thông tin người nhận
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                            <i class="fas fa-user text-white fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">{{ optional($thongBao->nguoiNhan)->name ?? 'N/A' }}</h5>
                            <p class="text-muted mb-1">{{ optional($thongBao->nguoiNhan)->email ?? 'N/A' }}</p>
                            <small class="text-muted">ID: {{ $thongBao->nguoi_nhan_id }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Content -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>Nội dung thông báo
                    </h5>
                </div>
                <div class="card-body">
                    @if($thongBao->payload && is_array($thongBao->payload))
                        @if(isset($thongBao->payload['title']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-heading me-1"></i>Tiêu đề
                            </h6>
                            <div class="alert alert-primary border-0">
                                <h5 class="mb-0">{{ $thongBao->payload['title'] }}</h5>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thongBao->payload['message']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-comment me-1"></i>Nội dung
                            </h6>
                            <div class="alert alert-light border">
                                <p class="mb-0 fs-6">{{ $thongBao->payload['message'] }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thongBao->payload['link']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-link me-1"></i>Liên kết
                            </h6>
                            <div class="alert alert-info border-0">
                                <a href="{{ $thongBao->payload['link'] }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                                </a>
                                <small class="text-muted d-block mt-2">{{ $thongBao->payload['link'] }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thongBao->payload['subject']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-envelope me-1"></i>Chủ đề email
                            </h6>
                            <div class="alert alert-warning border-0">
                                <span class="fw-bold">{{ $thongBao->payload['subject'] }}</span>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="alert alert-warning border-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Không có nội dung thông báo
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- System Information -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thông tin hệ thống
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-redo me-1"></i>Số lần thử
                                </span>
                                <span class="badge bg-info fs-6">{{ $thongBao->so_lan_thu ?? 0 }}</span>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>Lần thử cuối
                                </span>
                                <small class="text-muted">
                                    @if($thongBao->lan_thu_cuoi)
                                        {{ $thongBao->lan_thu_cuoi->format('d/m/Y H:i') }}
                                    @else
                                        Chưa có
                                    @endif
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-plus me-1"></i>Ngày tạo
                                </span>
                                <small class="text-muted">{{ $thongBao->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-edit me-1"></i>Cập nhật cuối
                                </span>
                                <small class="text-muted">{{ $thongBao->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raw JSON Data -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-code me-2"></i>Dữ liệu JSON
                    </h5>
                </div>
                <div class="card-body">
                    <div class="bg-dark rounded p-3">
                        <pre class="text-light mb-0" style="font-size: 0.75rem; max-height: 300px; overflow-y: auto;">{{ json_encode($thongBao->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 24px;
}

.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.alert {
    border-radius: 0.5rem;
}

.badge {
    font-size: 0.75em;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 2px;
    }
}
</style>
@endpush