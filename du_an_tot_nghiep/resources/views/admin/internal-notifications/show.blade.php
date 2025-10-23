@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-eye me-2 text-primary"></i>Chi tiết thông báo nội bộ
            </h1>
            <p class="text-muted mb-0">Thông tin chi tiết về thông báo nội bộ</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.internal-notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
            <a href="{{ route('admin.internal-notifications.edit', $notification) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Chỉnh sửa
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
                                    @switch($notification->trang_thai)
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
                                        @switch($notification->trang_thai)
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
                                                <span class="text-secondary">{{ $notification->trang_thai }}</span>
                                        @endswitch
                                    </h4>
                                    <p class="text-muted mb-0">
                                        @switch($notification->kenh)
                                            @case('email')
                                                <i class="fas fa-envelope me-1"></i>Email
                                                @break
                                            @case('in_app')
                                                <i class="fas fa-bell me-1"></i>In-app
                                                @break
                                            @default
                                                {{ $notification->kenh }}
                                        @endswitch
                                        • {{ $notification->ten_template }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-muted small">
                                <div><i class="fas fa-calendar me-1"></i>{{ $notification->created_at->format('d/m/Y') }}</div>
                                <div><i class="fas fa-clock me-1"></i>{{ $notification->created_at->format('H:i') }}</div>
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
                    @if($notification->payload && is_array($notification->payload))
                        @if(isset($notification->payload['title']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-heading me-1"></i>Tiêu đề
                            </h6>
                            <div class="alert alert-primary border-0">
                                <h5 class="mb-0">{{ $notification->payload['title'] }}</h5>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($notification->payload['message']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-comment me-1"></i>Nội dung
                            </h6>
                            <div class="alert alert-light border">
                                <p class="mb-0 fs-6">{{ $notification->payload['message'] }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($notification->payload['link']))
                        <div class="mb-4">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-link me-1"></i>Liên kết
                            </h6>
                            <div class="alert alert-info border-0">
                                <a href="{{ $notification->payload['link'] }}" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                                </a>
                                <small class="text-muted d-block mt-2">{{ $notification->payload['link'] }}</small>
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
                                <span class="badge bg-primary fs-6">{{ $notification->ten_template }}</span>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-broadcast-tower me-1"></i>Kênh
                                </span>
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
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-plus me-1"></i>Ngày tạo
                                </span>
                                <small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-edit me-1"></i>Cập nhật cuối
                                </span>
                                <small class="text-muted">{{ $notification->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipient Info -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Người nhận
                    </h5>
                </div>
                <div class="card-body">
                    @if($notification->nguoiNhan)
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <img src="{{ $notification->nguoiNhan->avatar ? asset('storage/' . $notification->nguoiNhan->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}" 
                                     alt="avatar" class="rounded-circle" style="width: 50px; height: 50px;">
                            </div>
                            <div>
                                <div class="fw-bold">{{ $notification->nguoiNhan->name }}</div>
                                <small class="text-muted">{{ $notification->nguoiNhan->email }}</small>
                                <br>
                                <span class="badge bg-{{ $notification->nguoiNhan->vai_tro === 'admin' ? 'warning' : 'info' }}">
                                    {{ ucfirst($notification->nguoiNhan->vai_tro) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-muted">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Không tìm thấy thông tin người nhận
                        </div>
                    @endif
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
                        <a href="{{ route('admin.internal-notifications.edit', $notification) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Chỉnh sửa
                        </a>
                        
                        @if($notification->trang_thai === 'failed')
                            <form method="POST" action="{{ route('admin.internal-notifications.resend', $notification) }}" 
                                  onsubmit="return confirm('Bạn có chắc muốn gửi lại thông báo này?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-redo me-1"></i>Gửi lại
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('admin.internal-notifications.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                        </a>
                        
                        @if(isset($notification->payload['link']))
                            <a href="{{ $notification->payload['link'] }}" class="btn btn-outline-info" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i>Xem liên kết
                            </a>
                        @endif
                        
                        <form method="POST" action="{{ route('admin.internal-notifications.destroy', $notification) }}" 
                              onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i>Xóa thông báo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




