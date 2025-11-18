<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý khách sạn')</title>
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap 5 -->
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> --}}

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>

    <style>
        body {
            font-family: "Inter", sans-serif;
        }

        aside {
            min-width: 300px;
            min-height: 100vh;
            transition: all 0.3s;
            background-color: #343a40;
        }

        aside .nav-link {
            color: #adb5bd;
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.2s;
        }

        aside .nav-link.active,
        aside .nav-link:hover {
            color: #fff;
            background-color: #495057;
            border-radius: 6px;
        }

        header {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
        }

        .sidebar-brand span {
            color: #ffc107;
        }

        main {
            min-height: calc(100vh - 80px);
        }

        .dropdown-toggle::after {
            margin-left: 0.25rem;
        }

        .alert {
            position: fixed;
            top: 80px;
            right: 20px;
            min-width: 250px;
            z-index: 1050;
        }

        @media (max-width: 768px) {
            aside {
                position: fixed;
                left: -260px;
                top: 0;
                height: 100vh;
                z-index: 1100;
            }

            aside.show {
                left: 0;
            }

            .sidebar-toggle-btn {
                display: inline-block;
            }
        }
    </style>
</head>

<body class="bg-light">

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex">
        <!-- Sidebar -->
        <aside class="p-3 shadow-sm">
            <div class="sidebar-brand mb-4">
                <span>Hotel Manager</span>
            </div>
            <nav class="nav flex-column">
                @if (Auth::check() && in_array(Auth::user()->vai_tro, ['nhan_vien', 'admin']))
                    <a href="{{ route('staff.index') }}"
                        class="nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="{{ route('staff.bookings') }}"
                        class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i> Tổng quan booking
                    </a>
                    {{-- <a href="{{ route('staff.pending-bookings') }}"
                        class="nav-link {{ request()->routeIs('staff.pending-bookings') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check me-2"></i> Danh Sách Booking Chờ Xác Nhận
                    </a> --}}
                    <a href="{{ route('staff.rooms') }}"
                        class="nav-link {{ request()->routeIs('staff.rooms') ? 'active' : '' }}">
                        <i class="bi bi-door-open me-2"></i> Quản Lý Tình Trạng Phòng
                    </a>
                    <a href="{{ route('staff.checkin') }}"
                        class="nav-link {{ request()->routeIs('staff.checkin') ? 'active' : '' }}">
                        <i class="bi bi-person-check-fill me-2"></i> Danh Sách Booking Check-in
                    </a>

                    <a href="{{ route('payment.pending_payments') }}"
                        class="nav-link {{ request()->routeIs('payment.pending-payments') ? 'active' : '' }}">
                        <i class="bi bi-credit-card me-2"></i> Chờ thanh toán
                    </a>
                    <a href="{{ route('staff.room-overview') }}"
                        class="nav-link {{ request()->routeIs('staff.room-overview') ? 'active' : '' }}">
                        <i class="bi bi-house-fill me-2"></i> Tổng quan phòng
                    </a>
                    <a href="{{ route('staff.reports') }}"
                        class="nav-link {{ request()->routeIs('staff.reports') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Báo cáo nhân viên
                    </a>
                @endif
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex-grow-1 ms-3">
            <!-- Header -->
            <header class="bg-white shadow-sm p-3 d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-secondary d-md-none sidebar-toggle-btn"
                    onclick="toggleSidebar()">☰</button>
                <div class="d-flex align-items-center gap-3">
                    <!-- Admin Panel Button -->
                    @if(Auth::check() && Auth::user()->vai_tro === 'admin')
                    <a href="{{ route('admin.loai_phong.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-shield-check me-1"></i>Admin Panel
                    </a>
                    @endif
                    
                    <!-- Notifications -->
                    <div class="dropdown">
                        <button class="btn btn-light position-relative" type="button" id="staffNotificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="staffNotificationBadge" style="display: none;">
                                0
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 500px; overflow-y: auto;">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Thông báo</span>
                                <button class="btn btn-sm btn-outline-primary" onclick="markAllStaffNotificationsAsRead()" title="Đánh dấu tất cả đã đọc">
                                    <i class="bi bi-check-all"></i>
                                </button>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div id="staffNotificationList">
                                <div class="text-center p-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Đang tải...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="text-center p-2">
                                <a href="{{ route('admin.admin-notifications.index') ?? '#' }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i>Xem tất cả thông báo
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle"
                            id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ auth()->user() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                alt="avatar" class="rounded-circle border border-light shadow-sm" width="40"
                                height="40">
                            <div class="ms-2 text-dark">
                                <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                <small class="text-muted">{{ ucfirst(Auth::user()->vai_tro) }}</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item">Hồ sơ cá nhân</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main -->
            <main class="p-4">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('aside').classList.toggle('show');
        }

        // Enable all tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(el) {
                return new bootstrap.Tooltip(el);
            });
        });

        // Staff Notification Scripts
        // Load staff notifications
        function loadStaffNotifications() {
            fetch('/api/notifications/recent', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateStaffNotificationList(data.data);
                    } else {
                        console.error('API error:', data.message);
                        document.getElementById('staffNotificationList').innerHTML = 
                            '<div class="text-center p-3 text-muted">Không thể tải thông báo</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading staff notifications:', error);
                    document.getElementById('staffNotificationList').innerHTML = 
                        '<div class="text-center p-3 text-muted">Không thể tải thông báo</div>';
                });
        }

        // Update staff notification list
        function updateStaffNotificationList(notifications) {
            const listContainer = document.getElementById('staffNotificationList');
            
            if (!notifications || notifications.length === 0) {
                listContainer.innerHTML = '<div class="text-center p-3 text-muted">Không có thông báo nào</div>';
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                const payload = notification.payload || { title: 'Thông báo', message: 'Nội dung không hợp lệ' };
                const isUnread = notification.trang_thai !== 'read';
                const timeAgo = getTimeAgo(notification.created_at);
                const link = payload.link || '#';
                
                html += `
                    <a href="${link}" class="dropdown-item ${isUnread ? 'bg-light' : ''}" style="white-space: normal; text-decoration: none; color: inherit;" onclick="markNotificationAsRead(${notification.id}, event)">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-2">
                                <i class="bi bi-bell text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold ${isUnread ? 'text-primary' : ''}">${escapeHtml(payload.title || 'Thông báo')}</div>
                                <div class="small text-muted">${escapeHtml(payload.message || '')}</div>
                                <div class="small text-muted mt-1">${timeAgo}</div>
                            </div>
                            ${isUnread ? '<div class="flex-shrink-0"><span class="badge bg-primary rounded-pill">Mới</span></div>' : ''}
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                `;
            });
            
            listContainer.innerHTML = html;
        }

        // Load unread count
        function loadStaffUnreadCount() {
            fetch('/api/notifications/unread-count', {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateStaffNotificationBadge(data.count);
                    } else {
                        console.error('API error:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading unread count:', error);
                });
        }

        // Update notification badge
        function updateStaffNotificationBadge(count) {
            const badge = document.getElementById('staffNotificationBadge');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Mark notification as read
        function markNotificationAsRead(notificationId, event) {
            // Don't prevent default if it's a link
            fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadStaffNotifications();
                    loadStaffUnreadCount();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        // Mark all staff notifications as read
        function markAllStaffNotificationsAsRead() {
            fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadStaffNotifications();
                    loadStaffUnreadCount();
                } else {
                    console.error('API error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
        }

        // Get time ago
        function getTimeAgo(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Vừa xong';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' phút trước';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' giờ trước';
            return Math.floor(diffInSeconds / 86400) + ' ngày trước';
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStaffNotifications();
            loadStaffUnreadCount();
            
            // Refresh every 30 seconds
            setInterval(() => {
                loadStaffNotifications();
                loadStaffUnreadCount();
            }, 30000);
        });
    </script>
</body>

</html>
