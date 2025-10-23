@extends('layouts.admin')

@section('title', 'Thông báo Admin/Nhân viên')

@push('styles')
<style>
    .notification-card {
        transition: all 0.3s ease;
        border-left: 4px solid #dee2e6;
    }
    .notification-card.unread {
        border-left-color: #007bff;
        background-color: #f8f9fa;
    }
    .notification-card.read {
        opacity: 0.7;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .stats-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .stats-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .stats-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-bell me-2"></i>Thông báo Admin/Nhân viên
            </h1>
            <p class="text-muted mb-0">Quản lý thông báo nội bộ cho admin và nhân viên</p>
        </div>
        <div>
            <a href="{{ route('admin.admin-notifications.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tạo thông báo mới
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Tổng thông báo</div>
                            <div class="h5 mb-0 font-weight-bold">{{ $stats['total'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Đã gửi</div>
                            <div class="h5 mb-0 font-weight-bold">{{ $stats['sent'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Chưa đọc</div>
                            <div class="h5 mb-0 font-weight-bold">{{ $stats['unread'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Thất bại</div>
                            <div class="h5 mb-0 font-weight-bold">{{ $stats['failed'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.admin-notifications.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Tìm theo nội dung, người nhận...">
                </div>
                <div class="col-md-3">
                    <label for="trang_thai" class="form-label">Trạng thái</label>
                    <select class="form-select" id="trang_thai" name="trang_thai">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" {{ request('trang_thai') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                        <option value="sent" {{ request('trang_thai') == 'sent' ? 'selected' : '' }}>Đã gửi</option>
                        <option value="read" {{ request('trang_thai') == 'read' ? 'selected' : '' }}>Đã đọc</option>
                        <option value="failed" {{ request('trang_thai') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="nguoi_nhan_id" class="form-label">Người nhận</label>
                    <select class="form-select" id="nguoi_nhan_id" name="nguoi_nhan_id">
                        <option value="">Tất cả người nhận</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('nguoi_nhan_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->vai_tro }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách thông báo</h6>
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="markAllAsRead()">
                    <i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($notifications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Nội dung</th>
                                <th width="15%">Người nhận</th>
                                <th width="15%">Template</th>
                                <th width="10%">Trạng thái</th>
                                <th width="15%">Thời gian</th>
                                <th width="15%">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                @php
                                    $payload = $notification->payload; // Model already handles conversion
                                    $isUnread = $notification->trang_thai !== 'read';
                                @endphp
                                <tr class="notification-row {{ $isUnread ? 'table-warning' : '' }}">
                                    <td>
                                        @if($isUnread)
                                            <span class="badge bg-primary rounded-pill">Mới</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong class="text-dark">{{ $payload['title'] ?? 'Không có tiêu đề' }}</strong>
                                            <small class="text-muted">{{ Str::limit($payload['message'] ?? 'Không có nội dung', 50) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ strtoupper(substr($notification->nguoiNhan->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $notification->nguoiNhan->name }}</div>
                                                <small class="text-muted">{{ $notification->nguoiNhan->vai_tro }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $notification->ten_template }}</span>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <div>{{ $notification->created_at->format('d/m/Y') }}</div>
                                            <small>{{ $notification->created_at->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.admin-notifications.show', $notification->id) }}" 
                                               class="btn btn-outline-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($notification->trang_thai !== 'read')
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="markAsRead({{ $notification->id }})" title="Đánh dấu đã đọc">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            @if(auth()->user()->vai_tro === 'admin')
                                                <form method="POST" action="{{ route('admin.admin-notifications.destroy', $notification->id) }}" 
                                                      class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {{ $notifications->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có thông báo nào</h5>
                    <p class="text-muted">Chưa có thông báo nào được tạo hoặc không tìm thấy thông báo phù hợp.</p>
                    <a href="{{ route('admin.admin-notifications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo thông báo đầu tiên
                    </a>
                </div>
            @endif
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

function markAllAsRead() {
    if (confirm('Bạn có chắc muốn đánh dấu tất cả thông báo đã đọc?')) {
        fetch('/admin/admin-notifications/mark-all-read', {
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
            alert('Có lỗi xảy ra khi đánh dấu tất cả thông báo đã đọc');
        });
    }
}
</script>
@endpush
