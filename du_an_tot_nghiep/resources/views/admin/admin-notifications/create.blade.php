@extends('layouts.admin')

@section('title', 'Tạo thông báo mới')

@push('styles')
<style>
    .template-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .template-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .template-card.selected {
        border-color: #007bff;
        background-color: #f8f9ff;
    }
    .json-editor {
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
    }
    .preview-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-plus-circle me-2"></i>Tạo thông báo mới
            </h1>
            <p class="text-muted mb-0">Gửi thông báo nội bộ cho admin và nhân viên</p>
        </div>
        <div>
            <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit me-2"></i>Thông tin thông báo
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.admin-notifications.store') }}" id="notificationForm">
                        @csrf
                        
                        <!-- Template Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Chọn template (tùy chọn)</label>
                            <div class="row">
                                @foreach($templates as $key => $name)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" data-template="{{ $key }}">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                                <h6 class="card-title">{{ $name }}</h6>
                                                <small class="text-muted">{{ $key }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Template Name -->
                        <div class="mb-3">
                            <label for="ten_template" class="form-label fw-bold">Tên template <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ten_template') is-invalid @enderror" 
                                   id="ten_template" name="ten_template" value="{{ old('ten_template') }}" 
                                   placeholder="Ví dụ: system_maintenance, new_booking..." required>
                            @error('ten_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- JSON Payload -->
                        <div class="mb-4">
                            <label for="payload" class="form-label fw-bold">Nội dung JSON <span class="text-danger">*</span></label>
                            <textarea class="form-control json-editor @error('payload') is-invalid @enderror" 
                                      id="payload" name="payload" rows="8" 
                                      placeholder='{"title": "Tiêu đề", "message": "Nội dung", "link": "/admin", "subject": "Chủ đề"}' 
                                      required>{{ old('payload') }}</textarea>
                            @error('payload')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Định dạng JSON với các trường: title, message, link, subject
                            </div>
                        </div>

                        <!-- Recipients -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Người nhận <span class="text-danger">*</span></label>
                            <div class="row">
                                @foreach($users as $user)
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="nguoi_nhan_ids[]" value="{{ $user->id }}" 
                                                   id="user_{{ $user->id }}"
                                                   {{ in_array($user->id, old('nguoi_nhan_ids', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="user_{{ $user->id }}">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $user->name }}</div>
                                                    <small class="text-muted">{{ $user->vai_tro }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('nguoi_nhan_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Tạo & Gửi thông báo
                            </button>
                            <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Preview Card -->
            <div class="card preview-card mb-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-eye me-2"></i>Xem trước thông báo
                    </h6>
                    <div id="preview-content">
                        <div class="text-center text-muted">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i>
                            <p class="mb-0">Nhập nội dung để xem trước</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-magic me-2"></i>Thao tác nhanh
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllUsers()">
                            <i class="fas fa-check-double me-1"></i>Chọn tất cả
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAllUsers()">
                            <i class="fas fa-times me-1"></i>Bỏ chọn tất cả
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="selectAdminsOnly()">
                            <i class="fas fa-user-shield me-1"></i>Chỉ admin
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="selectStaffOnly()">
                            <i class="fas fa-users me-1"></i>Chỉ nhân viên
                        </button>
                    </div>
                </div>
            </div>

            <!-- Help -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <h6>JSON Format:</h6>
                        <pre class="bg-light p-2 rounded small">{
  "title": "Tiêu đề thông báo",
  "message": "Nội dung chi tiết",
  "link": "/admin/dashboard",
  "subject": "Chủ đề email"
}</pre>
                        
                        <h6 class="mt-3">Templates có sẵn:</h6>
                        <ul class="list-unstyled small">
                            @foreach($templates as $key => $name)
                                <li><code>{{ $key }}</code> - {{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Template selection
document.querySelectorAll('.template-card').forEach(card => {
    card.addEventListener('click', function() {
        // Remove selected class from all cards
        document.querySelectorAll('.template-card').forEach(c => c.classList.remove('selected'));
        // Add selected class to clicked card
        this.classList.add('selected');
        
        // Auto-fill form based on template
        const template = this.dataset.template;
        fillTemplate(template);
    });
});

// Template data
const templateData = {
    'system_maintenance': {
        title: 'Bảo trì hệ thống',
        message: 'Hệ thống sẽ được bảo trì từ {start_time} đến {end_time}. Vui lòng lưu công việc và đăng xuất.',
        link: '/admin/maintenance',
        subject: 'Thông báo bảo trì hệ thống'
    },
    'new_booking': {
        title: 'Đặt phòng mới',
        message: 'Có đặt phòng mới từ khách hàng {customer_name}. Mã đặt phòng: {booking_reference}',
        link: '/admin/bookings',
        subject: 'Đặt phòng mới cần xử lý'
    },
    'payment_received': {
        title: 'Thanh toán nhận được',
        message: 'Đã nhận thanh toán {amount} VNĐ cho đặt phòng {booking_reference}',
        link: '/admin/payments',
        subject: 'Xác nhận thanh toán'
    },
    'booking_cancelled': {
        title: 'Hủy đặt phòng',
        message: 'Đặt phòng {booking_reference} đã bị hủy bởi {customer_name}',
        link: '/admin/bookings',
        subject: 'Thông báo hủy đặt phòng'
    },
    'system_alert': {
        title: 'Cảnh báo hệ thống',
        message: 'Có vấn đề với hệ thống: {alert_message}. Vui lòng kiểm tra ngay.',
        link: '/admin/system',
        subject: 'Cảnh báo hệ thống'
    },
    'daily_report': {
        title: 'Báo cáo hàng ngày',
        message: 'Báo cáo hoạt động ngày {date}: {summary}',
        link: '/admin/reports',
        subject: 'Báo cáo hàng ngày'
    },
    'monthly_summary': {
        title: 'Tóm tắt tháng',
        message: 'Báo cáo tổng kết tháng {month}: {summary}',
        link: '/admin/reports/monthly',
        subject: 'Báo cáo tháng'
    }
};

function fillTemplate(template) {
    if (templateData[template]) {
        const data = templateData[template];
        document.getElementById('ten_template').value = template;
        document.getElementById('payload').value = JSON.stringify(data, null, 2);
        updatePreview();
    }
}

// Update preview
function updatePreview() {
    const payloadText = document.getElementById('payload').value;
    const previewDiv = document.getElementById('preview-content');
    
    try {
        const payload = JSON.parse(payloadText);
        previewDiv.innerHTML = `
            <div class="notification-preview">
                <h6 class="fw-bold mb-2">${payload.title || 'Tiêu đề'}</h6>
                <p class="mb-2">${payload.message || 'Nội dung'}</p>
                ${payload.link ? `<small><i class="fas fa-link me-1"></i>${payload.link}</small>` : ''}
            </div>
        `;
    } catch (e) {
        previewDiv.innerHTML = `
            <div class="text-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>
                JSON không hợp lệ
            </div>
        `;
    }
}

// Listen to payload changes
document.getElementById('payload').addEventListener('input', updatePreview);

// User selection functions
function selectAllUsers() {
    document.querySelectorAll('input[name="nguoi_nhan_ids[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllUsers() {
    document.querySelectorAll('input[name="nguoi_nhan_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function selectAdminsOnly() {
    document.querySelectorAll('input[name="nguoi_nhan_ids[]"]').forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        checkbox.checked = label.textContent.includes('admin');
    });
}

function selectStaffOnly() {
    document.querySelectorAll('input[name="nguoi_nhan_ids[]"]').forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        checkbox.checked = label.textContent.includes('nhan_vien');
    });
}

// Form validation
document.getElementById('notificationForm').addEventListener('submit', function(e) {
    const selectedUsers = document.querySelectorAll('input[name="nguoi_nhan_ids[]"]:checked');
    if (selectedUsers.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một người nhận');
        return;
    }
    
    try {
        JSON.parse(document.getElementById('payload').value);
    } catch (e) {
        e.preventDefault();
        alert('JSON không hợp lệ. Vui lòng kiểm tra lại định dạng.');
        return;
    }
});

// Initialize preview
updatePreview();
</script>
@endpush







