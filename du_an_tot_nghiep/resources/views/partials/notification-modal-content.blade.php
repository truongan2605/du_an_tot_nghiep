<div class="row">
    <!-- Notification Content -->
    <div class="col-12">
        @if($thong_bao->payload && is_array($thong_bao->payload))
            @if(isset($thong_bao->payload['title']))
            <div class="mb-4">
                <h4 class="fw-bold text-primary">
                    <i class="fas fa-heading me-2"></i>{{ $thong_bao->payload['title'] }}
                </h4>
            </div>
            @endif
            
            @if(isset($thong_bao->payload['message']))
            <div class="mb-4">
                <div class="alert alert-light border-start border-primary border-4">
                    <p class="mb-0 fs-6">{{ $thong_bao->payload['message'] }}</p>
                </div>
            </div>
            @endif
            
            @if(isset($thong_bao->payload['link']))
            <div class="mb-4">
                <a href="{{ $thong_bao->payload['link'] }}" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                </a>
            </div>
            @endif
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Không có nội dung thông báo
            </div>
        @endif
    </div>
</div>

<!-- Notification Info -->
<div class="row mt-4">
    <div class="col-md-6">
        <h6 class="fw-bold text-muted mb-3">
            <i class="fas fa-info-circle me-2"></i>Thông tin thông báo
        </h6>
        
        <div class="mb-3">
            <label class="form-label fw-bold">
                <i class="fas fa-tag me-1"></i>Loại thông báo
            </label>
            <div class="form-control-plaintext">
                <span class="badge bg-primary">{{ $thong_bao->ten_template }}</span>
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
    </div>
    
    <div class="col-md-6">
        <h6 class="fw-bold text-muted mb-3">
            <i class="fas fa-clock me-2"></i>Thời gian
        </h6>
        
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
</div>



