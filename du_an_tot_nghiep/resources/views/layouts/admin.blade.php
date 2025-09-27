<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Admin Panel')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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
                    <a class="nav-link {{ request()->routeIs('tien-nghi.*') ? 'active' : '' }}" href="{{ route('admin.tien-nghi.index') }}">
                        <i class="fas fa-concierge-bell me-2"></i> Tiện nghi
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-bed me-2"></i> Phòng
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-users me-2"></i> Người dùng
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar me-2"></i> Thống kê
                    </a>
                    <a class="nav-link" href="{{ route('voucher.index') }}">
                        <i class="fas fa-chart-bar me-2"></i> Voucher
                    </a>
                </nav>
            </div>

            <!-- Main content -->
            <div class="col-md-10 main-content">
                <div class="p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
