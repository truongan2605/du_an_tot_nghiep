@extends('layouts.admin')

@section('title', 'Chi tiết thông báo')

@push('styles')
<style>
    .notification-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    .json-viewer {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
    }
    .info-card {
        border-left: 4px solid #007bff;
    }
    .content-card {
        border-left: 4px solid #28a745;
    }
    .system-card {
        border-left: 4px solid #ffc107;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-eye me-2"></i>Chi tiết thông báo
            </h1>
            <p class="text-muted mb-0">Thông tin chi tiết về thông báo</p>
        </div>
        <div>
            <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Notification Header -->
            <div class="card notification-header mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            @php
                                $payload = $notification->payload; // Model already handles conversion
                            @endphp
                            <h4 class="mb-2">{{ $payload['title'] ?? 'Không có tiêu đề' }}</h4>
                            <p class="mb-0 opacity-75">{{ $payload['message'] ?? 'Không có nội dung' }}</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            @php
                                $statusConfig = [
                                    'pending' => ['class' => 'bg-warning', 'text' => 'Chờ xử lý'],
                                    'sent' => ['class' => 'bg-success', 'text' => 'Đã gửi'],
                                    'read' => ['class' => 'bg-primary', 'text' => 'Đã đọc'],
                                    'failed' => ['class' => 'bg-danger', 'text' => 'Thất bại'],
                                ];
                                $config = $statusConfig[$notification->trang_thai] ?? $statusConfig['pending'];
                            @endphp
                            <span class="badge {{ $config['class'] }} status-badge">{{ $config['text'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Content -->
            <div class="card content-card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-alt me-2"></i>Nội dung thông báo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Tiêu đề</h6>
                            <p class="fw-bold">{{ $payload['title'] ?? 'Không có tiêu đề' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Chủ đề email</h6>
                            <p class="fw-bold">{{ $payload['subject'] ?? 'Không có chủ đề' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Nội dung</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $payload['message'] ?? 'Không có nội dung' }}
                            </div>
                        </div>
                    </div>
                    @if(isset($payload['link']))
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-muted mb-2">Liên kết</h6>
                                <a href="{{ $payload['link'] }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>{{ $payload['link'] }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Raw JSON Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-code me-2"></i>Dữ liệu JSON
                    </h6>
                </div>
                <div class="card-body">
                    <pre class="json-viewer p-3 mb-0">{{ json_encode($notification->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Basic Information -->
            <div class="card info-card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">ID thông báo</h6>
                        <p class="fw-bold mb-0">#{{ $notification->id }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Template</h6>
                        <span class="badge bg-info">{{ $notification->ten_template }}</span>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Kênh</h6>
                        <span class="badge bg-secondary">{{ $notification->kenh }}</span>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Người tạo</h6>
                        <p class="fw-bold mb-0">{{ auth()->user()->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Recipient Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user me-2"></i>Người nhận
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                            {{ strtoupper(substr($notification->nguoiNhan->name, 0, 1)) }}
                        </div>
                        <div>
                            <h6 class="mb-1">{{ $notification->nguoiNhan->name }}</h6>
                            <p class="text-muted mb-0">{{ $notification->nguoiNhan->email }}</p>
                            <span class="badge bg-info">{{ $notification->nguoiNhan->vai_tro }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="card system-card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog me-2"></i>Thông tin hệ thống
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Trạng thái</h6>
                        @php
                            $statusConfig = [
                                'pending' => ['class' => 'bg-warning', 'text' => 'Chờ xử lý'],
                                'sent' => ['class' => 'bg-success', 'text' => 'Đã gửi'],
                                'read' => ['class' => 'bg-primary', 'text' => 'Đã đọc'],
                                'failed' => ['class' => 'bg-danger', 'text' => 'Thất bại'],
                            ];
                            $config = $statusConfig[$notification->trang_thai] ?? $statusConfig['pending'];
                        @endphp
                        <span class="badge {{ $config['class'] }}">{{ $config['text'] }}</span>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Số lần thử</h6>
                        <p class="fw-bold mb-0">{{ $notification->so_lan_thu }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Lần thử cuối</h6>
                        <p class="fw-bold mb-0">{{ $notification->lan_thu_cuoi ? $notification->lan_thu_cuoi->format('d/m/Y H:i:s') : 'Chưa có' }}</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Tạo lúc</h6>
                        <p class="fw-bold mb-0">{{ $notification->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="mb-0">
                        <h6 class="text-muted mb-1">Cập nhật lúc</h6>
                        <p class="fw-bold mb-0">{{ $notification->updated_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools me-2"></i>Hành động
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($notification->trang_thai !== 'read')
                            <button type="button" class="btn btn-success" onclick="markAsRead({{ $notification->id }})">
                                <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                            </button>
                        @endif
                        
                        @if($notification->trang_thai === 'failed')
                            <button type="button" class="btn btn-warning" onclick="resendNotification({{ $notification->id }})">
                                <i class="fas fa-redo me-1"></i>Gửi lại
                            </button>
                        @endif
                        
                        @if(auth()->user()->vai_tro === 'admin')
                            <form method="POST" action="{{ route('admin.admin-notifications.destroy', $notification->id) }}" 
                                  onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash me-1"></i>Xóa thông báo
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('admin.admin-notifications.edit', $notification->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Chỉnh sửa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/admin/admin-notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi đánh dấu thông báo đã đọc');
    });
}

function resendNotification(notificationId) {
    if (confirm('Bạn có chắc muốn gửi lại thông báo này?')) {
        // Implement resend logic here
        alert('Đang chuyển trang');
    }
}
</script>
@endpush
