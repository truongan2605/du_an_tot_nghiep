@extends('layouts.admin')

@section('title', 'Batch Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Batch Notifications</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.thong-bao.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tạo thông báo mới
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select class="form-control" id="batch-filter">
                                <option value="">Tất cả batch</option>
                                @foreach($batchIds as $batchId)
                                    <option value="{{ $batchId }}">{{ $batchId }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control" id="status-filter">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending">Đang chờ</option>
                                <option value="sent">Đã gửi</option>
                                <option value="failed">Thất bại</option>
                            </select>
                        </div>
                    </div>

                    <!-- Batch Stats -->
                    <div class="row mb-4">
                        @foreach($batchStats as $batchId => $stats)
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $batchId }}</h6>
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="text-success">
                                                <strong>{{ $stats->sent }}</strong>
                                                <br><small>Đã gửi</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-danger">
                                                <strong>{{ $stats->failed }}</strong>
                                                <br><small>Thất bại</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-warning">
                                                <strong>{{ $stats->pending }}</strong>
                                                <br><small>Đang chờ</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="text-info">
                                                <strong>{{ $stats->total }}</strong>
                                                <br><small>Tổng</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Bắt đầu: {{ $stats->started_at ? $stats->started_at->format('d/m/Y H:i') : 'N/A' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Notifications Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Batch ID</th>
                                    <th>Người nhận</th>
                                    <th>Kênh</th>
                                    <th>Template</th>
                                    <th>Trạng thái</th>
                                    <th>Lần thử</th>
                                    <th>Lỗi</th>
                                    <th>Thời gian</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notifications as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.batch-notifications.show', $notification->batch_id) }}">
                                            {{ $notification->batch_id }}
                                        </a>
                                    </td>
                                    <td>{{ $notification->nguoiNhan->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $notification->kenh }}</span>
                                    </td>
                                    <td>{{ $notification->ten_template }}</td>
                                    <td>
                                        @switch($notification->trang_thai)
                                            @case('sent')
                                                <span class="badge badge-success">Đã gửi</span>
                                                @break
                                            @case('failed')
                                                <span class="badge badge-danger">Thất bại</span>
                                                @break
                                            @case('pending')
                                                <span class="badge badge-warning">Đang chờ</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $notification->trang_thai }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $notification->so_lan_thu }}</td>
                                    <td>
                                        @if($notification->error_message)
                                            <span class="text-danger" title="{{ $notification->error_message }}">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $notification->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($notification->trang_thai === 'failed')
                                            <button class="btn btn-sm btn-warning retry-notification" 
                                                    data-id="{{ $notification->id }}">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Filter functionality
    $('#batch-filter, #status-filter').on('change', function() {
        const batchId = $('#batch-filter').val();
        const status = $('#status-filter').val();
        
        let url = new URL(window.location);
        if (batchId) url.searchParams.set('batch_id', batchId);
        else url.searchParams.delete('batch_id');
        
        if (status) url.searchParams.set('trang_thai', status);
        else url.searchParams.delete('trang_thai');
        
        window.location.href = url.toString();
    });

    // Retry notification
    $('.retry-notification').on('click', function() {
        const notificationId = $(this).data('id');
        
        if (confirm('Bạn có chắc muốn gửi lại thông báo này?')) {
            $.ajax({
                url: `/admin/thong-bao/${notificationId}/resend`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Có lỗi xảy ra: ' + xhr.responseJSON?.message || 'Unknown error');
                }
            });
        }
    });
});
</script>
@endpush







