@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-plus me-2 text-primary"></i>Tạo thông báo nội bộ mới
            </h1>
            <p class="text-muted mb-0">Gửi thông báo cho admin và nhân viên</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.internal-notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
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
                    <form method="POST" action="{{ route('admin.internal-notifications.store') }}">
                        @csrf
                        
                        <!-- Recipient Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="nguoi_nhan_id" class="form-label">Người nhận cụ thể</label>
                                <select class="form-select" id="nguoi_nhan_id" name="nguoi_nhan_id">
                                    <option value="">Chọn người nhận cụ thể</option>
                                    @foreach($staff as $person)
                                        <option value="{{ $person->id }}" {{ old('nguoi_nhan_id') == $person->id ? 'selected' : '' }}>
                                            {{ $person->name }} ({{ ucfirst($person->vai_tro) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('nguoi_nhan_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hoặc gửi theo vai trò</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vai_tro_broadcast[]" value="admin" id="broadcast_admin" {{ in_array('admin', old('vai_tro_broadcast', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="broadcast_admin">
                                        <i class="fas fa-crown me-1 text-warning"></i>Admin
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="vai_tro_broadcast[]" value="nhan_vien" id="broadcast_nhan_vien" {{ in_array('nhan_vien', old('vai_tro_broadcast', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="broadcast_nhan_vien">
                                        <i class="fas fa-user-tie me-1 text-info"></i>Nhân viên
                                    </label>
                                </div>
                                @error('vai_tro_broadcast')
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
                                        <option value="{{ $channel }}" {{ old('kenh') == $channel ? 'selected' : '' }}>
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
                                   value="{{ old('ten_template') }}" placeholder="Nhập tên template (vd: system_announcement, meeting_reminder...)" required
                                   list="internal-template-suggestions">
                            <datalist id="internal-template-suggestions">
                                <option value="system_announcement">Thông báo hệ thống</option>
                                <option value="meeting_reminder">Nhắc nhở họp</option>
                                <option value="task_assignment">Giao nhiệm vụ</option>
                                <option value="deadline_reminder">Nhắc nhở deadline</option>
                                <option value="policy_update">Cập nhật chính sách</option>
                                <option value="training_reminder">Nhắc nhở đào tạo</option>
                                <option value="security_alert">Cảnh báo bảo mật</option>
                            </datalist>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Nhập tên template để tự động load JSON. Gợi ý: system_announcement, meeting_reminder, task_assignment...
                            </div>
                            @error('ten_template')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

        <!-- Template Selector - Hidden since auto-load from template name -->
        <div class="mb-4" style="display: none;">
            <label for="internal-template-select" class="form-label">Chọn mẫu có sẵn</label>
            <select class="form-select" id="internal-template-select">
                <option value="">Chọn mẫu có sẵn...</option>
                <option value="system_announcement">Thông báo hệ thống (system)</option>
                <option value="meeting_reminder">Nhắc nhở họp (meeting)</option>
                <option value="task_assignment">Giao nhiệm vụ (task)</option>
                <option value="deadline_reminder">Nhắc nhở deadline (deadline)</option>
                <option value="policy_update">Cập nhật chính sách (policy)</option>
                <option value="training_reminder">Nhắc nhở đào tạo (training)</option>
                <option value="security_alert">Cảnh báo bảo mật (security)</option>
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
                                      placeholder='{"title": "Tiêu đề thông báo", "message": "Nội dung chi tiết", "link": "/admin/dashboard"}'>{{ old('payload') }}</textarea>
                            <div class="form-text">
                                <strong>Ví dụ JSON:</strong><br>
                                <code>{"title": "Họp team", "message": "Họp team lúc 14:00 ngày mai", "link": "/admin/meetings"}</code>
                            </div>
                            @error('payload')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Gửi thông báo
                            </button>
                            <a href="{{ route('admin.internal-notifications.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Help Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Hướng dẫn
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Chọn người nhận:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-1"></i>Chọn người cụ thể để gửi riêng lẻ</li>
                        <li><i class="fas fa-check text-success me-1"></i>Chọn vai trò để gửi hàng loạt</li>
                        <li><i class="fas fa-check text-success me-1"></i>Có thể chọn cả admin và nhân viên</li>
                    </ul>

                    <h6 class="mt-3">Kênh gửi:</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-envelope text-info me-1"></i><strong>Email:</strong> Gửi qua email</li>
                        <li><i class="fas fa-bell text-success me-1"></i><strong>In-app:</strong> Thông báo trong hệ thống</li>
                    </ul>

                    <h6 class="mt-3">Nội dung JSON:</h6>
                    <ul class="list-unstyled small">
                        <li><code>title</code>: Tiêu đề thông báo</li>
                        <li><code>message</code>: Nội dung chi tiết</li>
                        <li><code>link</code>: Liên kết (tùy chọn)</li>
                    </ul>
                </div>
            </div>

            <!-- Staff List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Danh sách nhân viên
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($staff as $person)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold">{{ $person->name }}</div>
                                    <small class="text-muted">{{ $person->email }}</small>
                                </div>
                                <span class="badge bg-{{ $person->vai_tro === 'admin' ? 'warning' : 'info' }}">
                                    {{ ucfirst($person->vai_tro) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Notification Templates Manager
class NotificationTemplates {
    constructor() {
        this.templates = {
            internal: {
                system_announcement: {
                    title: "Thông báo hệ thống",
                    message: "Hệ thống sẽ bảo trì vào 2h sáng ngày mai. Vui lòng lưu lại công việc trước thời gian này.",
                    link: "/admin/announcements",
                    type: "system"
                },
                meeting_reminder: {
                    title: "Nhắc nhở họp",
                    message: "Họp team lúc 14:00 ngày mai tại phòng họp A. Vui lòng chuẩn bị báo cáo tiến độ.",
                    link: "/admin/meetings",
                    type: "meeting"
                },
                task_assignment: {
                    title: "Giao nhiệm vụ",
                    message: "Bạn được giao nhiệm vụ mới cần hoàn thành trước 17:00 ngày mai. Vui lòng kiểm tra chi tiết.",
                    link: "/admin/tasks",
                    type: "task"
                },
                deadline_reminder: {
                    title: "Nhắc nhở deadline",
                    message: "Dự án ABC cần hoàn thành trước 18:00 hôm nay. Vui lòng kiểm tra tiến độ.",
                    link: "/admin/projects",
                    type: "deadline"
                },
                policy_update: {
                    title: "Cập nhật chính sách",
                    message: "Có chính sách mới được cập nhật. Vui lòng đọc và xác nhận đã hiểu.",
                    link: "/admin/policies",
                    type: "policy"
                },
                training_reminder: {
                    title: "Nhắc nhở đào tạo",
                    message: "Khóa đào tạo về quy trình mới sẽ diễn ra vào 9:00 sáng mai. Vui lòng tham gia đầy đủ.",
                    link: "/admin/training",
                    type: "training"
                },
                security_alert: {
                    title: "Cảnh báo bảo mật",
                    message: "Phát hiện hoạt động đăng nhập bất thường. Vui lòng kiểm tra và báo cáo nếu cần.",
                    link: "/admin/security",
                    type: "security"
                }
            }
        };
    }

    getTemplate(type, templateName) {
        if (this.templates[type] && this.templates[type][templateName]) {
            return this.templates[type][templateName];
        }
        return null;
    }

    getTemplatesByType(type) {
        return this.templates[type] || {};
    }

    getTemplateJSON(type, templateName) {
        const template = this.getTemplate(type, templateName);
        if (template) {
            return JSON.stringify(template, null, 2);
        }
        return null;
    }

    createTemplateDropdown(type, selectId) {
        const templates = this.getTemplatesByType(type);
        const select = document.getElementById(selectId);
        
        if (!select) return;

        select.innerHTML = '<option value="">Chọn mẫu có sẵn...</option>';

        Object.keys(templates).forEach(key => {
            const template = templates[key];
            const option = document.createElement('option');
            option.value = key;
            option.textContent = `${template.title} (${template.type})`;
            select.appendChild(option);
        });
    }

    loadTemplateToTextarea(type, templateName, textareaId) {
        const jsonString = this.getTemplateJSON(type, templateName);
        const textarea = document.getElementById(textareaId);
        
        if (jsonString && textarea) {
            textarea.value = jsonString;
            this.validateJSON(textarea);
        }
    }

    validateJSON(textarea) {
        const value = textarea.value.trim();
        if (value) {
            try {
                JSON.parse(value);
                textarea.classList.remove('is-invalid');
                textarea.classList.add('is-valid');
            } catch (e) {
                textarea.classList.remove('is-valid');
                textarea.classList.add('is-invalid');
            }
        } else {
            textarea.classList.remove('is-valid', 'is-invalid');
        }
    }
}

// Global instance
window.notificationTemplates = new NotificationTemplates();
</script>

<script>
// Simple template loading function
function loadInternalTemplate(templateName) {
    const templates = {
        system_announcement: {
            title: "Thông báo hệ thống",
            message: "Hệ thống sẽ bảo trì vào 2h sáng ngày mai. Vui lòng lưu lại công việc trước thời gian này.",
            link: "/admin/announcements",
            type: "system"
        },
        meeting_reminder: {
            title: "Nhắc nhở họp",
            message: "Họp team lúc 14:00 ngày mai tại phòng họp A. Vui lòng chuẩn bị báo cáo tiến độ.",
            link: "/admin/meetings",
            type: "meeting"
        },
        task_assignment: {
            title: "Giao nhiệm vụ",
            message: "Bạn được giao nhiệm vụ mới cần hoàn thành trước 17:00 ngày mai. Vui lòng kiểm tra chi tiết.",
            link: "/admin/tasks",
            type: "task"
        },
        deadline_reminder: {
            title: "Nhắc nhở deadline",
            message: "Dự án ABC cần hoàn thành trước 18:00 hôm nay. Vui lòng kiểm tra tiến độ.",
            link: "/admin/projects",
            type: "deadline"
        },
        policy_update: {
            title: "Cập nhật chính sách",
            message: "Có chính sách mới được cập nhật. Vui lòng đọc và xác nhận đã hiểu.",
            link: "/admin/policies",
            type: "policy"
        },
        training_reminder: {
            title: "Nhắc nhở đào tạo",
            message: "Khóa đào tạo về quy trình mới sẽ diễn ra vào 9:00 sáng mai. Vui lòng tham gia đầy đủ.",
            link: "/admin/training",
            type: "training"
        },
        security_alert: {
            title: "Cảnh báo bảo mật",
            message: "Phát hiện hoạt động đăng nhập bất thường. Vui lòng kiểm tra và báo cáo nếu cần.",
            link: "/admin/security",
            type: "security"
        }
    };
    
    const template = templates[templateName];
    if (template) {
        const jsonString = JSON.stringify(template, null, 2);
        const textarea = document.getElementById('payload');
        if (textarea) {
            textarea.value = jsonString;
            console.log('Template loaded:', templateName, jsonString);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up internal templates...');
    
    // Test if elements exist
    console.log('Template input exists:', !!document.getElementById('ten_template'));
    console.log('Payload textarea exists:', !!document.getElementById('payload'));
    
    const templateSelect = document.getElementById('internal-template-select');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedTemplate = this.value;
            console.log('Template selected:', selectedTemplate);
            if (selectedTemplate) {
                loadInternalTemplate(selectedTemplate);
            }
        });
    } else {
        console.error('Template select not found');
    }

    // Auto-fill JSON when template name changes
    const templateInput = document.getElementById('ten_template');
    
    if (templateInput) {
        templateInput.addEventListener('input', function() {
            const template = this.value;
            console.log('Internal template name changed:', template);
            if (template) {
                // Load template based on template name
                loadInternalTemplate(template);
            }
        });
    } else {
        console.error('Internal template input not found');
    }

    // Validate JSON format
    const payloadTextarea = document.getElementById('payload');
    if (payloadTextarea) {
        payloadTextarea.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value) {
                try {
                    JSON.parse(value);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
    }
});
</script>
@endpush
