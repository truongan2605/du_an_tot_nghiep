<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý khách sạn')</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
                @if(Auth::check() && in_array(Auth::user()->vai_tro, ['nhan_vien','admin']))
                    <a href="{{ route('staff.index') }}" class="nav-link {{ request()->routeIs('staff.index') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="{{ route('staff.bookings') }}" class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i> Tổng quan booking
                    </a>
                    <a href="{{ route('staff.pending-bookings') }}" class="nav-link {{ request()->routeIs('staff.pending-bookings') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check me-2"></i> Quản lý đặt phòng
                    </a>
                    <a href="{{ route('staff.rooms') }}" class="nav-link {{ request()->routeIs('staff.rooms') ? 'active' : '' }}">
                        <i class="bi bi-door-open me-2"></i> Danh sách trạng thái phòng
                    </a>
                    <a href="{{ route('payment.pending_payments') }}" class="nav-link {{ request()->routeIs('payment.pending-payments') ? 'active' : '' }}">
                        <i class="bi bi-credit-card me-2"></i> Chờ thanh toán
                    </a>
                    <a href="{{ route('staff.room-overview') }}" class="nav-link {{ request()->routeIs('staff.room-overview') ? 'active' : '' }}">
                        <i class="bi bi-house-fill me-2"></i> Tổng quan phòng
                    </a>
                    <a href="{{ route('staff.reports') }}" class="nav-link {{ request()->routeIs('staff.reports') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Báo cáo nhân viên
                    </a>
                @endif
            </nav>
        </aside>

        <!-- Main content -->
        <div class="flex-grow-1 ms-3">
            <!-- Header -->
            <header class="bg-white shadow-sm p-3 d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-secondary d-md-none sidebar-toggle-btn" onclick="toggleSidebar()">☰</button>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications -->
                    <button class="btn btn-light position-relative">
                        🔔
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $notificationsCount ?? 0 }}
                        </span>
                    </button>

                    <!-- User dropdown -->
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ auth()->user() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}" alt="avatar" class="rounded-circle border border-light shadow-sm" width="40" height="40">
                            <div class="ms-2 text-dark">
                                <div class="fw-semibold">{{ Auth::user()->name }}</div>
                                <small class="text-muted">{{ ucfirst(Auth::user()->vai_tro) }}</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" >Hồ sơ cá nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
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
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (el) {
                return new bootstrap.Tooltip(el);
            });
        });
    </script>
</body>

</html>
