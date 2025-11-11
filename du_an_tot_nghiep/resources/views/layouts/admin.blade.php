<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
      <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js'></script>
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding-left: 2.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }

        .sidebar .accordion-button {
            color: #adb5bd;
            background-color: #495057;
            border: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar .accordion-button:not(.collapsed) {
            color: #fff;
            background-color: #343a40;
        }

        .sidebar .accordion-button:focus {
            box-shadow: none;
        }

        .sidebar .accordion-body {
            padding: 0;
            background-color: #495057;
        }

        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .section-icon {
            margin-right: 0.5rem;
        }

        .accordion-collapse {
            transition: height 0.25s ease-out;
        }

        .content-loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .content-loading.show {
            display: block;
        }

        .content-loading.error {
            color: #dc3545;
        }

        /* Hiệu ứng fade-in khi load xong */
        #main-content {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        #main-content.loaded {
            opacity: 1;
        }
    </style>
</head>

<body>
       <div class="container-fluid">
        <div class="row">
            <!-- Sidebar dynamic -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white">{{ auth()->user()->vai_tro === 'admin' ? 'Admin Panel' : 'Admin Panel' }}</h4>
                </div>
                <div class="accordion accordion-flush" id="adminSidebarAccordion">
                    
                    {{-- Section 1: Cấu hình hệ thống (chỉ admin) --}}
                    @if(auth()->user()->vai_tro === 'admin')
                    <div class="accordion-item border-0 bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseConfig">
                                <i class="fas fa-cogs section-icon"></i>Cấu hình hệ thống
                            </button>
                        </h2>
                        <div id="collapseConfig" class="accordion-collapse collapse" data-bs-parent="#adminSidebarAccordion">
                            <div class="accordion-body">
                                <nav class="nav flex-column">
                                    <a class="nav-link {{ request()->routeIs('admin.loai_phong.*') ? 'active' : '' }}" href="{{ route('admin.loai_phong.index') }}" data-route="{{ route('admin.loai_phong.index') }}">
                                        <i class="fas fa-bed me-2"></i>Loại Phòng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.tien-nghi.*') ? 'active' : '' }}" href="{{ route('admin.tien-nghi.index') }}" data-route="{{ route('admin.tien-nghi.index') }}">
                                        <i class="fas fa-concierge-bell me-2"></i>Dịch vụ
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.vat-dung.*') ? 'active' : '' }}" href="{{ route('admin.vat-dung.index') }}" data-route="{{ route('admin.vat-dung.index') }}">
                                        <i class="fas fa-concierge-bell me-2"></i>Vật dụng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.phong.*') ? 'active' : '' }}" href="{{ route('admin.phong.index') }}" data-route="{{ route('admin.phong.index') }}">
                                        <i class="fas fa-bed me-2"></i>Phòng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.bed-types.*') ? 'active' : '' }}" href="{{ route('admin.bed-types.index') }}" data-route="{{ route('admin.bed-types.index') }}">
                                        <i class="fas fa-layer-group me-2"></i>Loại giường
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.tang.*') ? 'active' : '' }}" href="{{ route('admin.tang.index') }}" data-route="{{ route('admin.tang.index') }}">
                                        <i class="fas fa-layer-group me-2"></i>Quản Lý Tầng
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Section 2: Quản lý người dùng (chỉ admin) --}}
                    @if(auth()->user()->vai_tro === 'admin')
                    <div class="accordion-item border-0 bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers">
                                <i class="fas fa-users section-icon"></i>Quản lý người dùng
                            </button>
                        </h2>
                        <div id="collapseUsers" class="accordion-collapse collapse" data-bs-parent="#adminSidebarAccordion">
                            <div class="accordion-body">
                                <nav class="nav flex-column">
                                    <a class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}" href="{{ route('admin.user.index') }}" data-route="{{ route('admin.user.index') }}">
                                        <i class="fas fa-users me-2"></i>Quản Lý Khách Hàng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.nhan-vien.*') ? 'active' : '' }}" href="{{ route('admin.nhan-vien.index') }}" data-route="{{ route('admin.nhan-vien.index') }}">
                                        <i class="fas fa-user-tie me-2"></i>Quản Lý Nhân Viên
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.voucher.*') ? 'active' : '' }}" href="{{ route('admin.voucher.index') }}" data-route="{{ route('admin.voucher.index') }}">
                                        <i class="fas fa-gift me-2"></i>Quản Lý Voucher
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Section 3: Hoạt động hàng ngày (cả admin và nhân viên) --}}
                    <div class="accordion-item border-0 bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOperations">
                                <i class="fas fa-tasks section-icon"></i>Hoạt động hàng ngày
                            </button>
                        </h2>
                        <div id="collapseOperations" class="accordion-collapse collapse" data-bs-parent="#adminSidebarAccordion">
                            <div class="accordion-body">
                                <nav class="nav flex-column">
                                    <a class="nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}" href="{{ route('staff.index') }}" data-route="{{ route('staff.index') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>Thống kê (Dashboard)
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}" href="{{ route('staff.bookings') }}" data-route="{{ route('staff.bookings') }}">
                                        <i class="fas fa-calendar-check me-2"></i>Tổng quan booking
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('staff.rooms') ? 'active' : '' }}" href="{{ route('staff.rooms') }}" data-route="{{ route('staff.rooms') }}">
                                        <i class="fas fa-door-open me-2"></i>Quản Lý Tình Trạng Phòng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('staff.checkin') ? 'active' : '' }}" href="{{ route('staff.checkin') }}" data-route="{{ route('staff.checkin') }}">
                                        <i class="fas fa-user-check me-2"></i>Danh Sách Booking Check-in
                                    </a>
                                    {{-- <a class="nav-link {{ request()->routeIs('payment.pending-payments') ? 'active' : '' }}" href="{{ route('payment.pending_payments') }}" data-route="{{ route('payment.pending_payments') }}">
                                        <i class="fas fa-credit-card me-2"></i>Chờ thanh toán
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('staff.room-overview') ? 'active' : '' }}" href="{{ route('staff.room-overview') }}" data-route="{{ route('staff.room-overview') }}">
                                        <i class="fas fa-building me-2"></i>Tổng quan phòng
                                    </a> --}}
                                    <a class="nav-link {{ request()->routeIs('staff.reports') ? 'active' : '' }}" href="{{ route('staff.reports') }}" data-route="{{ route('staff.reports') }}">
                                        <i class="fas fa-file-alt me-2"></i>Báo cáo nhân viên
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: Thông báo (cả hai, nhưng admin có thêm) --}}
                    <div class="accordion-item border-0 bg-transparent">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNotifications">
                                <i class="fas fa-bell section-icon"></i>Thông báo
                            </button>
                        </h2>
                        <div id="collapseNotifications" class="accordion-collapse collapse" data-bs-parent="#adminSidebarAccordion">
                            <div class="accordion-body">
                                <nav class="nav flex-column">
                                    @if(auth()->user()->vai_tro === 'admin')
                                    <a class="nav-link {{ request()->routeIs('admin.customer-notifications.*') ? 'active' : '' }}" href="{{ route('admin.customer-notifications.index') }}" data-route="{{ route('admin.customer-notifications.index') }}">
                                        <i class="fas fa-users me-2"></i>Thông Báo Khách Hàng
                                    </a>
                                    <a class="nav-link {{ request()->routeIs('admin.internal-notifications.*') ? 'active' : '' }}" href="{{ route('admin.internal-notifications.index') }}" data-route="{{ route('admin.internal-notifications.index') }}">
                                        <i class="fas fa-building me-2"></i>Thông Báo Nội Bộ
                                    </a>
                                    @else
                                    <a class="nav-link" href="#">Thông báo cá nhân (sắp có)</a>  {{-- Placeholder cho nhân viên --}}
                                    @endif
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content (giữ nguyên từ version trước, với loading spinner fix) -->
            <div class="col-md-10 main-content">
                <!-- Header (giữ nguyên) -->
                <div class="bg-white shadow-sm border-bottom p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0" id="page-title">@yield('title', 'Admin Panel')</h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Notification Bell (giữ nguyên) -->
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
                                        <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-sm btn-primary" data-route="{{ route('admin.admin-notifications.index') }}">
                                            <i class="fas fa-eye me-1"></i>Xem tất cả thông báo
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- User Info -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->vai_tro) }})
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
                    <div class="content-loading" id="content-loading">  {{-- Không show mặc định --}}
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted" id="loading-text">Đang tải...</p>
                    </div>
                    <div id="main-content">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadAdminNotifications();
            loadAdminUnreadCount();

            setInterval(() => {
                loadAdminNotifications();
                loadAdminUnreadCount();
            }, 5000);

            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                const activeSection = activeLink.closest('.accordion-collapse');
                if (activeSection && !activeSection.classList.contains('show')) new bootstrap.Collapse(activeSection, { show: true });
            }

            document.addEventListener('click', e => {
                const link = e.target.closest('a[data-route]');
                if (link) {
                    e.preventDefault();
                    const url = link.getAttribute('data-route');
                    const title = link.textContent.trim() || 'Admin Panel';
                    loadContent(url, title, link);
                }
            });

            window.addEventListener('popstate', e => {
                if (e.state?.url) loadContent(e.state.url, e.state.title, null, true);
            });
        });

        function loadContent(url, title, activeLink = null, fromHistory = false) {
            const contentContainer = document.getElementById('main-content');
            const loadingEl = document.getElementById('content-loading');
            const loadingTextEl = document.getElementById('loading-text');
            const pageTitleEl = document.getElementById('page-title');

            if (!contentContainer || !loadingEl) return window.location.href = url;

            loadingEl.classList.add('show');
            loadingTextEl.textContent = 'Đang tải...';
            contentContainer.classList.remove('loaded');
            contentContainer.innerHTML = '';

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch(url, { credentials: 'include', signal: controller.signal })
                .then(res => {
                    clearTimeout(timeoutId);
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('main-content');
                    if (!newContent) throw new Error('Không tìm thấy #main-content');

                    contentContainer.innerHTML = newContent.innerHTML;
                    pageTitleEl.textContent = title;
                    contentContainer.classList.add('loaded');

                    if (typeof $ !== 'undefined') $(contentContainer).find('.select2').select2();

                    if (activeLink) {
                        document.querySelectorAll('.nav-link').forEach(a => a.classList.remove('active'));
                        activeLink.classList.add('active');
                    }

                    if (!fromHistory) history.pushState({ url, title }, '', url);
                })
                .catch(err => {
                    console.error('Load lỗi:', err);
                    loadingTextEl.textContent = 'Lỗi tải trang, đang làm mới...';
                    setTimeout(() => window.location.href = url, 1500);
                })
                .finally(() => loadingEl.classList.remove('show'));
        }
          function loadAdminNotifications() {
            fetch('/api/notifications/recent', {
                    credentials: 'include'
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateAdminNotificationList(data.data);
                    } else {
                        console.error('API error:', data.message);
                        document.getElementById('adminNotificationList').innerHTML =
                            '<div class="text-center p-3 text-muted">Không thể tải thông báo</div>';
                    }
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
                const payload = notification.payload || {
                    title: 'Thông báo',
                    message: 'Nội dung không hợp lệ'
                };

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
            fetch('/api/notifications/unread-count', {
                    credentials: 'include'
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateAdminNotificationBadge(data.count);
                    } else {
                        console.error('API error:', data.message);
                    }
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
            fetch('/api/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadAdminNotifications();
                        loadAdminUnreadCount();
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
