/**
 * Notification JSON Templates Manager
 * Quản lý các mẫu JSON cho thông báo
 */

class NotificationTemplates {
    constructor() {
        this.templates = {
            // Templates cho khách hàng
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
            },
            
            // Templates cho nội bộ
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

    /**
     * Lấy template theo loại và tên
     */
    getTemplate(type, templateName) {
        if (this.templates[type] && this.templates[type][templateName]) {
            return this.templates[type][templateName];
        }
        return null;
    }

    /**
     * Lấy danh sách templates theo loại
     */
    getTemplatesByType(type) {
        return this.templates[type] || {};
    }

    /**
     * Tạo JSON string từ template
     */
    getTemplateJSON(type, templateName) {
        const template = this.getTemplate(type, templateName);
        if (template) {
            return JSON.stringify(template, null, 2);
        }
        return null;
    }

    /**
     * Tạo dropdown options cho templates
     */
    createTemplateDropdown(type, selectId) {
        const templates = this.getTemplatesByType(type);
        const select = document.getElementById(selectId);
        
        if (!select) return;

        // Clear existing options
        select.innerHTML = '<option value="">Chọn mẫu có sẵn...</option>';

        // Add template options
        Object.keys(templates).forEach(key => {
            const template = templates[key];
            const option = document.createElement('option');
            option.value = key;
            option.textContent = `${template.title} (${template.type})`;
            select.appendChild(option);
        });
    }

    /**
     * Load template vào textarea
     */
    loadTemplateToTextarea(type, templateName, textareaId) {
        const jsonString = this.getTemplateJSON(type, templateName);
        const textarea = document.getElementById(textareaId);
        
        if (jsonString && textarea) {
            textarea.value = jsonString;
            this.validateJSON(textarea);
        }
    }

    /**
     * Validate JSON format
     */
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

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize customer notification templates
    if (document.getElementById('customer-template-select')) {
        window.notificationTemplates.createTemplateDropdown('customer', 'customer-template-select');
    }
    
    // Initialize internal notification templates
    if (document.getElementById('internal-template-select')) {
        window.notificationTemplates.createTemplateDropdown('internal', 'internal-template-select');
    }
});




