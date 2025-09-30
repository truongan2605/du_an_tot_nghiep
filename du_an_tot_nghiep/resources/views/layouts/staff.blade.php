<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Quản lý khách sạn')</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="d-flex">
        <aside class="bg-dark shadow-sm p-3" style="width: 250px; min-height: 100vh;">
            <h4 class="text-white fw-bold">🏨 Hotel Manager</h4>
            <nav class="nav flex-column mt-4">
                @if (Auth::check() && Auth::user()->vai_tro === 'nhan_vien')
                   
                    <a href="{{ route('staff.bookings') }}" class="nav-link {{ request()->routeIs('staff.bookings') ? 'active' : '' }}">📋 Quản lý đặt phòng</a>
                @endif
            </nav>
        </aside>

        <div class="flex-grow-1 ms-3">
            <header class="bg-white shadow-sm p-3 d-flex justify-content-between align-items-center">
                <div>
                    <input type="text" class="form-control" placeholder="Tìm kiếm...">
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light">🔔</button>
                    <div class="d-flex align-items-center gap-2">
                        <img src="https://i.pravatar.cc/40?{{ Auth::id() }}" alt="avatar" class="rounded-circle" width="40" height="40">
                        <span>{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </header>

            <main class="p-4">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>