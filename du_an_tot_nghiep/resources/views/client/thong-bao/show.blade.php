@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-bell me-2 text-primary"></i>Chi tiết thông báo
            </h1>
            <p class="text-muted mb-0">Thông tin chi tiết về thông báo của bạn</p>
        </div>
        <div class="btn-group">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
            @if($thong_bao->trang_thai !== 'read')
                <button type="button" class="btn btn-success" id="markAsReadBtn">
                    <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                </button>
            @endif
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
                                    @switch($thong_bao->trang_thai)
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
                                        @switch($thong_bao->trang_thai)
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
                                                <span class="text-secondary">{{ $thong_bao->trang_thai }}</span>
                                        @endswitch
                                    </h4>
                                    <p class="text-muted mb-0">
                                        @switch($thong_bao->kenh)
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
                                                {{ $thong_bao->kenh }}
                                        @endswitch
                                        • {{ $thong_bao->ten_template }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-muted small">
                                <div><i class="fas fa-calendar me-1"></i>{{ $thong_bao->created_at->format('d/m/Y') }}</div>
                                <div><i class="fas fa-clock me-1"></i>{{ $thong_bao->created_at->format('H:i') }}</div>
                            </div>
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
                    @if($thong_bao->payload && is_array($thong_bao->payload))
                        @if(isset($thong_bao->payload['title']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-heading me-1"></i>Tiêu đề
                            </h6>
                            <div class="alert alert-primary border-0">
                                <h5 class="mb-0">{{ $thong_bao->payload['title'] }}</h5>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thong_bao->payload['message']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-comment me-1"></i>Nội dung
                            </h6>
                            <div class="alert alert-light border">
                                <p class="mb-0 fs-6">{{ $thong_bao->payload['message'] }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thong_bao->payload['link']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-link me-1"></i>Liên kết
                            </h6>
                            <div class="alert alert-info border-0">
                                <a href="{{ $thong_bao->payload['link'] }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                                </a>
                                <small class="text-muted d-block mt-2">{{ $thong_bao->payload['link'] }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($thong_bao->payload['subject']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                            <i class="fas fa-envelope me-1"></i>Chủ đề email
                        </h6>
                        <div class="alert alert-warning border-0">
                            <span class="fw-bold">{{ $thong_bao->payload['subject'] }}</span>
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
            <!-- Notification Info -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin thông báo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-tag me-1"></i>Loại thông báo
                                </span>
                                <span class="badge bg-primary fs-6">{{ $thong_bao->ten_template }}</span>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-broadcast-tower me-1"></i>Kênh
                                </span>
                                @switch($thong_bao->kenh)
                                    @case('email')
                                        <span class="badge bg-info">
                                            <i class="fas fa-envelope me-1"></i>Email
                                        </span>
                                        @break
                                    @case('sms')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-sms me-1"></i>SMS
                                        </span>
                                        @break
                                    @case('in_app')
                                        <span class="badge bg-success">
                                            <i class="fas fa-bell me-1"></i>In-app
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $thong_bao->kenh }}</span>
                                @endswitch
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-plus me-1"></i>Ngày tạo
                                </span>
                                <small class="text-muted">{{ $thong_bao->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-edit me-1"></i>Cập nhật cuối
                                </span>
                                <small class="text-muted">{{ $thong_bao->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thao tác
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($thong_bao->trang_thai !== 'read')
                            <button type="button" class="btn btn-success" id="markAsReadBtn2">
                                <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                            </button>
                        @endif
                        
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        
                        @if(isset($thong_bao->payload['link']))
                            <a href="{{ $thong_bao->payload['link'] }}" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                            </a>
                        @endif
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const markAsReadBtn = document.getElementById('markAsReadBtn');
    const markAsReadBtn2 = document.getElementById('markAsReadBtn2');
    const notificationId = {{ $thong_bao->id }};
    
    function markAsRead() {
        fetch(`/notification/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide mark as read buttons
                if (markAsReadBtn) markAsReadBtn.style.display = 'none';
                if (markAsReadBtn2) markAsReadBtn2.style.display = 'none';
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check me-1"></i>Đã đánh dấu thông báo là đã đọc
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container').insertBefore(alert, document.querySelector('.container').firstChild);
                
                // Update page title to show read status
                document.querySelector('.text-warning, .text-success, .text-danger, .text-info').textContent = 'Đã đọc';
                document.querySelector('.text-warning, .text-success, .text-danger, .text-info').className = 'text-info';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi đánh dấu đã đọc');
        });
    }
    
    if (markAsReadBtn) {
        markAsReadBtn.addEventListener('click', markAsRead);
    }
    
    if (markAsReadBtn2) {
        markAsReadBtn2.addEventListener('click', markAsRead);
    }
});
</script>
@endpush
