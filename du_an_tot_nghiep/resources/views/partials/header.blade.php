<!-- Header START -->
<header class="navbar-light header-sticky">
	<!-- Logo Nav START -->
	<nav class="navbar navbar-expand-xl">
		<div class="container">
			<!-- Logo START -->
			<a class="navbar-brand" href="{{ url('/') }}">
				<img class="light-mode-item navbar-brand-item" src="{{ asset('template/stackbros/assets/images/logo.svg') }}" alt="logo">
			</a>
			<!-- Logo END -->

			<!-- Responsive navbar toggler -->
			<button class="navbar-toggler ms-auto ms-sm-0 p-0 p-sm-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-animation">
					<span></span>
					<span></span>
					<span></span>
				</span>
        <span class="d-none d-sm-inline-block small">Menu</span>
			</button>

			<!-- Responsive category toggler -->
			<button class="navbar-toggler ms-sm-auto mx-3 me-md-0 p-0 p-sm-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCategoryCollapse" aria-controls="navbarCategoryCollapse" aria-expanded="false" aria-label="Toggle navigation">
				<i class="bi bi-grid-3x3-gap-fill fa-fw"></i><span class="d-none d-sm-inline-block small">Category</span>
			</button>

			<!-- Main navbar START -->
			<div class="navbar-collapse collapse" id="navbarCollapse">
				<ul class="navbar-nav navbar-nav-scroll me-auto">

					<!-- Nav item Listing -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" id="listingMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Listings</a>
						<ul class="dropdown-menu" aria-labelledby="listingMenu">
							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">Hotel</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/index.html') }}">Hotel Home</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/index-hotel-chain.html') }}">Hotel Chain</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/index-resort.html') }}">Hotel Resort</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/hotel-grid.html') }}">Hotel Grid</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/hotel-list.html') }}">Hotel List</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/hotel-detail.html') }}">Hotel Detail</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/room-detail.html') }}">Room Detail</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/hotel-booking.html') }}">Hotel Booking</a></li>
								</ul>
							</li>

							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/booking-confirm.html') }}">Booking Confirmed</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/compare-listing.html') }}">Compare Listing</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/offer-detail.html') }}">Offer Detail</a></li>
						</ul>
					</li>

					<!-- Nav item Pages -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" id="pagesMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Pages</a>
						<ul class="dropdown-menu" aria-labelledby="pagesMenu">

							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/about.html') }}">About</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/contact.html') }}">Contact</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/contact-2.html') }}">Contact 2</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/team.html') }}">Our Team</a></li>

							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">Authentication</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/sign-in.html') }}">Sign In</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/sign-up.html') }}">Sign Up</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/forgot-password.html') }}">Forgot Password</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/two-factor-auth.html') }}">Two factor authentication</a></li>
								</ul>
							</li>

							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">Blog</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/blog.html') }}">Blog</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/blog-detail.html') }}">Blog Detail</a></li>
								</ul>
							</li>

							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">Help</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/help-center.html') }}">Help Center</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/help-detail.html') }}">Help Detail</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/privacy-policy.html') }}">Privacy Policy</a></li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/terms-of-service.html') }}">Terms of Service</a></li>
								</ul>
							</li>

							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/pricing.html') }}">Pricing</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/faq.html') }}">FAQs</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/error.html') }}">Error 404</a></li>
							<li> <a class="dropdown-item" href="{{ asset('template/stackbros/coming-soon.html') }}">Coming Soon</a></li>
						</ul>
					</li>

					<!-- Nav item Account -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" id="accounntMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Accounts</a>
						<ul class="dropdown-menu" aria-labelledby="accounntMenu">
							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">User Profile</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-profile.html') }}">My Profile</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-bookings.html') }}">My Bookings</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-travelers.html') }}">Travelers</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-payment-details.html') }}">Payment Details</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-wishlist.html') }}">Wishlist</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-settings.html') }}">Settings</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/account-delete.html') }}">Delete Profile</a> </li>
								</ul>
							</li>

							<!-- Dropdown submenu -->
							<li class="dropdown-submenu dropend">
								<a class="dropdown-item dropdown-toggle" href="#">Agent Dashboard</a>
								<ul class="dropdown-menu" data-bs-popper="none">
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-dashboard.html') }}">Dashboard</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-listings.html') }}">Listings</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-bookings.html') }}">Bookings</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-activities.html') }}">Activities</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-earnings.html') }}">Earnings</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-reviews.html') }}">Reviews</a> </li>
									<li> <a class="dropdown-item" href="{{ asset('template/stackbros/agent-settings.html') }}">Settings</a> </li>
								</ul>
							</li>
							
							@auth
								@if(auth()->user()->isAdmin())
									<li> <a class="dropdown-item" href="{{ route('admin.tien-nghi.index') }}">Master Admin</a> </li>
								@endif
							@endauth
						</ul>
					</li>

          <!-- Nav item link-->
					<li class="nav-item dropdown d-none">
						<a class="nav-link" href="#" id="advanceMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-ellipsis-h"></i>
						</a>
						<ul class="dropdown-menu min-w-auto" data-bs-popper="none">
							<li> 
								<a class="dropdown-item" href="#" target="_blank">
									<i class="text-warning fa-fw bi bi-life-preserver me-2"></i>Support
								</a> 
							</li>
							<li> 
								<a class="dropdown-item" href="{{ asset('template/stackbros/docs/index.html') }}" target="_blank">
									<i class="text-danger fa-fw bi bi-card-text me-2"></i>Documentation
								</a> 
							</li>
							<li> <hr class="dropdown-divider"></li>
							<li> 
								<a class="dropdown-item" href="#" target="_blank">
									<i class="text-info fa-fw bi bi-toggle-off me-2"></i>RTL demo
								</a> 
							</li>
							<li> 
								<a class="dropdown-item" href="#" target="_blank">
									<i class="text-success fa-fw bi bi-cloud-download-fill me-2"></i>Buy Booking!
								</a> 
							</li>
							<li> <hr class="dropdown-divider"></li>
							<li> 
								<a class="dropdown-item" href="{{ asset('template/stackbros/docs/alerts.html') }}" target="_blank">
									<i class="text-orange fa-fw bi bi-puzzle-fill me-2"></i>Components
								</a> 
							</li>
						</ul>
					</li>
				</ul>
			</div>
			<!-- Main navbar END -->

			<!-- Nav category menu START -->
			<div class="navbar-collapse collapse" id="navbarCategoryCollapse">
				<ul class="navbar-nav navbar-nav-scroll nav-pills-primary-soft text-center ms-auto p-2 p-xl-0">
					<!-- Nav item Hotel -->
					<li class="nav-item"> <a class="nav-link active" href="{{ asset('template/stackbros/index.html') }}"><i class="fa-solid fa-hotel me-2"></i>Hotel</a>	</li>

					
				</ul>
			</div>
			<!-- Nav category menu END -->

			<!-- Profile and Notification START -->
			<ul class="nav flex-row align-items-center list-unstyled ms-xl-auto">

				<!-- Notification dropdown START -->
				<li class="nav-item dropdown ms-0 ms-md-3">
					<!-- Notification button -->
					<a class="nav-notification btn btn-light p-0 mb-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
						<i class="bi bi-bell fa-fw"></i>
					</a>
					<!-- Notification dote -->
					@auth
					@php
						$__unreadCount = \App\Models\ThongBao::where('nguoi_nhan_id', auth()->id())
							->where('trang_thai', '!=', 'read')
							->count();
					@endphp
					@if($__unreadCount > 0)
					<span class="notif-badge animation-blink"></span>
					@endif
					@endauth

					<!-- Notification dropdown menu START -->
					<div class="dropdown-menu dropdown-animation dropdown-menu-end dropdown-menu-size-md shadow-lg p-0">
						<div class="card bg-transparent">
							<!-- Card header -->
							<div class="card-header bg-transparent d-flex justify-content-between align-items-center border-bottom">
								<h6 class="m-0">Thông báo @auth @if($__unreadCount>0)<span class="badge bg-danger bg-opacity-10 text-danger ms-2">{{ $__unreadCount }} mới</span>@endif @endauth</h6>
								@auth
								<!-- Form removed - no longer needed -->
								@endauth
							</div>

							<!-- Card body START -->
							<div class="card-body p-0">
								<ul class="list-group list-group-flush list-unstyled p-2">
									@auth
									@php
										$__notifications = \App\Models\ThongBao::where('nguoi_nhan_id', auth()->id())
											->latest('id')
											->limit(10)
											->get();
									@endphp
									@forelse($__notifications as $__n)
									<li>
										<a href="{{ route('notifications.show', $__n->id) }}" 
										   class="list-group-item list-group-item-action rounded border-0 mb-1 p-3 w-100 text-start notification-item {{ $__n->trang_thai !== 'read' ? 'notif-unread' : '' }}">
											<h6 class="mb-1">{{ $__n->payload['title'] ?? $__n->ten_template }}</h6>
											<p class="mb-0 small">{{ $__n->payload['message'] ?? '' }}</p>
											<span class="small text-muted">{{ $__n->created_at?->diffForHumans() }}</span>
										</a>
									</li>
									@empty
									<li class="text-center text-muted py-3">Không có thông báo</li>
									@endforelse
									@endauth
								</ul>
							</div>
							<!-- Card body END -->

							<!-- Card footer -->
							<div class="card-footer bg-transparent text-center border-top">
								<button class="btn btn-sm btn-link mb-0 p-0" onclick="notificationManager.markAllAsRead()">
									<i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
								</button>
							</div>
						</div>
					</div>
					<!-- Notification dropdown menu END -->
				</li>
				<!-- Notification dropdown END -->

				<!-- Profile dropdown START -->
				<li class="nav-item ms-3 dropdown">
					<!-- Avatar -->
					<a class="avatar avatar-sm p-0" href="#" id="profileDropdown" role="button"
					data-bs-auto-close="outside" data-bs-display="static" data-bs-toggle="dropdown" aria-expanded="false">
						<img class="avatar-img rounded-2"
							src="{{ auth()->check() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
							alt="avatar">
					</a>

					<ul class="dropdown-menu dropdown-animation dropdown-menu-end shadow pt-3" aria-labelledby="profileDropdown">
						@auth
						<!-- Profile info -->
						<li class="px-3 mb-3">
							<div class="d-flex align-items-center">
								<!-- Avatar -->
								<div class="avatar me-3">
									<img class="avatar-img rounded-circle shadow"
										src="{{ auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
										alt="avatar">
								</div>
								<div>
									<a class="h6 mt-2 mt-sm-0" href="{{ url('/profile') }}">{{ auth()->user()->name }}</a>
									<p class="small m-0">{{ auth()->user()->email }}</p>
								</div>
							</div>
						</li>

						<!-- Links -->
						<li> <hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="{{ url('/account/bookings') }}"><i class="bi bi-bookmark-check fa-fw me-2"></i>My Bookings</a></li>
						<li><a class="dropdown-item" href="{{ url('/account/wishlist') }}"><i class="bi bi-heart fa-fw me-2"></i>My Wishlist</a></li>
						<li><a class="dropdown-item" href="{{ route('account.settings') }}"><i class="bi bi-gear fa-fw me-2"></i>Settings</a></li>
						<li><a class="dropdown-item" href="{{ url('/help') }}"><i class="bi bi-info-circle fa-fw me-2"></i>Help Center</a></li>

						<li> <hr class="dropdown-divider"></li>

						<!-- Logout (POST) -->
						<li class="px-3">
							<form method="POST" action="{{ route('logout') }}">
								@csrf
								<button type="submit" class="dropdown-item bg-danger-soft-hover">
									<i class="bi bi-power fa-fw me-2"></i>Sign Out
								</button>
							</form>
						</li>
						@endauth

						@guest
						<li class="px-3 mb-2">
							<div class="d-flex align-items-center">
								<div>
									<a class="h6 mt-2 mt-sm-0" href="{{ route('login') }}">Guest</a>
									<p class="small m-0">Login to use all features</p>
								</div>
							</div>
						</li>
						<li> <hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a></li>
						<li><a class="dropdown-item" href="{{ route('register') }}"><i class="bi bi-person-plus me-2"></i>Sign Up</a></li>
						@endguest
					</ul>
				</li>
				<!-- Profile dropdown END -->
			</ul>
			<!-- Profile and Notification START -->

		</div>
	</nav>
	<!-- Logo Nav END -->
</header>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">
                    <i class="fas fa-bell me-2"></i>Chi tiết thông báo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin thông báo...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="markAsReadBtn" style="display: none;">
                    <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('notificationModal');
    const modalBody = document.getElementById('notificationModalBody');
    const markAsReadBtn = document.getElementById('markAsReadBtn');
    let currentNotificationId = null;
    
    // Handle modal show event
    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        currentNotificationId = button.getAttribute('data-notification-id');
        
        // Reset modal content
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2 text-muted">Đang tải thông tin thông báo...</p>
            </div>
        `;
        
        // Hide mark as read button initially
        markAsReadBtn.style.display = 'none';
        
        // Fetch notification details via AJAX
        console.log('Fetching notification:', currentNotificationId);
        
        // Add timeout to prevent infinite loading
        const timeoutId = setTimeout(() => {
            modalBody.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-clock me-1"></i>
                    Tải thông báo quá lâu. Vui lòng thử lại.
                    <br><br>
                    <button class="btn btn-sm btn-outline-warning" onclick="location.reload()">
                        <i class="fas fa-refresh me-1"></i>Thử lại
                    </button>
                    <button class="btn btn-sm btn-outline-info ms-2" onclick="testRoute(${currentNotificationId})">
                        <i class="fas fa-bug me-1"></i>Test Route
                    </button>
                </div>
            `;
        }, 10000); // 10 seconds timeout
        
        fetch(`/thong-bao/${currentNotificationId}/modal`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            clearTimeout(timeoutId); // Clear timeout on successful response
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                modalBody.innerHTML = data.html;
                
                // Show mark as read button if notification is unread
                if (data.isUnread) {
                    markAsReadBtn.style.display = 'inline-block';
                }
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Không thể tải thông tin thông báo: ${data.message || 'Lỗi không xác định'}
                    </div>
                `;
            }
        })
        .catch(error => {
            clearTimeout(timeoutId); // Clear timeout on error
            console.error('Fetch error:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Lỗi khi tải thông tin thông báo. Vui lòng thử lại.<br>
                    <small>Chi tiết: ${error.message}</small>
                    <br><br>
                    <button class="btn btn-sm btn-outline-danger" onclick="location.reload()">
                        <i class="fas fa-refresh me-1"></i>Thử lại
                    </button>
                </div>
            `;
        });
    });
    
    // Test route function
    window.testRoute = function(notificationId) {
        console.log('Testing route for notification:', notificationId);
        fetch(`/test-notification/${notificationId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Test response:', data);
                document.getElementById('notificationModalBody').innerHTML = data.html;
            })
            .catch(error => {
                console.error('Test route error:', error);
                document.getElementById('notificationModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        Test route failed: ${error.message}
                    </div>
                `;
            });
    };
    
    // Handle mark as read button
    markAsReadBtn.addEventListener('click', function() {
        if (currentNotificationId) {
            // Mark as read via AJAX
            fetch(`/thong-bao/${currentNotificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                // Hide mark as read button
                markAsReadBtn.style.display = 'none';
                
                // Remove unread styling from notification item
                const notificationItem = document.querySelector(`[data-notification-id="${currentNotificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('notif-unread');
                }
                
                // Update badge count
                updateNotificationBadge();
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check me-1"></i>Đã đánh dấu thông báo là đã đọc
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                modalBody.insertBefore(alert, modalBody.firstChild);
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-triangle me-1"></i>Lỗi khi đánh dấu đã đọc
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                modalBody.insertBefore(alert, modalBody.firstChild);
            });
        }
    });
    
    // Function to update notification badge
    function updateNotificationBadge() {
        fetch('/api/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notif-badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.style.display = 'block';
                        badge.textContent = data.count;
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error updating badge:', error);
            });
    }
});
</script>
