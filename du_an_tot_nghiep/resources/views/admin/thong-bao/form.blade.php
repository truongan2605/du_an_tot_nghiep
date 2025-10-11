@php
    $editing = isset($thongBao);
    $thongBao = $thongBao ?? null;
@endphp

<div class="row g-3">
    @if(!$editing)
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title text-primary">
                    <i class="fas fa-broadcast-tower me-2"></i>Gửi theo vai trò (tùy chọn)
                </h6>
                <select name="vai_tro_broadcast[]" class="form-select" multiple id="vai-tro-broadcast">
                    <option value="admin" @selected(collect(old('vai_tro_broadcast', []))->contains('admin'))>
                        <i class="fas fa-user-shield me-1"></i> Admin
                    </option>
                    <option value="nhan_vien" @selected(collect(old('vai_tro_broadcast', []))->contains('nhan_vien'))>
                        <i class="fas fa-user-tie me-1"></i> Nhân viên
                    </option>
                </select>
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Nếu chọn vai trò, hệ thống sẽ tạo thông báo cho tất cả người dùng thuộc vai trò đó. Trường "Người nhận" phía dưới sẽ bị bỏ qua.
                </small>
                @error('vai_tro_broadcast')<div class="text-danger small">{{ $message }}</div>@enderror
                @error('vai_tro_broadcast.*')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    @endif

    <div class="col-12" id="nguoi-nhan-field">
        <label class="form-label">
            <i class="fas fa-user me-1"></i>Người nhận
        </label>
        <select name="nguoi_nhan_id" class="form-select" id="nguoi-nhan-select">
            <option value="">-- Chọn người nhận --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('nguoi_nhan_id', $thongBao?->nguoi_nhan_id ?? '') == $user->id)>
                    {{ $user->name }} - {{ $user->email }}
                </option>
            @endforeach
        </select>
        @error('nguoi_nhan_id')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">
            <i class="fas fa-bullhorn me-1"></i>Kênh gửi
        </label>
        <select name="kenh" class="form-select" required>
            @foreach($channels as $c)
                <option value="{{ $c }}" @selected(old('kenh', $thongBao?->kenh ?? '') == $c)>
                    @switch($c)
                        @case('email')
                            <i class="fas fa-envelope me-1"></i>Email
                            @break
                        @case('sms')
                            <i class="fas fa-sms me-1"></i>SMS
                            @break
                        @case('in_app')
                            <i class="fas fa-bell me-1"></i>In-app
                            @break
                        @default
                            {{ $c }}
                    @endswitch
                </option>
            @endforeach
        </select>
        @error('kenh')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">
            <i class="fas fa-tag me-1"></i>Template
        </label>
        <select name="ten_template" class="form-select" id="templateSelect" required>
            <option value="booking_created" @selected(old('ten_template', $thongBao?->ten_template ?? '') == 'booking_created')>
                <i class="fas fa-calendar-plus me-1"></i>booking_created
            </option>
            <option value="payment_success" @selected(old('ten_template', $thongBao?->ten_template ?? '') == 'payment_success')>
                <i class="fas fa-credit-card me-1"></i>payment_success
            </option>
            <option value="booking_cancelled" @selected(old('ten_template', $thongBao?->ten_template ?? '') == 'booking_cancelled')>
                <i class="fas fa-times-circle me-1"></i>booking_cancelled
            </option>
            <option value="checkin_reminder" @selected(old('ten_template', $thongBao?->ten_template ?? '') == 'checkin_reminder')>
                <i class="fas fa-clock me-1"></i>checkin_reminder
            </option>
            <option value="custom" @selected(old('ten_template', $thongBao?->ten_template ?? '') == 'custom')>
                <i class="fas fa-edit me-1"></i>custom
            </option>
        </select>
        @error('ten_template')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    @if($editing)
    <div class="col-md-4">
        <label class="form-label">
            <i class="fas fa-flag me-1"></i>Trạng thái
        </label>
        <div class="form-control-plaintext">
            @switch($thongBao?->trang_thai)
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
                        <i class="fas fa-times me-1"></i>Gửi thất bại
                    </span>
                    @break
                @case('read')
                    <span class="badge bg-info">
                        <i class="fas fa-eye me-1"></i>Đã đọc
                    </span>
                    @break
                @default
                    <span class="badge bg-secondary">{{ $thongBao?->trang_thai }}</span>
            @endswitch
        </div>
    </div>
    @endif

    <div class="col-12">
        <label class="form-label">
            <i class="fas fa-code me-1"></i>Payload (JSON)
        </label>
        <textarea name="payload" class="form-control" id="payloadTextarea" rows="8" 
                  placeholder='{"title":"Tiêu đề","message":"Nội dung","link":"/path","subject":"Chủ đề email"}'>{{ old('payload', $thongBao?->payload ? json_encode($thongBao->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <small class="form-text text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Nhập JSON với các trường: title, message, link, subject. Chọn template để tự động điền.
        </small>
        @error('payload')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    @if($editing)
    <div class="col-md-6">
        <label class="form-label">
            <i class="fas fa-redo me-1"></i>Số lần thử
        </label>
        <div class="form-control-plaintext">
            <span class="badge bg-info">{{ $thongBao?->so_lan_thu ?? 0 }}</span>
        </div>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">
            <i class="fas fa-calendar me-1"></i>Lần thử cuối
        </label>
        <div class="form-control-plaintext">
            @if($thongBao?->lan_thu_cuoi)
                <span class="text-muted">{{ $thongBao->lan_thu_cuoi->format('d/m/Y H:i') }}</span>
            @else
                <span class="text-muted">Chưa có</span>
            @endif
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('templateSelect');
    const payloadTextarea = document.getElementById('payloadTextarea');
    const vaiTroBroadcast = document.getElementById('vai-tro-broadcast');
    const nguoiNhanField = document.getElementById('nguoi-nhan-field');
    const nguoiNhanSelect = document.getElementById('nguoi-nhan-select');
    
    // Template JSON samples
    const templates = {
        'booking_created': {
            "title": "Đặt phòng thành công",
            "message": "Bạn đã đặt phòng thành công với mã tham chiếu: {booking_reference}",
            "link": "/account/bookings/{booking_id}",
            "subject": "Xác nhận đặt phòng"
        },
        'payment_success': {
            "title": "Thanh toán thành công",
            "message": "Bạn đã thanh toán thành công cho đơn đặt phòng {booking_reference}. Số tiền: {amount} VNĐ",
            "link": "/account/bookings/{booking_id}",
            "subject": "Xác nhận thanh toán"
        },
        'booking_cancelled': {
            "title": "Hủy đặt phòng thành công",
            "message": "Bạn đã hủy đặt phòng #{booking_reference} thành công. Ngày hủy: {cancelled_date}",
            "link": "/account/bookings/{booking_id}",
            "subject": "Xác nhận hủy đặt phòng"
        },
        'checkin_reminder': {
            "title": "Nhắc nhở check-in",
            "message": "Ngày mai bạn sẽ check-in lúc 14:00. Đơn đặt phòng: {booking_reference}",
            "link": "/account/bookings/{booking_id}",
            "subject": "Nhắc nhở check-in"
        }
    };
    
    // Handle template selection
    templateSelect.addEventListener('change', function() {
        const selectedTemplate = this.value;
        
        if (selectedTemplate && selectedTemplate !== 'custom' && templates[selectedTemplate]) {
            // Auto-fill JSON content
            payloadTextarea.value = JSON.stringify(templates[selectedTemplate], null, 2);
        } else if (selectedTemplate === 'custom') {
            // Clear for custom template
            payloadTextarea.value = '';
            payloadTextarea.placeholder = '{"title":"Tiêu đề tùy chỉnh","message":"Nội dung tùy chỉnh","link":"/path/to/link"}';
        }
    });
    
    // Handle role broadcast selection
    function toggleNguoiNhanField() {
        const selectedRoles = Array.from(vaiTroBroadcast.selectedOptions).map(option => option.value);
        
        if (selectedRoles.length > 0) {
            // Disable individual user selection when roles are selected
            nguoiNhanSelect.disabled = true;
            nguoiNhanSelect.required = false;
            nguoiNhanField.style.opacity = '0.6';
            nguoiNhanField.title = 'Trường này bị vô hiệu hóa khi chọn gửi theo vai trò';
        } else {
            // Enable individual user selection when no roles are selected
            nguoiNhanSelect.disabled = false;
            nguoiNhanSelect.required = true;
            nguoiNhanField.style.opacity = '1';
            nguoiNhanField.title = '';
        }
    }
    
    vaiTroBroadcast.addEventListener('change', toggleNguoiNhanField);
    
    // Initialize with current values
    if (templateSelect.value && templateSelect.value !== 'custom') {
        templateSelect.dispatchEvent(new Event('change'));
    }
    
    // Initialize role broadcast state
    toggleNguoiNhanField();
});
</script>