<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <!-- AI chatbox -->
<script data-name-bot="huy"
	src="https://app.preny.ai/embed-global.js"
	data-button-style="width:300px;height:300px;"
	data-language="vi"
	async
	defer
	data-preny-bot-id="692bcf49a98b11e2f6c759c8"
></script>

<!-- end chat -->
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

    @include('partials.header')
    <main>
        @yield('content')
    </main>
    @include('partials.footer')

    <!-- Scripts -->
    <script src="{{ asset('template/stackbros/assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/tiny-slider/tiny-slider.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/glightbox/js/glightbox.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/flatpickr/js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('template/stackbros/assets/vendor/choices/js/choices.min.js') }}"></script>

    <script src="{{ asset('template/stackbros/assets/js/functions.js') }}"></script>

    <!-- Vite Assets for Notifications -->
    @vite(['resources/js/app.js'])

    <!-- Client Notification Scripts -->
    <script>
        // Set user ID for notification manager
        window.userId = {{ auth()->id() ?? 'null' }};
    </script>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const CSRF_TOKEN = csrfMeta ? csrfMeta.getAttribute('content') : null;
            const TOGGLE_BASE = "{{ url('account/wishlist/toggle') }}";
            const LOGIN_URL = "{{ route('login') }}";

            function updateButtonUI(btn, status) {
                if (!btn) return;
                const icon = btn.querySelector('i');
                const label = btn.querySelector('.wl-label');
                const isDetail = !!label || btn.id === 'detail-wishlist-btn';

                if (status === 'added') {
                    btn.setAttribute('aria-pressed', 'true');
                    btn.setAttribute('title', 'Xóa khỏi danh sách yêu thích');
                    if (icon) {
                        icon.className = 'fa-solid fa-heart';
                        icon.classList.add('text-danger');
                    }
                    if (isDetail && label) label.textContent = 'Đã lưu';
                } else if (status === 'removed') {
                    btn.setAttribute('aria-pressed', 'false');
                    btn.setAttribute('title', 'Thêm vào danh sách yêu thích');
                    if (icon) {
                        icon.className = 'fa-regular fa-heart';
                        icon.classList.remove('text-danger');
                    }
                    if (isDetail && label) label.textContent = 'Thêm vào yêu thích';
                }
            }

            function showToast(msg, isError = false) {
                const d = document.createElement('div');
                d.className = 'alert ' + (isError ? 'alert-danger' : 'alert-success') + ' position-fixed';
                d.style.right = '20px';
                d.style.bottom = '20px';
                d.style.zIndex = 1150;
                d.innerText = msg;
                document.body.appendChild(d);
                setTimeout(() => d.remove(), 2200);
            }

            async function toggleWishlist(phongId, btn) {
                if (!CSRF_TOKEN) {
                    window.location.href = LOGIN_URL;
                    return;
                }

                const url = TOGGLE_BASE + '/' + phongId;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({})
                    });

                    const ctype = res.headers.get('content-type') || '';

                    if (!ctype.includes('application/json')) {
                        window.location.href = LOGIN_URL;
                        return;
                    }

                    const data = await res.json();

                    if (res.ok) {
                        if (data.status === 'added' || data.status === 'removed') {
                            updateButtonUI(btn, data.status);
                            showToast(data.status === 'added' ? 'Đã thêm vào yêu thích' : 'Đã xóa khỏi yêu thích');
                            if (btn.classList.contains('wishlist-toggle')) {
                                setTimeout(() => window.location.reload(), 400);
                            }
                        } else {
                            console.warn('Unknown wishlist payload', data);
                            showToast('Hoàn thành thao tác', false);
                        }
                    } else {
                        console.error('Wishlist error', data);
                        showToast(data.message || 'Đã xảy ra lỗi', true);
                    }
                } catch (err) {
                    console.error('Network/JSON error', err);
                    showToast('Lỗi mạng', true);
                }
            }

            document.body.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-wishlist, #detail-wishlist-btn, .wishlist-toggle');
                if (!btn) return;
                e.preventDefault();
                const phongId = btn.getAttribute('data-phong-id') || btn.getAttribute('data-phong');
                if (!phongId) return;
                toggleWishlist(phongId, btn);
            });
        });
    </script>

</body>

@push('styles')
    <style>
        .stretched-link::after {
            z-index: 1;
        }

        .btn-wishlist {
            z-index: 9999;
            pointer-events: auto;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.35);
            border: none;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }
        .sticky-sidebar {
    position: sticky;
    top: 80px; /* chỉnh tùy độ cao header */
}


        .btn-wishlist i {
            color: #fff;
            font-size: 14px;
        }

        .btn-wishlist i.text-danger {
            color: #dc3545 !important;
        }

        #detail-wishlist-btn {
            margin-top: .75rem;
        }

        #detail-wishlist-btn .wl-label {
            font-weight: 600;
        }
    </style>
@endpush
