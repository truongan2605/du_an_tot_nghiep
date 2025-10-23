@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-edit me-2 text-primary"></i>Chỉnh sửa thông báo khách hàng
            </h1>
            <p class="text-muted mb-0">Cập nhật thông tin thông báo khách hàng</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.customer-notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
            <a href="{{ route('admin.customer-notifications.show', $notification) }}" class="btn btn-outline-primary">
                <i class="fas fa-eye me-1"></i>Xem chi tiết
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin thông báo</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.customer-notifications.update', $notification) }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Recipient Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="nguoi_nhan_id" class="form-label">Khách hàng cụ thể</label>
                                <select class="form-select" id="nguoi_nhan_id" name="nguoi_nhan_id">
                                    <option value="">Chọn khách hàng cụ thể</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                                {{ old('nguoi_nhan_id', $notification->nguoi_nhan_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('nguoi_nhan_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hoặc gửi cho tất cả khách hàng</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_to_all_customers" value="1" id="send_to_all" {{ old('send_to_all_customers') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="send_to_all">
                                        <i class="fas fa-users me-1 text-success"></i>Gửi cho tất cả khách hàng
                                    </label>
                                </div>
                                @error('send_to_all_customers')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Channel -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="kenh" class="form-label">Kênh gửi <span class="text-danger">*</span></label>
                                <select class="form-select" id="kenh" name="kenh" required>
                                    <option value="">Chọn kênh gửi</option>
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel }}" {{ old('kenh', $notification->kenh) == $channel ? 'selected' : '' }}>
                                            @switch($channel)
                                                @case('email')
                                                    <i class="fas fa-envelope me-1"></i>Email
                                                    @break
                                                @case('in_app')
                                                    <i class="fas fa-bell me-1"></i>In-app
                                                    @break
                                            @endswitch
                                            {{ ucfirst($channel) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kenh')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Template -->
                        <div class="mb-4">
                            <label for="ten_template" class="form-label">Loại thông báo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ten_template" name="ten_template" 
                                   value="{{ old('ten_template', $notification->ten_template) }}" placeholder="Ví dụ: booking_confirmation, promotion_offer" required>
                            @error('ten_template')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Template Selector -->
                        <div class="mb-4">
                            <label for="customer-template-select" class="form-label">Chọn mẫu có sẵn</label>
                            <select class="form-select" id="customer-template-select">
                                <option value="">Chọn mẫu có sẵn...</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Chọn mẫu để tự động điền nội dung JSON
                            </div>
                        </div>

                        <!-- Payload -->
                        <div class="mb-4">
                            <label for="payload" class="form-label">Nội dung thông báo (JSON)</label>
                            <textarea class="form-control" id="payload" name="payload" rows="8" 
                                      placeholder='{"title": "Tiêu đề thông báo", "message": "Nội dung chi tiết", "link": "/account/bookings"}'>{{ old('payload', $notification->payload ? json_encode($notification->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                            <div class="form-text">
                                <strong>Ví dụ JSON:</strong><br>
                                <code>{"title": "Xác nhận đặt phòng", "message": "Đặt phòng thành công! Mã đặt phòng: #12345", "link": "/account/bookings"}</code>
                            </div>
                            @error('payload')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Cập nhật thông báo
                            </button>
                            <a href="{{ route('admin.customer-notifications.show', $notification) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Current Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Trạng thái:</span>
                                @switch($notification->trang_thai)
                                    @case('pending')
                                        <span class="badge bg-warning">Chờ xử lý</span>
                                        @break
                                    @case('sent')
                                        <span class="badge bg-success">Đã gửi</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger">Thất bại</span>
                                        @break
                                    @case('read')
                                        <span class="badge bg-info">Đã đọc</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $notification->trang_thai }}</span>
                                @endswitch
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Ngày tạo:</span>
                                <small class="text-muted">{{ $notification->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Cập nhật cuối:</span>
                                <small class="text-muted">{{ $notification->updated_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Lưu ý khi chỉnh sửa:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-exclamation-triangle text-warning me-1"></i>Thay đổi người nhận sẽ tạo thông báo mới</li>
                        <li><i class="fas fa-exclamation-triangle text-warning me-1"></i>Thay đổi nội dung sẽ cập nhật thông báo hiện tại</li>
                        <li><i class="fas fa-info-circle text-info me-1"></i>JSON phải đúng định dạng</li>
                        <li><i class="fas fa-info-circle text-info me-1"></i>Kiểm tra kỹ trước khi lưu</li>
                    </ul>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thao tác khác
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.customer-notifications.show', $notification) }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                        </a>
                        
                        @if($notification->trang_thai === 'failed')
                            <form method="POST" action="{{ route('admin.customer-notifications.resend', $notification) }}" 
                                  onsubmit="return confirm('Bạn có chắc muốn gửi lại thông báo này?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-redo me-1"></i>Gửi lại
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('admin.customer-notifications.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/notification-templates.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Template selector functionality
    const templateSelect = document.getElementById('customer-template-select');
    const payloadTextarea = document.getElementById('payload');
    
    templateSelect.addEventListener('change', function() {
        const selectedTemplate = this.value;
        if (selectedTemplate) {
            window.notificationTemplates.loadTemplateToTextarea('customer', selectedTemplate, 'payload');
        }
    });

    // Auto-fill JSON example when template changes
    const templateInput = document.getElementById('ten_template');
    
    templateInput.addEventListener('input', function() {
        const template = this.value;
        if (template && !payloadTextarea.value) {
            // Try to find matching template
            const templates = window.notificationTemplates.getTemplatesByType('customer');
            if (templates[template]) {
                window.notificationTemplates.loadTemplateToTextarea('customer', template, 'payload');
            }
        }
    });

    // Validate JSON format
    payloadTextarea.addEventListener('blur', function() {
        window.notificationTemplates.validateJSON(this);
    });

    // Toggle between specific customer and all customers
    const specificCustomerSelect = document.getElementById('nguoi_nhan_id');
    const sendToAllCheckbox = document.getElementById('send_to_all');
    
    sendToAllCheckbox.addEventListener('change', function() {
        if (this.checked) {
            specificCustomerSelect.disabled = true;
            specificCustomerSelect.value = '';
        } else {
            specificCustomerSelect.disabled = false;
        }
    });
    
    specificCustomerSelect.addEventListener('change', function() {
        if (this.value) {
            sendToAllCheckbox.checked = false;
        }
    });
});
</script>
@endpush
