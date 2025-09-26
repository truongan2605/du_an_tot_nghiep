<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - @yield('title')</title>

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- FontAwesome icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .sidebar {
            min-width: 220px;
            max-width: 220px;
            background: #343a40;
            color: #fff;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
        }
        .sidebar a:hover {
            background: #495057;
            color: #fff;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        select option {
    color: black; 
    background-color: white;
}

select option:checked {
    color: black !important;
    background-color: #d6e9ff !important; /* xanh nhạt hơn */
}

    </style>
</head>
<body>

    {{-- Header --}}
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1">
        {{-- Sidebar --}}
        <div class="sidebar">
            <h5 class="p-3 border-bottom">Menu</h5>
            {{-- <a href="{{ route('admin.rooms.index') }}"><i class="fa fa-bed"></i> Rooms</a> --}}
            <a href="{{ route('admin.phong.index') }}"><i class="fa fa-bed"></i> Rooms</a>
            <a href="#"><i class="fa fa-users"></i> Users</a>
            <a href="#"><i class="fa fa-cog"></i> Settings</a>
        </div>

        {{-- Content --}}
        <div class="content w-100">
            @yield('content')
        </div>
    </div>

    {{-- Footer --}}
    <footer class="bg-dark text-white text-center py-2">
        <small>&copy; 2025 Admin Panel</small>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
