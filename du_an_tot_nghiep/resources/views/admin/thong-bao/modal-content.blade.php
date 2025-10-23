<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">
            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
        </h6>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-user me-1"></i>Người nhận
            </label>
            <div class="d-flex align-items-center">
                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                    <i class="fas fa-user text-white"></i>
                </div>
                <div>
                    <h6 class="mb-0">{{ optional($thong_bao->nguoiNhan)->name ?? 'N/A' }}</h6>
                    <small class="text-muted">{{ optional($thong_bao->nguoiNhan)->email ?? 'N/A' }}</small>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-broadcast-tower me-1"></i>Kênh
            </label>
            <div>
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
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-tag me-1"></i>Template
            </label>
            <div class="form-control-plaintext">
                <span class="badge bg-light text-dark">{{ $thong_bao->ten_template }}</span>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-flag me-1"></i>Trạng thái
            </label>
            <div>
                @switch($thong_bao->trang_thai)
                    @case('pending')
                        <span class="badge bg-warning">
                            <i class="fas fa-clock me-1"></i>Chờ xử lý
                        </span>
                        @break
                    @case('sent')
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Đã gửi
                        </span>
                        @break
                    @case('failed')
                        <span class="badge bg-danger">
                            <i class="fas fa-times me-1"></i>Gửi thất bại
                        </span>
                        @break
                    @case('read')
                        <span class="badge bg-info">
                            <i class="fas fa-eye me-1"></i>Đã đọc
                        </span>
                        @break
                    @default
                        <span class="badge bg-secondary">{{ $thong_bao->trang_thai }}</span>
                @endswitch
            </div>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">
            <i class="fas fa-cogs me-2"></i>Thông tin hệ thống
        </h6>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-redo me-1"></i>Số lần thử
            </label>
            <div class="form-control-plaintext">
                <span class="badge bg-info">{{ $thong_bao->so_lan_thu ?? 0 }}</span>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-calendar me-1"></i>Lần thử cuối
            </label>
            <div class="form-control-plaintext">
                @if($thong_bao->lan_thu_cuoi)
                    <span class="text-muted">{{ $thong_bao->lan_thu_cuoi->format('d/m/Y H:i') }}</span>
                @else
                    <span class="text-muted">Chưa có</span>
                @endif
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-plus me-1"></i>Ngày tạo
            </label>
            <div class="form-control-plaintext">
                <span class="text-muted">{{ $thong_bao->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-edit me-1"></i>Cập nhật cuối
            </label>
            <div class="form-control-plaintext">
                <span class="text-muted">{{ $thong_bao->updated_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Notification Content -->
@if($thong_bao->payload && is_array($thong_bao->payload))
<div class="mt-4">
    <h6 class="fw-bold text-primary mb-3">
        <i class="fas fa-file-alt me-2"></i>Nội dung thông báo
    </h6>
    
    @if(isset($thong_bao->payload['title']))
    <div class="mb-3">
        <label class="form-label fw-bold">
            <i class="fas fa-heading me-1"></i>Tiêu đề
        </label>
        <div class="alert alert-light border">
            <h6 class="mb-0">{{ $thong_bao->payload['title'] }}</h6>
        </div>
    </div>
    @endif
    
    @if(isset($thong_bao->payload['message']))
    <div class="mb-3">
        <label class="form-label fw-bold">
            <i class="fas fa-comment me-1"></i>Nội dung
        </label>
        <div class="alert alert-light border">
            <p class="mb-0">{{ $thong_bao->payload['message'] }}</p>
        </div>
    </div>
    @endif
    
    @if(isset($thong_bao->payload['link']))
    <div class="mb-3">
        <label class="form-label fw-bold">
            <i class="fas fa-link me-1"></i>Liên kết
        </label>
        <div class="alert alert-light border">
            <a href="{{ $thong_bao->payload['link'] }}" class="text-decoration-none" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>{{ $thong_bao->payload['link'] }}
            </a>
        </div>
    </div>
    @endif
    
    @if(isset($thong_bao->payload['subject']))
    <div class="mb-3">
        <label class="form-label fw-bold">
            <i class="fas fa-envelope me-1"></i>Chủ đề email
        </label>
        <div class="alert alert-light border">
            <span>{{ $thong_bao->payload['subject'] }}</span>
        </div>
    </div>
    @endif
</div>
@else
<div class="mt-4">
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Không có nội dung thông báo
    </div>
</div>
@endif



