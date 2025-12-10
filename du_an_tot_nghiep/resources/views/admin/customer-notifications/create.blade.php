@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-plus me-2 text-primary"></i>Tạo thông báo khách hàng mới
            </h1>
            <p class="text-muted mb-0">Gửi thông báo cho khách hàng</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.customer-notifications.index') }}" class="btn btn-secondary">
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
                    <form method="POST" action="{{ route('admin.customer-notifications.store') }}">
                        @csrf
                        
                        <!-- Recipient Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="nguoi_nhan_id" class="form-label">Khách hàng cụ thể</label>
                                <select class="form-select" id="nguoi_nhan_id" name="nguoi_nhan_id">
                                    <option value="">Chọn khách hàng cụ thể</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('nguoi_nhan_id') == $customer->id ? 'selected' : '' }}>
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
                                        <option value="{{ $channel }}" {{ old('kenh') == $channel ? 'selected' : '' }}>
                                            @switch($channel)
                                                @case('email')
                                                    <i class="fas fa-envelope me-1"></i>Email
                                                    @break
                                                @case('in_app')
                                                    <i class="fas fa-bell me-1"></i>In-app
                                                    @break
                                            @endswitch
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
                                   value="{{ old('ten_template') }}" placeholder="Nhập tên template (vd: booking_confirmation, payment_success...)" required
                                   list="template-suggestions">
                            <datalist id="template-suggestions">
                                <option value="booking_confirmation">Xác nhận đặt phòng</option>
                                <option value="booking_reminder">Nhắc nhở đặt phòng</option>
                                <option value="payment_success">Thanh toán thành công</option>
                                <option value="payment_reminder">Nhắc nhở thanh toán</option>
                                <option value="promotion_offer">Ưu đãi đặc biệt</option>
                                <option value="welcome_message">Chào mừng</option>
                                <option value="account_verification">Xác thực tài khoản</option>
                            </datalist>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Nhập tên template để tự động load JSON. Gợi ý: booking_confirmation, payment_success, welcome_message...
                                <br>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="testLoadTemplate()">
                                    <i class="fas fa-test-tube me-1"></i> Test Load Template
                                </button>
                            </div>
                            @error('ten_template')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

        <!-- Template Selector - Hidden since auto-load from template name -->
        <div class="mb-4" style="display: none;">
            <label for="customer-template-select" class="form-label">Chọn mẫu có sẵn</label>
            <select class="form-select" id="customer-template-select">
                <option value="">Chọn mẫu có sẵn...</option>
                <option value="booking_confirmation">Xác nhận đặt phòng (success)</option>
                <option value="booking_reminder">Nhắc nhở đặt phòng (info)</option>
                <option value="payment_success">Thanh toán thành công (success)</option>
                <option value="payment_reminder">Nhắc nhở thanh toán (warning)</option>
                <option value="promotion_offer">Ưu đãi đặc biệt (promotion)</option>
                <option value="welcome_message">Chào mừng (welcome)</option>
                <option value="account_verification">Xác thực tài khoản (verification)</option>
            </select>
            <div class="form-text">
                <i class="fas fa-info-circle me-1"></i>
                Chọn mẫu để tự động điền nội dung JSON
            </div>
        </div>

                        <!-- Notification Content -->
                        <div class="mb-4">
                            <label for="notification_title" class="form-label">Tiêu đề thông báo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="notification_title" name="notification_title" 
                                   value="{{ old('notification_title') }}" placeholder="Ví dụ: Xác nhận đặt phòng thành công" required>
                            @error('notification_title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="notification_message" class="form-label">Nội dung thông báo <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="notification_message" name="notification_message" rows="4" 
                                      placeholder="Ví dụ: Đặt phòng thành công! Mã đặt phòng: #12345. Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi." required>{{ old('notification_message') }}</textarea>
                            @error('notification_message')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="notification_link" class="form-label">Link (tùy chọn)</label>
                            <input type="text" class="form-control" id="notification_link" name="notification_link" 
                                   value="{{ old('notification_link') }}" placeholder="Ví dụ: /account/bookings hoặc https://example.com">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Link sẽ được mở khi người dùng click vào thông báo
                            </div>
                            @error('notification_link')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Hidden payload field for backend -->
                        <input type="hidden" id="payload" name="payload" value="">

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Gửi thông báo
                            </button>
                            <a href="{{ route('admin.customer-notifications.index') }}" class="btn btn-secondary">
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
                        <li><i class="fas fa-check text-success me-1"></i>Chọn khách hàng cụ thể để gửi riêng lẻ</li>
                        <li><i class="fas fa-check text-success me-1"></i>Chọn "Gửi cho tất cả" để broadcast</li>
                        <li><i class="fas fa-check text-success me-1"></i>Hệ thống sẽ tự động lấy danh sách khách hàng</li>
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

            <!-- Customer List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Danh sách khách hàng
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($customers as $customer)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-bold">{{ $customer->name }}</div>
                                    <small class="text-muted">{{ $customer->email }}</small>
                                </div>
                                <span class="badge bg-success">
                                    Khách hàng
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
            customer: {
                booking_confirmation: {
                    title: "Xác nhận đặt phòng",
                    message: "Đặt phòng thành công! Mã đặt phòng: #12345. Vui lòng kiểm tra email để biết thêm chi tiết.",
                    link: "/account/bookings",
                    type: "success"
                },
                booking_reminder: {
                    title: "Nhắc nhở đặt phòng",
                    message: "Đừng quên đặt phòng cho chuyến đi sắp tới. Chúng tôi có nhiều ưu đãi hấp dẫn đang chờ bạn!",
                    link: "/rooms",
                    type: "info"
                },
                payment_success: {
                    title: "Thanh toán thành công",
                    message: "Thanh toán của bạn đã được xử lý thành công. Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!",
                    link: "/account/payments",
                    type: "success"
                },
                payment_reminder: {
                    title: "Nhắc nhở thanh toán",
                    message: "Vui lòng thanh toán đơn hàng trước 24h để đảm bảo đặt phòng của bạn được giữ lại.",
                    link: "/account/payments",
                    type: "warning"
                },
                promotion_offer: {
                    title: "Ưu đãi đặc biệt",
                    message: "Giảm 20% cho đơn hàng tiếp theo của bạn! Sử dụng mã giảm giá: SAVE20",
                    link: "/promotions",
                    type: "promotion"
                },
                welcome_message: {
                    title: "Chào mừng bạn đến với hệ thống",
                    message: "Cảm ơn bạn đã đăng ký! Hãy khám phá các dịch vụ tuyệt vời của chúng tôi.",
                    link: "/account/dashboard",
                    type: "welcome"
                },
                account_verification: {
                    title: "Xác thực tài khoản",
                    message: "Vui lòng xác thực email của bạn để hoàn tất đăng ký tài khoản.",
                    link: "/account/verify",
                    type: "verification"
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
        console.log('Creating template dropdown for type:', type, 'selectId:', selectId);
        const templates = this.getTemplatesByType(type);
        console.log('Templates found:', templates);
        const select = document.getElementById(selectId);
        
        if (!select) {
            console.error('Select element not found:', selectId);
            return;
        }

        select.innerHTML = '<option value="">Chọn mẫu có sẵn...</option>';

        Object.keys(templates).forEach(key => {
            const template = templates[key];
            const option = document.createElement('option');
            option.value = key;
            option.textContent = `${template.title} (${template.type})`;
            select.appendChild(option);
            console.log('Added option:', key, template.title);
        });
        
        console.log('Template dropdown created with', Object.keys(templates).length, 'options');
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
// Test function
function testLoadTemplate() {
    console.log('Testing template load...');
    loadCustomerTemplate('booking_confirmation');
}

// Simple template loading function
function loadCustomerTemplate(templateName) {
    const templates = {
        booking_confirmation: {
            title: "Xác nhận đặt phòng",
            message: "Đặt phòng thành công! Mã đặt phòng: #12345. Vui lòng kiểm tra email để biết thêm chi tiết.",
            link: "/account/bookings",
            type: "success"
        },
        booking_reminder: {
            title: "Nhắc nhở đặt phòng",
            message: "Đừng quên đặt phòng cho chuyến đi sắp tới. Chúng tôi có nhiều ưu đãi hấp dẫn đang chờ bạn!",
            link: "/rooms",
            type: "info"
        },
        payment_success: {
            title: "Thanh toán thành công",
            message: "Thanh toán của bạn đã được xử lý thành công. Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!",
            link: "/account/payments",
            type: "success"
        },
        payment_reminder: {
            title: "Nhắc nhở thanh toán",
            message: "Vui lòng thanh toán đơn hàng trước 24h để đảm bảo đặt phòng của bạn được giữ lại.",
            link: "/account/payments",
            type: "warning"
        },
        promotion_offer: {
            title: "Ưu đãi đặc biệt",
            message: "Giảm 20% cho đơn hàng tiếp theo của bạn! Sử dụng mã giảm giá: SAVE20",
            link: "/promotions",
            type: "promotion"
        },
        welcome_message: {
            title: "Chào mừng bạn đến với hệ thống",
            message: "Cảm ơn bạn đã đăng ký! Hãy khám phá các dịch vụ tuyệt vời của chúng tôi.",
            link: "/account/dashboard",
            type: "welcome"
        },
        account_verification: {
            title: "Xác thực tài khoản",
            message: "Vui lòng xác thực email của bạn để hoàn tất đăng ký tài khoản.",
            link: "/account/verify",
            type: "verification"
        }
    };
    
    const template = templates[templateName];
    if (template) {
        const jsonString = JSON.stringify(template, null, 2);
        const textarea = document.getElementById('payload');
        if (textarea) {
            textarea.value = jsonString;
            console.log('Customer template loaded:', templateName, jsonString);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up customer templates...');
    
    // Test if elements exist
    console.log('Template input exists:', !!document.getElementById('ten_template'));
    console.log('Payload textarea exists:', !!document.getElementById('payload'));
    
    const templateSelect = document.getElementById('customer-template-select');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedTemplate = this.value;
            console.log('Customer template selected:', selectedTemplate);
            if (selectedTemplate) {
                loadCustomerTemplate(selectedTemplate);
            }
        });
    } else {
        console.error('Customer template select not found');
    }

    // Auto-fill JSON when template name changes
    const templateInput = document.getElementById('ten_template');
    
    if (templateInput) {
        templateInput.addEventListener('input', function() {
            const template = this.value;
            console.log('Template name changed:', template);
            if (template) {
                // Load template based on template name
                loadCustomerTemplate(template);
            }
        });
    } else {
        console.error('Template input not found');
    }

    // Auto-generate JSON payload from form fields
    function updatePayload() {
        const title = document.getElementById('notification_title').value;
        const message = document.getElementById('notification_message').value;
        const link = document.getElementById('notification_link').value;
        
        const payload = {
            title: title,
            message: message
        };
        
        if (link) {
            payload.link = link;
        }
        
        document.getElementById('payload').value = JSON.stringify(payload);
        console.log('Generated payload:', payload);
    }
    
    // Add event listeners to form fields
    document.getElementById('notification_title').addEventListener('input', updatePayload);
    document.getElementById('notification_message').addEventListener('input', updatePayload);
    document.getElementById('notification_link').addEventListener('input', updatePayload);
    
    // Initial payload generation
    updatePayload();

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
