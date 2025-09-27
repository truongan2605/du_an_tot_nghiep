<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Booking')</title>

    <script>
        const storedTheme = localStorage.getItem('theme')

        const getPreferredTheme = () => {
            if (storedTheme) {
                return storedTheme
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }

        const setTheme = function(theme) {
            if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-bs-theme', 'dark')
            } else {
                document.documentElement.setAttribute('data-bs-theme', theme)
            }
        }

        setTheme(getPreferredTheme())
    </script>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('template/stackbros/assets/images/favicon.ico') }}">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/font-awesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/tiny-slider/tiny-slider.css') }}">
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/glightbox/css/glightbox.css') }}">
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/flatpickr/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/vendor/choices/css/choices.min.css') }}">

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('template/stackbros/assets/css/style.css') }}">

    <style>
        .auth-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem .75rem;
        }
        .auth-home-btn {
            min-width: 140px;
        }
        .auth-home-fab {
            position: fixed;
            left: 1rem;
            top: 1rem;
            z-index: 1050;
            border-radius: 999px;
            padding: .5rem .65rem;
        }
    </style>

    @stack('styles')
</head>
<body class="@yield('body-class', '')">

    <div class="container-fluid auth-topbar">
        <div class="d-flex align-items-center">
            <a href="{{ url('/') }}" class="d-inline-block me-3">
                <img src="{{ asset('template/stackbros/assets/images/logo-icon.svg') }}" alt="logo" style="height:36px;">
            </a>
            <span class="text-muted small d-none d-sm-inline">Welcome to Booking</span>
        </div>

        <div class="d-flex align-items-center">
            <a href="{{ url('/') }}" class="btn btn-outline-primary auth-home-btn d-flex align-items-center">
                <i class="bi bi-house-door-fill me-2"></i> Back to Home
            </a>
        </div>
    </div>

    <main>
        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="{{ asset('template/stackbros/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/tiny-slider/tiny-slider.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/glightbox/js/glightbox.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/flatpickr/js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/choices/js/choices.min.js') }}"></script>

    <script src="{{ asset('template/stackbros/assets/js/functions.js') }}"></script>

    @stack('scripts')
</body>
</html>
