/**
 * Notification Manager - Real-time notification handling
 */
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.refreshInterval = 30000; // 30 seconds
        this.isInitialized = false;
        
        this.init();
    }
    
    init() {
        if (this.isInitialized) return;
        
        this.isInitialized = true;
        this.loadUnreadCount();
        this.setupAutoRefresh();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Listen for new notifications (if using WebSockets or Server-Sent Events)
        if (typeof window.Echo !== 'undefined' && window.userId) {
            console.log('Setting up Echo listeners for user:', window.userId);
            console.log('Echo instance:', window.Echo);
            
            // Listen to user-specific channel
            window.Echo.private(`user.${window.userId}`)
                .listen('NotificationCreated', (e) => {
                    console.log('Received notification:', e);
                    this.handleNewNotification(e.notification);
                })
                .listen('NotificationSent', (e) => {
                    console.log('Notification sent:', e);
                    this.handleNewNotification(e.notification);
                });
                
            // Listen to room updates channel
            window.Echo.channel('room-updates')
                .listen('RoomCreated', (e) => {
                    console.log('Room created:', e);
                    this.showRoomUpdateNotification(e.room, 'created');
                })
                .listen('RoomUpdated', (e) => {
                    console.log('Room updated:', e);
                    this.showRoomUpdateNotification(e.room, 'updated');
                });
                
            // Listen to booking updates channel
            window.Echo.channel('booking-updates')
                .listen('BookingCreated', (e) => {
                    console.log('Booking created:', e);
                    this.showBookingUpdateNotification(e.booking, 'created');
                });
                
            console.log('Echo listeners set up successfully');
        } else {
            console.log('Echo not available or userId not set:', {
                echo: typeof window.Echo,
                userId: window.userId
            });
        }
        
        // Listen for page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.loadUnreadCount();
            }
        });
    }
    
    setupAutoRefresh() {
        setInterval(() => {
            if (!document.hidden) {
                this.loadUnreadCount();
            }
        }, this.refreshInterval);
    }
    
    async loadUnreadCount() {
        try {
            console.log('Loading unread count...');
            const response = await fetch('/api/notifications/unread-count', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (response.ok) {
                const data = await response.json();
                console.log('Response data:', data);
                if (data.success) {
                    this.updateBadge(data.count);
                    this.unreadCount = data.count;
                    console.log('Updated badge with count:', data.count);
                }
            } else {
                console.error('API error:', response.status, response.statusText);
                const errorText = await response.text();
                console.error('Error response:', errorText);
            }
        } catch (error) {
            console.error('Error loading unread count:', error);
        }
    }
    
    async loadRecentNotifications(limit = 10) {
        try {
            const response = await fetch(`/api/notifications/recent?limit=${limit}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.notifications = data.data;
                    this.updateNotificationDropdown();
                }
            }
        } catch (error) {
            console.error('Error loading recent notifications:', error);
        }
    }
    
    updateBadge(count) {
        const badge = document.querySelector('.notif-badge');
        if (badge) {
            if (count > 0) {
                badge.style.display = 'block';
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.add('animation-blink');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('animation-blink');
            }
        }
        
        // Update page title if there are unread notifications
        if (count > 0) {
            document.title = `(${count}) ${document.title.replace(/^\(\d+\)\s*/, '')}`;
        } else {
            document.title = document.title.replace(/^\(\d+\)\s*/, '');
        }
    }
    
    updateNotificationDropdown() {
        const container = document.querySelector('.notification-dropdown .list-group');
        if (!container) return;
        
        if (this.notifications.length === 0) {
            container.innerHTML = '<li class="text-center text-muted py-3">Không có thông báo</li>';
            return;
        }
        
        let html = '';
        this.notifications.forEach(notification => {
            const isUnread = notification.trang_thai !== 'read';
            const payload = notification.payload || {};
            
            html += `
                <li>
                    <a href="#" 
                       class="list-group-item list-group-item-action rounded border-0 mb-1 p-3 w-100 text-start notification-item ${isUnread ? 'notif-unread' : ''}"
                       data-notification-id="${notification.id}"
                       onclick="notificationManager.viewNotification(${notification.id})">
                        <h6 class="mb-1">${payload.title || notification.ten_template}</h6>
                        <p class="mb-0 small">${payload.message || ''}</p>
                        <span class="small text-muted">${this.formatDate(notification.created_at)}</span>
                    </a>
                </li>
            `;
        });
        
        container.innerHTML = html;
    }
    
    async viewNotification(id) {
        try {
            const response = await fetch(`/api/notifications/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showNotificationModal(data.data);
                }
            }
        } catch (error) {
            console.error('Error viewing notification:', error);
            this.showError('Không thể tải thông tin thông báo');
        }
    }
    
    showNotificationModal(notification) {
        const modal = document.getElementById('notificationModal');
        const modalBody = document.getElementById('notificationModalBody');
        const markAsReadBtn = document.getElementById('markAsReadBtn');
        
        if (!modal || !modalBody) return;
        
        const payload = notification.payload || {};
        const isUnread = notification.trang_thai !== 'read';
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary mb-3">${payload.title || notification.ten_template}</h6>
                    <div class="alert alert-light">
                        <p class="mb-0">${payload.message || 'Không có nội dung'}</p>
                    </div>
                    ${payload.link ? `
                        <div class="mb-3">
                            <a href="${payload.link}" class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                            </a>
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Thông tin thông báo</h6>
                    <ul class="list-unstyled">
                        <li><strong>Loại:</strong> ${notification.ten_template}</li>
                        <li><strong>Kênh:</strong> ${notification.kenh}</li>
                        <li><strong>Trạng thái:</strong> <span class="badge bg-${this.getStatusColor(notification.trang_thai)}">${this.getStatusText(notification.trang_thai)}</span></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Thời gian</h6>
                    <ul class="list-unstyled">
                        <li><strong>Tạo:</strong> ${this.formatDate(notification.created_at)}</li>
                        <li><strong>Cập nhật:</strong> ${this.formatDate(notification.updated_at)}</li>
                    </ul>
                </div>
            </div>
        `;
        
        // Show/hide mark as read button
        if (markAsReadBtn) {
            if (isUnread) {
                markAsReadBtn.style.display = 'inline-block';
                markAsReadBtn.onclick = () => this.markAsRead(notification.id);
            } else {
                markAsReadBtn.style.display = 'none';
            }
        }
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    async markAsRead(id) {
        try {
            const response = await fetch(`/api/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess('Đã đánh dấu thông báo là đã đọc');
                    this.loadUnreadCount();
                    this.loadRecentNotifications();
                    
                    // Update notification item styling
                    const notificationItem = document.querySelector(`[data-notification-id="${id}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('notif-unread');
                    }
                } else {
                    this.showError(data.message);
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
            this.showError('Có lỗi xảy ra khi đánh dấu đã đọc');
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess(data.message);
                    this.loadUnreadCount();
                    this.loadRecentNotifications();
                } else {
                    this.showError(data.message);
                }
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            this.showError('Có lỗi xảy ra khi đánh dấu tất cả đã đọc');
        }
    }
    
    handleNewNotification(notification) {
        // Add to notifications list
        this.notifications.unshift(notification);
        if (this.notifications.length > 10) {
            this.notifications = this.notifications.slice(0, 10);
        }
        
        // Update UI
        this.updateNotificationDropdown();
        this.loadUnreadCount();
        
        // Show browser notification if permission granted
        this.showBrowserNotification(notification);
    }
    
    showRoomUpdateNotification(room, action) {
        const actionText = action === 'created' ? 'được thêm mới' : 'được cập nhật';
        const message = `Phòng ${room.ma_phong} (${room.name || 'Không có tên'}) ${actionText}. Giá: ${new Intl.NumberFormat('vi-VN').format(room.gia_cuoi_cung || 0)} VNĐ`;
        
        // Show toast notification
        this.showToast({
            title: action === 'created' ? 'Phòng mới được thêm' : 'Phòng được cập nhật',
            message: message,
            type: 'info'
        });
        
        // Log to console
        console.log(`Room ${action}:`, room);
    }
    
    showBookingUpdateNotification(booking, action) {
        const message = `Đơn đặt phòng mới ${booking.ma_dat_phong} - Tổng tiền: ${new Intl.NumberFormat('vi-VN').format(booking.tong_tien || 0)} VNĐ`;
        
        // Show toast notification
        this.showToast({
            title: 'Đơn đặt phòng mới',
            message: message,
            type: 'success'
        });
        
        // Log to console
        console.log(`Booking ${action}:`, booking);
    }
    
    showToast(data) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${data.type || 'info'} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
        alert.innerHTML = `
            <strong>${data.title || 'Thông báo'}</strong><br>
            ${data.message || ''}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    async showBrowserNotification(notification) {
        if (!('Notification' in window)) return;
        
        if (Notification.permission === 'granted') {
            const payload = notification.payload || {};
            new Notification(payload.title || notification.ten_template, {
                body: payload.message || '',
                icon: '/favicon.ico',
                tag: `notification-${notification.id}`
            });
        } else if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                this.showBrowserNotification(notification);
            }
        }
    }
    
    showSuccess(message) {
        this.showAlert('success', message);
    }
    
    showError(message) {
        this.showAlert('danger', message);
    }
    
    showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'sent': 'success',
            'read': 'info',
            'failed': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    getStatusText(status) {
        const texts = {
            'pending': 'Chờ xử lý',
            'sent': 'Đã gửi',
            'read': 'Đã đọc',
            'failed': 'Thất bại'
        };
        return texts[status] || status;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        // Less than 1 minute
        if (diff < 60000) {
            return 'Vừa xong';
        }
        
        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} phút trước`;
        }
        
        // Less than 1 day
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} giờ trước`;
        }
        
        // More than 1 day
        return date.toLocaleDateString('vi-VN');
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
    
    // Load recent notifications for dropdown
    if (window.notificationManager) {
        window.notificationManager.loadRecentNotifications();
    }
});

// Export for use in other scripts
window.NotificationManager = NotificationManager;








