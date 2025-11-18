<footer class="bg-dark pt-5">
	<div class="container">
		<!-- Row START -->
		<div class="row g-4">

			<!-- Widget 1 START -->
			<div class="col-lg-3">
				<!-- logo -->
				<a href="{{ url('/') }}">
					<img class="h-40px" src="{{ asset('template/stackbros/assets/images/logo-light.svg') }}" alt="logo">
				</a>
				<p class="my-3 text-body-secondary">Chúng tôi cam kết mang đến cho bạn những trải nghiệm đặt phòng khách sạn tuyệt vời và dịch vụ chăm sóc khách hàng tận tâm.</p>
				<p class="mb-2"><a href="#" class="text-body-secondary text-primary-hover"><i class="bi bi-telephone me-2"></i>038 201 3369</a> </p>
				<p class="mb-0"><a href="#" class="text-body-secondary text-primary-hover"><i class="bi bi-envelope me-2"></i>anltph51720@gmail.com</a></p>
			</div>
			<!-- Widget 1 END -->

			<!-- Widget 2 START -->
			<div class="col-lg-8 ms-auto">
				<div class="row g-4">
					<!-- Link block -->
					<div class="col-6 col-md-3">
						<h5 class="text-white mb-2 mb-md-4">Trang</h5>
						<ul class="nav flex-column text-primary-hover">
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Về chúng tôi</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Liên hệ</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Tin tức và Blog</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Gặp gỡ đội ngũ</a></li>
						</ul>
					</div>

					<!-- Link block -->
					<div class="col-6 col-md-3">
						<h5 class="text-white mb-2 mb-md-4">Liên kết</h5>
						<ul class="nav flex-column text-primary-hover">
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Đăng ký</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Đăng nhập</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Chính sách bảo mật</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Điều khoản</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Cookie</a></li>
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#">Hỗ trợ</a></li>
						</ul>
					</div>
									


					<!-- Link block -->
					<div class="col-6 col-md-3">
						<h5 class="text-white mb-2 mb-md-4">Đặt phòng</h5>
						<ul class="nav flex-column text-primary-hover">
							<li class="nav-item"><a class="nav-link text-body-secondary" href="#"><i class="fa-solid fa-hotel me-2"></i>Khách sạn</a></li>

						</ul>
					</div>
				</div>
			</div>
			<!-- Widget 2 END -->

		</div><!-- Row END -->

		<!-- Payment and card -->
		<div class="row g-4 justify-content-between mt-0 mt-md-2">

			<!-- Social media icon -->
			<div class="col-sm-5 col-md-6 col-lg-3 text-sm-end">
				<h5 class="text-white mb-2">Theo dõi chúng tôi</h5>
				<ul class="list-inline mb-0 mt-3">
					<li class="list-inline-item"> <a class="btn btn-sm px-2 bg-facebook mb-0" href="#"><i class="fab fa-fw fa-facebook-f"></i></a> </li>
					<li class="list-inline-item"> <a class="btn btn-sm shadow px-2 bg-instagram mb-0" href="#"><i class="fab fa-fw fa-instagram"></i></a> </li>
					<li class="list-inline-item"> <a class="btn btn-sm shadow px-2 bg-twitter mb-0" href="#"><i class="fab fa-fw fa-twitter"></i></a> </li>
					<li class="list-inline-item"> <a class="btn btn-sm shadow px-2 bg-linkedin mb-0" href="#"><i class="fab fa-fw fa-linkedin-in"></i></a> </li>
				</ul>	
			</div>
		</div>

		<!-- Divider -->
		<hr class="mt-4 mb-0">

		<!-- Bottom footer -->
		<div class="row">
			<div class="container">
				<div class="d-lg-flex justify-content-between align-items-center py-3 text-center text-lg-start">
					<!-- copyright links-->
					<div class="nav mt-2 mt-lg-0">
						<ul class="list-inline text-primary-hover mx-auto mb-0">
							<li class="list-inline-item me-0"><a class="nav-link text-body-secondary py-1" href="#">Chính sách bảo mật</a></li>
							<li class="list-inline-item me-0"><a class="nav-link text-body-secondary py-1" href="#">Điều khoản và điều kiện</a></li>
							<li class="list-inline-item me-0"><a class="nav-link text-body-secondary py-1 pe-0" href="#">Chính sách hoàn tiền</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</footer>
<!-- =======================
Footer END -->

<!-- Back to top -->
<div class="back-top"></div>

<!-- Navbar mobile START -->
<div class="navbar navbar-mobile">
	<ul class="navbar-nav">
		<!-- Nav item Home -->
		<li class="nav-item">
			<a class="nav-link active" href="{{ asset('template/stackbros/index.html') }}"><i class="bi bi-house-door fa-fw"></i>
				<span class="mb-0 nav-text">Trang chủ</span>
			</a>	
		</li>

		<!-- Nav item My Trips -->
		<li class="nav-item"> 
			<a class="nav-link" href="{{ asset('template/stackbros/account-bookings.html') }}"><i class="bi bi-briefcase fa-fw"></i>
				<span class="mb-0 nav-text">Chuyến đi của tôi</span>
			</a>	
		</li>

		<!-- Nav item Offer -->
		<li class="nav-item"> 
			<a class="nav-link" href="{{ asset('template/stackbros/offer-detail.html') }}"><i class="bi bi-percent fa-fw"></i>
				<span class="mb-0 nav-text">Ưu đãi</span> 
			</a>
		</li>

		<!-- Nav item Account -->
		<li class="nav-item"> 
			<a class="nav-link" href="{{ asset('template/stackbros/account-profile.html') }}"><i class="bi bi-person-circle fa-fw"></i>
				<span class="mb-0 nav-text">Tài khoản</span>
			</a>
		</li>
	</ul>
</div>
<!-- Navbar mobile END -->
