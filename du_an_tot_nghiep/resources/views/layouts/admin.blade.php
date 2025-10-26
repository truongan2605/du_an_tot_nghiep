<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }

        .sidebar .nav-link {
            color: #adb5bd;
        }

        .sidebar .nav-link:hover {
            color: #fff;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }

        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white">Admin Panel</h4>
                </div>
                <nav class="nav flex-column">

                    <a class="nav-link {{ request()->routeIs('loai_phong.*') ? 'active' : '' }}"
                        href="{{ route('admin.loai_phong.index') }}">
                        <i class="fas fa-bed me-2"></i>Loại Phòng
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar me-2"></i> Thống kê
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.tien-nghi.*') ? 'active' : '' }}"
                        href="{{ route('admin.tien-nghi.index') }}">
                        <i class="fas fa-concierge-bell me-2"></i> Dịch vụ
                    </a>
                     <a class="nav-link {{ request()->routeIs('admin.vat-dung.*') ? 'active' : '' }}"
                        href="{{ route('admin.vat-dung.index') }}">
                        <i class="fas fa-concierge-bell me-2"></i> Vật dụng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.phong.*') ? 'active' : '' }}"
                        href="{{ route('admin.phong.index') }}">
                        <i class="fas fa-bed me-2"></i> Phòng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.bed-types.*') ? 'active' : '' }}"
                        href="{{ route('admin.bed-types.index') }}">
                        <i class="fas fa-layer-group me-2"></i> Loại giường
                    </a>

                    <a class="nav-link {{ request()->routeIs('admin.tang.*') ? 'active' : '' }}"
                        href="{{ route('admin.tang.index') }}">
                        <i class="fas fa-layer-group me-2"></i> Quản Lý Tầng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}"
                        href="{{ route('admin.user.index') }}">
                        <i class="fas fa-users me-2"></i> Quản Lý Khách Hàng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.nhan-vien.*') ? 'active' : '' }}"
                        href="{{ route('admin.nhan-vien.index') }}">
                        <i class="fas fa-user-tie me-2"></i> Quản Lý Nhân Viên
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.voucher.*') ? 'active' : '' }}"
                        href="{{ route('admin.voucher.index') }}">
                        <i class="fas fa-gift me-2"></i> Quản Lý Voucher
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.customer-notifications.*') ? 'active' : '' }}"
                        href="{{ route('admin.customer-notifications.index') }}">
                        <i class="fas fa-users me-2"></i> Thông Báo Khách Hàng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.internal-notifications.*') ? 'active' : '' }}"
                        href="{{ route('admin.internal-notifications.index') }}">
                        <i class="fas fa-building me-2"></i> Thông Báo Nội Bộ
                    </a>
                </nav>
            </div>

            <!-- Main content -->
            <div class="col-md-10 main-content">
                <!-- Admin Header with Notifications -->
                <div class="bg-white shadow-sm border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">@yield('title', 'Admin Panel')</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Notification Bell -->
                            <div class="dropdown">
                                <button class="btn btn-outline-primary position-relative" type="button" id="adminNotificationDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="adminNotificationBadge" style="display: none;">
                                        0
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" style="width: 350px;">
                                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                                        <span>Thông báo</span>
                                        <button class="btn btn-sm btn-outline-primary" onclick="markAllAdminNotificationsAsRead()">
                                            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
                                        </button>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div id="adminNotificationList">
                                        <div class="text-center p-3">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div class="text-center p-2">
                                        <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>Xem tất cả thông báo
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User Info -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>{{ auth()->user()->name }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('home') }}">
                                        <i class="fas fa-home me-2"></i>Về trang chủ
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                   

                   
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Notification Scripts -->
    <script>
        // Load admin notifications
        function loadAdminNotifications() {
            fetch('/admin/api/admin-notifications/recent')
                .then(response => response.json())
                .then(data => {
                    updateAdminNotificationList(data);
                })
                .catch(error => {
                    console.error('Error loading admin notifications:', error);
                    document.getElementById('adminNotificationList').innerHTML = 
                        '<div class="text-center p-3 text-muted">Không thể tải thông báo</div>';
                });
        }

        // Update admin notification list
        function updateAdminNotificationList(notifications) {
            const listContainer = document.getElementById('adminNotificationList');
            
            if (notifications.length === 0) {
                listContainer.innerHTML = '<div class="text-center p-3 text-muted">Không có thông báo nào</div>';
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                // Payload is already processed by the model
                const payload = notification.payload || { title: 'Thông báo', message: 'Nội dung không hợp lệ' };
                
                const isUnread = notification.trang_thai !== 'read';
                const timeAgo = getTimeAgo(notification.created_at);
                
                html += `
                    <div class="dropdown-item ${isUnread ? 'bg-light' : ''}" style="white-space: normal;">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-bell text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">${payload.title || 'Thông báo'}</div>
                                <div class="small text-muted">${payload.message || ''}</div>
                                <div class="small text-muted">${timeAgo}</div>
                            </div>
                            ${isUnread ? '<div class="flex-shrink-0"><span class="badge bg-primary rounded-pill">Mới</span></div>' : ''}
                        </div>
                    </div>
                `;
            });
            
            listContainer.innerHTML = html;
        }

        // Load unread count
        function loadAdminUnreadCount() {
            fetch('/admin/api/admin-notifications/unread-count')
                .then(response => response.json())
                .then(data => {
                    updateAdminNotificationBadge(data.count);
                })
                .catch(error => {
                    console.error('Error loading unread count:', error);
                });
        }

        // Update notification badge
        function updateAdminNotificationBadge(count) {
            const badge = document.getElementById('adminNotificationBadge');
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Mark all admin notifications as read
        function markAllAdminNotificationsAsRead() {
            fetch('/admin/admin-notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadAdminNotifications();
                    loadAdminUnreadCount();
                }
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
        }

        // Get time ago
        function getTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Vừa xong';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' phút trước';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' giờ trước';
            return Math.floor(diffInSeconds / 86400) + ' ngày trước';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAdminNotifications();
            loadAdminUnreadCount();
            
            // Refresh every 30 seconds
            setInterval(() => {
                loadAdminNotifications();
                loadAdminUnreadCount();
            }, 5000);
        });
    </script>
    
    @yield('scripts')
</body>

</html>
