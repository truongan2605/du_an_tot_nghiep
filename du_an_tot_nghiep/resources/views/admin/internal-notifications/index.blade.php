@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-building me-2 text-primary"></i>Thông báo nội bộ
            </h1>
            <p class="text-muted mb-0">Quản lý thông báo gửi cho admin và nhân viên</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.internal-notifications.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tạo thông báo mới
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        @php
            $totalCount = $notifications->total();
            $sentCount = $notifications->where('trang_thai', 'sent')->count();
            $pendingCount = $notifications->where('trang_thai', 'pending')->count();
            $failedCount = $notifications->where('trang_thai', 'failed')->count();
        @endphp
        
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Tổng thông báo</h6>
                            <h3 class="mb-0">{{ $totalCount }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bell fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Đã gửi</h6>
                            <h3 class="mb-0">{{ $sentCount }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Chờ xử lý</h6>
                            <h3 class="mb-0">{{ $pendingCount }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Thất bại</h6>
                            <h3 class="mb-0">{{ $failedCount }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.internal-notifications.index') }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="trang_thai" class="form-label">Trạng thái</label>
                        <select class="form-select" id="trang_thai" name="trang_thai">
                            <option value="">Tất cả</option>
                            <option value="pending" {{ request('trang_thai') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="sent" {{ request('trang_thai') == 'sent' ? 'selected' : '' }}>Đã gửi</option>
                            <option value="failed" {{ request('trang_thai') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                            <option value="read" {{ request('trang_thai') == 'read' ? 'selected' : '' }}>Đã đọc</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="vai_tro" class="form-label">Vai trò</label>
                        <select class="form-select" id="vai_tro" name="vai_tro">
                            <option value="">Tất cả</option>
                            <option value="admin" {{ request('vai_tro') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="nhan_vien" {{ request('vai_tro') == 'nhan_vien' ? 'selected' : '' }}>Nhân viên</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="nguoi_nhan_id" class="form-label">Người nhận</label>
                        <select class="form-select" id="nguoi_nhan_id" name="nguoi_nhan_id">
                            <option value="">Tất cả</option>
                            @foreach($staff as $person)
                                <option value="{{ $person->id }}" {{ request('nguoi_nhan_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }} ({{ ucfirst($person->vai_tro) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Tìm kiếm</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Tìm kiếm theo nội dung...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Lọc
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Danh sách thông báo nội bộ</h5>
        </div>
        <div class="card-body p-0">
            @if($notifications && $notifications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Người nhận</th>
                                <th>Loại thông báo</th>
                                <th>Kênh</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $notification->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <img src="{{ $notification->nguoiNhan && $notification->nguoiNhan->avatar ? asset('storage/' . $notification->nguoiNhan->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}" 
                                                     alt="avatar" class="rounded-circle" style="width: 32px; height: 32px;">
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $notification->nguoiNhan ? $notification->nguoiNhan->name : 'N/A' }}</div>
                                                <small class="text-muted">
                                                    {{ $notification->nguoiNhan ? ucfirst($notification->nguoiNhan->vai_tro) : 'N/A' }} • {{ $notification->nguoiNhan ? $notification->nguoiNhan->email : 'N/A' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $notification->ten_template }}</span>
                                        @if($notification->payload && isset($notification->payload['title']))
                                            <br><small class="text-muted">{{ $notification->payload['title'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($notification->kenh)
                                            @case('email')
                                                <span class="badge bg-info">
                                                    <i class="fas fa-envelope me-1"></i>Email
                                                </span>
                                                @break
                                            @case('in_app')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-bell me-1"></i>In-app
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $notification->kenh }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($notification->trang_thai)
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
                                                <span class="badge bg-secondary">{{ $notification->trang_thai }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <div>{{ $notification->created_at ? $notification->created_at->format('d/m/Y') : 'N/A' }}</div>
                                            <small>{{ $notification->created_at ? $notification->created_at->format('H:i') : 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.internal-notifications.show', $notification) }}" 
                                               class="btn btn-outline-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.internal-notifications.edit', $notification) }}" 
                                               class="btn btn-outline-secondary" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($notification->trang_thai === 'failed')
                                                <form method="POST" action="{{ route('admin.internal-notifications.resend', $notification) }}" 
                                                      class="d-inline" onsubmit="return confirm('Bạn có chắc muốn gửi lại thông báo này?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning" title="Gửi lại">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.internal-notifications.destroy', $notification) }}" 
                                                  class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có thông báo nội bộ nào</h5>
                    <p class="text-muted">Chưa có thông báo nào được gửi cho admin và nhân viên.</p>
                    <a href="{{ route('admin.internal-notifications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo thông báo đầu tiên
                    </a>
                </div>
            @endif
        </div>
        @if($notifications && $notifications->hasPages())
            <div class="card-footer bg-light border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Hiển thị <strong>{{ $notifications->firstItem() }}</strong> đến 
                            <strong>{{ $notifications->lastItem() }}</strong> trong tổng số 
                            <strong>{{ $notifications->total() }}</strong> kết quả
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-md-end justify-content-center">
                            {{ $notifications->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
