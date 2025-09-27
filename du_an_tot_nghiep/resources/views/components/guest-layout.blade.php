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

        window.addEventListener('DOMContentLoaded', () => {
            var el = document.querySelector('.theme-icon-active');
            if (el != 'undefined' && el != null) {
                const showActiveTheme = theme => {
                    const activeThemeIcon = document.querySelector('.theme-icon-active use')
                    const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
                    const svgOfActiveBtn = btnToActive.querySelector('.mode-switch use').getAttribute('href')

                    document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
                        element.classList.remove('active')
                    })

                    btnToActive.classList.add('active')
                    activeThemeIcon.setAttribute('href', svgOfActiveBtn)
                }

                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (storedTheme !== 'light' || storedTheme !== 'dark') {
                        setTheme(getPreferredTheme())
                    }
                })

                showActiveTheme(getPreferredTheme())

                document.querySelectorAll('[data-bs-theme-value]')
                    .forEach(toggle => {
                        toggle.addEventListener('click', () => {
                            const theme = toggle.getAttribute('data-bs-theme-value')
                            localStorage.setItem('theme', theme)
                            setTheme(theme)
                            showActiveTheme(theme)
                        })
                    })

            }
        })
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

    @stack('styles')
</head>

<body class="@yield('body-class', 'has-navbar-mobile')">

    
    <main>
        {{ $slot }}
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
