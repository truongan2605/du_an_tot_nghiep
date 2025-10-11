@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-bell me-2 text-primary"></i>Quản lý thông báo
            </h1>
            <p class="text-muted mb-0">Quản lý và theo dõi tất cả thông báo hệ thống</p>
        </div>
        <a href="{{ route('admin.thong-bao.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Tạo thông báo mới
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $thongBaos->total() }}</h4>
                            <p class="mb-0">Tổng thông báo</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bell fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $thongBaos->where('trang_thai', 'pending')->count() }}</h4>
                            <p class="mb-0">Chờ xử lý</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $thongBaos->where('trang_thai', 'sent')->count() }}</h4>
                            <p class="mb-0">Đã gửi</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $thongBaos->where('trang_thai', 'failed')->count() }}</h4>
                            <p class="mb-0">Gửi thất bại</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" value="{{ request('q') }}" 
                           placeholder="Tìm theo template, kênh, trạng thái..." 
                           class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kênh</label>
                    <select name="kenh" class="form-select">
                        <option value="">Tất cả kênh</option>
                        <option value="email" @selected(request('kenh') == 'email')>Email</option>
                        <option value="sms" @selected(request('kenh') == 'sms')>SMS</option>
                        <option value="in_app" @selected(request('kenh') == 'in_app')>In-app</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" @selected(request('trang_thai') == 'pending')>Chờ xử lý</option>
                        <option value="sent" @selected(request('trang_thai') == 'sent')>Đã gửi</option>
                        <option value="failed" @selected(request('trang_thai') == 'failed')>Gửi thất bại</option>
                        <option value="read" @selected(request('trang_thai') == 'read')>Đã đọc</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Lọc
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách thông báo
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">ID</th>
                            <th>Người nhận</th>
                            <th width="100">Kênh</th>
                            <th>Template</th>
                            <th width="120">Trạng thái</th>
                            <th width="100">Lần thử</th>
                            <th width="150">Thời gian</th>
                            <th width="200">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($thongBaos as $item)
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark">#{{ $item->id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ optional($item->nguoiNhan)->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ optional($item->nguoiNhan)->email ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @switch($item->kenh)
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
                                            <span class="badge bg-secondary">{{ $item->kenh }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $item->ten_template }}</strong>
                                        @if($item->payload && isset($item->payload['title']))
                                            <br><small class="text-muted">{{ $item->payload['title'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($item->trang_thai)
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
                                                <i class="fas fa-times me-1"></i>Thất bại
                                            </span>
                                            @break
                                        @case('read')
                                            <span class="badge bg-info">
                                                <i class="fas fa-eye me-1"></i>Đã đọc
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $item->trang_thai }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $item->so_lan_thu ?? 0 }}</span>
                                </td>
                                <td>
                                    <div>
                                        <small class="text-muted">{{ $item->created_at?->format('d/m/Y') }}</small>
                                        <br><small class="text-muted">{{ $item->created_at?->format('H:i') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.thong-bao.show', $item) }}" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.thong-bao.edit', $item) }}" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($item->trang_thai === 'failed')
                                            <form action="{{ route('admin.thong-bao.resend', $item) }}" 
                                                  method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Gửi lại thông báo này?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Gửi lại">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.thong-bao.destroy', $item) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Xóa thông báo này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-bell-slash fa-3x mb-3"></i>
                                        <h5>Không có thông báo</h5>
                                        <p>Chưa có thông báo nào được tạo.</p>
                                        <a href="{{ route('admin.thong-bao.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Tạo thông báo đầu tiên
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($thongBaos->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Hiển thị {{ $thongBaos->firstItem() }} - {{ $thongBaos->lastItem() }} 
                    trong {{ $thongBaos->total() }} thông báo
                </div>
                <div>
                    {{ $thongBaos->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75em;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}

.btn-group .btn:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

.stats-card {
    transition: transform 0.2s ease-in-out;
}

.stats-card:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
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



