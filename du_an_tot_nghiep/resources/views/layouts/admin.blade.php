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
            background: linear-gradient(180deg, #343a40 0%, #2c3237 100%);
            border-right: 1px solid #495057;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1030;
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #495057;
            text-align: center;
        }

        .sidebar-header h4 {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .sidebar-nav {
            padding: 0.5rem 0;
            overflow-y: auto;
            height: calc(100vh - 120px);
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #adb5bd;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: 0;
            border-left: 3px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0 0.25rem;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #007bff;
            transform: translateX(2px);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
            border-left-color: #007bff;
        }

        .sidebar .nav-link i {
            width: 18px;
            margin-right: 0.75rem;
            font-size: 1rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link.active i {
            transform: scale(1.1);
        }

        .sidebar-section {
            margin-bottom: 0.5rem;
            overflow: hidden;
        }

        .sidebar-section-header {
            cursor: pointer;
            padding: 0.5rem 1rem;
            color: #6c757d;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-section-header:hover {
            color: #adb5bd;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar-section-header i {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.875rem;
        }

        .sidebar-section-header.collapsed i {
            transform: rotate(-90deg);
        }

        .sidebar-section-content {
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #495057, transparent);
            margin: 1rem 1rem;
        }

        .main-wrapper {
            margin-left: 250px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .content-area {
            padding: 2rem;
            transition: opacity 0.3s ease;
        }

        .content-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Read-only mode styling (tùy chọn, disable buttons nếu session read_only_mode) */
        @if(session('read_only_mode'))
        .readonly-mode .btn:not([disabled]) {
            pointer-events: none;
            opacity: 0.6;
        }
        .readonly-mode form[method="POST"], .readonly-mode form[method="PUT"], .readonly-mode form[method="DELETE"] {
            display: none;
        }
        @endif

        /* Responsive: Sidebar collapses on mobile */
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            .main-wrapper {
                margin-left: 0;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1020;
                display: none;
                transition: opacity 0.3s ease;
            }
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4>Admin Panel</h4>
        </div>
        <div class="sidebar-nav">
            {{-- Menu Admin (luôn hiển thị, nhưng nhan_vien chỉ xem được nếu GET) --}}
            <div class="sidebar-section">
                <div class="sidebar-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#section-coso" aria-expanded="false">
                    <span>Quản lý Cơ sở</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sidebar-section-content collapse" id="section-coso">
                    <a class="nav-link {{ request()->routeIs('loai_phong.*') ? 'active' : '' }}" data-url="{{ route('admin.loai_phong.index') }}" data-section="section-coso">
                        <i class="fas fa-bed"></i>Loại Phòng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.phong.*') ? 'active' : '' }}" data-url="{{ route('admin.phong.index') }}" data-section="section-coso">
                        <i class="fas fa-door-open"></i>Phòng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.tang.*') ? 'active' : '' }}" data-url="{{ route('admin.tang.index') }}" data-section="section-coso">
                        <i class="fas fa-building"></i>Quản Lý Tầng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.bed-types.*') ? 'active' : '' }}" data-url="{{ route('admin.bed-types.index') }}" data-section="section-coso">
                        <i class="fas fa-bed"></i>Loại Giường
                    </a>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#section-dichvu" aria-expanded="false">
                    <span>Quản lý Dịch vụ</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sidebar-section-content collapse" id="section-dichvu">
                    <a class="nav-link" data-url="#" data-section="section-dichvu">
                        <i class="fas fa-chart-bar"></i>Thống kê
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.tien-nghi.*') ? 'active' : '' }}" data-url="{{ route('admin.tien-nghi.index') }}" data-section="section-dichvu">
                        <i class="fas fa-concierge-bell"></i>Dịch vụ
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.vat-dung.*') ? 'active' : '' }}" data-url="{{ route('admin.vat-dung.index') }}" data-section="section-dichvu">
                        <i class="fas fa-utensils"></i>Vật dụng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.voucher.*') ? 'active' : '' }}" data-url="{{ route('admin.voucher.index') }}" data-section="section-dichvu">
                        <i class="fas fa-gift"></i>Voucher
                    </a>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#section-nguoidung" aria-expanded="false">
                    <span>Quản lý Người dùng</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sidebar-section-content collapse" id="section-nguoidung">
                    <a class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}" data-url="{{ route('admin.user.index') }}" data-section="section-nguoidung">
                        <i class="fas fa-users"></i>Khách Hàng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.nhan-vien.*') ? 'active' : '' }}" data-url="{{ route('admin.nhan-vien.index') }}" data-section="section-nguoidung">
                        <i class="fas fa-user-tie"></i>Nhân Viên
                    </a>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#section-thongbao" aria-expanded="false">
                    <span>Thông báo</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sidebar-section-content collapse" id="section-thongbao">
                    <a class="nav-link {{ request()->routeIs('admin.customer-notifications.*') ? 'active' : '' }}" data-url="{{ route('admin.customer-notifications.index') }}" data-section="section-thongbao">
                        <i class="fas fa-users"></i>Khách Hàng
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.internal-notifications.*') ? 'active' : '' }}" data-url="{{ route('admin.internal-notifications.index') }}" data-section="section-thongbao">
                        <i class="fas fa-building"></i>Nội Bộ
                    </a>
                </div>
            </div>

            {{-- Menu Staff (hiển thị cho cả admin và nhan_vien, bỏ Thanh toán và Tổng quan) --}}
            @if(auth()->check() && in_array(auth()->user()->vai_tro, ['nhan_vien', 'admin']))
            <div class="sidebar-divider"></div>
            <div class="sidebar-section">
                <div class="sidebar-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#section-staff" aria-expanded="false">
                    <span>Staff Panel</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="sidebar-section-content collapse" id="section-staff">
                    <a class="nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}" data-url="{{ route('staff.index') }}" data-section="section-staff">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}" data-url="{{ route('staff.bookings') }}" data-section="section-staff">
                        <i class="fas fa-calendar-check"></i>Bookings
                    </a>
                    <a class="nav-link {{ request()->routeIs('staff.rooms') ? 'active' : '' }}" data-url="{{ route('staff.rooms') }}" data-section="section-staff">
                        <i class="fas fa-door-open"></i>Phòng
                    </a>
                    <a class="nav-link {{ request()->routeIs('staff.checkin') ? 'active' : '' }}" data-url="{{ route('staff.checkin') }}" data-section="section-staff">
                        <i class="fas fa-sign-in-alt"></i>Check-in
                    </a>
                    <a class="nav-link {{ request()->routeIs('staff.reports') ? 'active' : '' }}" data-url="{{ route('staff.reports') }}" data-section="section-staff">
                        <i class="fas fa-chart-line"></i>Báo cáo
                    </a>
                </div>
            </div>
            @endif
        </div>
    </nav>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <!-- Main wrapper -->
    <div class="main-wrapper">
        <div class="main-content">
            <!-- Header -->
            <header class="bg-white shadow-sm border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-md-none">
                        <button class="btn btn-outline-secondary" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    <div>
                        <h5 class="mb-0" id="pageTitle">@yield('title', 'Admin Panel')</h5>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        {{-- Nút Staff Panel giờ chỉ là link, vì layout chung --}}
                        @if(auth()->check() && auth()->user()->vai_tro === 'admin')
                        <a href="{{ route('staff.index') }}" class="btn btn-outline-success btn-sm" data-url="{{ route('staff.index') }}">
                            <i class="fas fa-user-tie me-1"></i>Staff
                        </a>
                        @endif
                        
                        <!-- Notification Bell (giữ nguyên) -->
                        <div class="dropdown">
                            <button class="btn btn-outline-primary position-relative btn-sm" type="button"
                                id="adminNotificationDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="adminNotificationBadge" style="display: none;">
                                    0
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Thông báo</span>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="markAllAdminNotificationsAsRead()">
                                        <i class="fas fa-check-double"></i> Đánh dấu tất cả
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
                                    <a href="{{ route('admin.admin-notifications.index') }}" class="btn btn-sm btn-primary" data-url="{{ route('admin.admin-notifications.index') }}">
                                        <i class="fas fa-eye me-1"></i>Xem tất cả
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>{{ auth()->user()?->name ?? 'Guest' }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('home') }}" data-url="{{ route('home') }}">
                                        <i class="fas fa-home me-2"></i>Về trang chủ
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
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
            </header>

            <!-- Content Area -->
            <div class="content-area" id="contentArea">
                {{-- Thêm class readonly-mode nếu session có --}}
                <div class="@if(session('read_only_mode') && !request()->routeIs('staff.*')) readonly-mode @endif">
        @yield('content')
    </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dynamic Navigation Script (AJAX load to prevent full reload) -->
    <script>
        // Global variables
        let currentUrl = window.location.pathname + window.location.search;

        // Function to load content via AJAX
        function loadContent(url, title = 'Admin Panel') {
            const contentArea = document.getElementById('contentArea');
            const pageTitle = document.getElementById('pageTitle');
            contentArea.classList.add('content-loading');

            // Update title
            pageTitle.textContent = title;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'include'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Parse HTML to extract content (assuming views have @section('content'))
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('#contentArea')?.innerHTML || html;

                // Inject content
                contentArea.innerHTML = newContent;

                // Re-init Select2 or other plugins if needed
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2').select2();
                }

                // Handle read-only mode: Remove if staff route (allow actions), keep for admin routes
                const contentDiv = contentArea.querySelector('div');
                if (url.startsWith('/staff') && contentDiv) {
                    contentDiv.classList.remove('readonly-mode');
                }

                contentArea.classList.remove('content-loading');
            })
            .catch(error => {
                console.error('Error loading content:', error);
                contentArea.innerHTML = '<div class="alert alert-danger">Lỗi tải nội dung. <a href="' + url + '">Tải lại trang</a></div>';
                contentArea.classList.remove('content-loading');
            });
        }

        // Handle nav-link clicks
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nav-link[data-url], .dropdown-item[data-url], a[data-url]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');
                    const sectionId = this.getAttribute('data-section');
                    if (url && url !== '#') {
                        // Update active state
                        document.querySelectorAll('.nav-link.active').forEach(active => active.classList.remove('active'));
                        this.classList.add('active');

                        // If in a section, ensure it's open (do not collapse)
                        if (sectionId) {
                            const section = document.getElementById(sectionId);
                            const header = document.querySelector(`[data-bs-target="#${sectionId}"]`);
                            if (header && !section.classList.contains('show')) {
                                // Only open if closed
                                const collapse = new bootstrap.Collapse(section, { show: true });
                                const icon = header.querySelector('i');
                                if (icon) icon.classList.remove('collapsed');
                            }
                            // No collapse logic - keep open
                        }

                        loadContent(url);
                        window.history.pushState({url: url}, '', url);
                    }
                });
            });

            // Handle browser back/forward
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.url) {
                    loadContent(event.state.url);
                }
            });

            // Initial load if needed
            const initialActive = document.querySelector('.nav-link.active[data-url]');
            if (initialActive) {
                currentUrl = initialActive.getAttribute('data-url');
            }
        });

        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Handle sidebar section collapse (optimized with Bootstrap native)
        document.querySelectorAll('.sidebar-section-header').forEach(header => {
            header.addEventListener('click', function(e) {
                e.stopPropagation();
                const target = this.getAttribute('data-bs-target');
                const content = document.querySelector(target);
                const collapse = bootstrap.Collapse.getInstance(content) || new bootstrap.Collapse(content, { toggle: true });
                const icon = this.querySelector('i');

                // Toggle and update icon
                collapse.toggle();
                if (content.classList.contains('show')) {
                    icon.classList.remove('collapsed');
                } else {
                    icon.classList.add('collapsed');
                }
            });
        });

        // Auto-expand section if active route inside (optimized)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.sidebar-section-content').forEach(section => {
                if (section.querySelector('.active')) {
                    const targetId = section.id;
                    const header = document.querySelector(`[data-bs-target="#${targetId}"]`);
                    if (header) {
                        header.classList.remove('collapsed');
                        new bootstrap.Collapse(section, { show: true });
                    }
                }
            });
        });
    </script>

    <!-- Admin Notification Scripts (giữ nguyên) -->
    <script>
        // Load admin notifications
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
            }, 30000);
        });
    </script>
    
    @stack('scripts')
    @yield('scripts')
</body>

</html>