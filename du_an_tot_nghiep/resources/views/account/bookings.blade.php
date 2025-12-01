@extends('layouts.app')

@section('title', 'Đặt phòng của tôi')

@section('content')
    <section class="pt-3">
        <div class="container">
            <div class="row g-2 g-lg-4">

                <!-- Sidebar START -->
                <div class="col-lg-4 col-xl-3">
                    <div class="offcanvas-lg offcanvas-end" tabindex="-1" id="offcanvasSidebar">
                        <div class="offcanvas-header justify-content-end pb-2">
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                data-bs-target="#offcanvasSidebar" aria-label="Close"></button>
                        </div>

                        <div class="offcanvas-body p-3 p-lg-0">
                            <div class="card bg-light w-100">
                                <div class="position-absolute top-0 end-0 p-3">
                                    <a href="{{ route('account.settings') }}" class="text-primary-hover"
                                        data-bs-toggle="tooltip" data-bs-title="Edit profile">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>

                                <div class="card-body p-3">
                                    <div class="text-center mb-3">
                                        <div class="avatar avatar-xl mb-2">
                                            <img class="avatar-img rounded-circle border border-2 border-white"
                                                src="{{ auth()->user() && auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('template/stackbros/assets/images/avatar/avt.jpg') }}"
                                                alt="avatar">
                                        </div>
                                        <h6 class="mb-0">{{ $user->name ?? $user->email }}</h6>
                                        <a href="mailto:{{ $user->email }}"
                                            class="text-reset text-primary-hover small">{{ $user->email }}</a>
                                        <hr>
                                    </div>

                                    <ul class="nav nav-pills-primary-soft flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.settings') }}"><i
                                                    class="bi bi-person fa-fw me-2"></i>My Profile</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link active" href="{{ route('account.booking.index') }}"><i
                                                    class="bi bi-ticket-perforated fa-fw me-2"></i>Đặt phòng của tôi</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('account.wishlist') }}"><i
                                                    class="bi bi-heart fa-fw me-2"></i>Danh sách yêu thích</a>
                                        </li>
                                        <li class="nav-item">
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit"
                                                    class="btn nav-link text-start text-danger bg-danger-soft-hover w-100">
                                                    <i class="fas fa-sign-out-alt fa-fw me-2"></i>Sign Out
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- Sidebar END -->

                <!-- Main content START -->
                <div class="col-lg-8 col-xl-9 ps-xl-5">

                    <div class="d-grid mb-0 d-lg-none w-100">
                        <button class="btn btn-primary mb-4" type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                            <i class="fas fa-sliders-h"></i> Menu
                        </button>
                    </div>

                    <div class="card border bg-transparent">
                        <div class="card-header bg-transparent border-bottom">
                            <h4 class="card-header-title">Đặt phòng của tôi</h4>
                        </div>

                        <div class="card-body p-0">
                            <ul class="nav nav-tabs nav-bottom-line nav-responsive nav-justified">
                                <li class="nav-item">
                                    <a class="nav-link mb-0 active" data-bs-toggle="tab" href="#tab-1"><i
                                            class="bi bi-briefcase-fill fa-fw me-1"></i>Đặt phòng sắp tới</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-0" data-bs-toggle="tab" href="#tab-2"><i
                                            class="bi bi-x-octagon fa-fw me-1"></i>Đặt phòng đã hủy</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mb-0" data-bs-toggle="tab" href="#tab-3"><i
                                            class="bi bi-patch-check fa-fw me-1"></i>Đặt phòng đã hoàn thành</a>
                                </li>
                            </ul>

                            <div class="tab-content p-2 p-sm-4" id="nav-tabContent">

                                @php
                                    $statusLabel = function ($t) {
                                        $map = [
                                            'dang_cho' => 'Đang chờ',
                                            'dang_cho_xac_nhan' => 'Đang chờ',
                                            'dang_su_dung' => 'Đang Sử Dụng',
                                            'da_xac_nhan' => 'Đã xác nhận',
                                            'da_huy' => 'Đã hủy',
                                            'hoan_thanh' => 'Hoàn thành',
                                        ];
                                        return $map[$t] ?? ucfirst(str_replace('_', ' ', $t));
                                    };
                                    $statusBadge = function ($t) {
                                        $map = [
                                            'dang_cho' => 'bg-warning',
                                            'dang_cho_xac_nhan' => 'bg-warning',
                                            'dang_su_dung' => 'bg-success',
                                            'da_xac_nhan' => 'bg-primary',
                                            'da_huy' => 'bg-danger',
                                            'hoan_thanh' => 'bg-info',
                                        ];
                                        return $map[$t] ?? 'bg-secondary';
                                    };

                                    // Format ngày tháng tiếng Việt
                                    $formatDateVi = function ($date, $format = 'D, d M Y') {
                                        if (!$date) {
                                            return '';
                                        }

                                        $carbon = \Carbon\Carbon::parse($date);

                                        $days = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
                                        $months = [
                                            '',
                                            'Tháng 1',
                                            'Tháng 2',
                                            'Tháng 3',
                                            'Tháng 4',
                                            'Tháng 5',
                                            'Tháng 6',
                                            'Tháng 7',
                                            'Tháng 8',
                                            'Tháng 9',
                                            'Tháng 10',
                                            'Tháng 11',
                                            'Tháng 12',
                                        ];

                                        if ($format === 'D, d M Y') {
                                            $dayName = $days[$carbon->dayOfWeek];
                                            $monthName = $months[$carbon->month];
                                            return $dayName .
                                                ', ' .
                                                $carbon->format('d') .
                                                ' ' .
                                                $monthName .
                                                ' ' .
                                                $carbon->format('Y');
                                        } elseif ($format === 'd M Y H:i') {
                                            $monthName = $months[$carbon->month];
                                            return $carbon->format('d') .
                                                ' ' .
                                                $monthName .
                                                ' ' .
                                                $carbon->format('Y H:i');
                                        }

                                        return $carbon->format($format);
                                    };
                                @endphp

                                {{-- Tab 1: Upcoming (dang_cho + da_xac_nhan) --}}
                                <div class="tab-pane fade show active" id="tab-1">
                                    <h6 class="mb-3">Đặt phòng sắp tới ({{ $upcoming->count() }})</h6>

                                    @forelse($upcoming as $b)
                                        @php
                                            $meta = is_array($b->snapshot_meta)
                                                ? $b->snapshot_meta
                                                : (json_decode($b->snapshot_meta, true) ?:
                                                []);
                                            $roomsCount =
                                                $meta['rooms_count'] ?? ($b->datPhongItems->sum('so_luong') ?: 1);
                                            $label = $statusLabel($b->trang_thai);
                                            $badge = $statusBadge($b->trang_thai);
                                        @endphp

                                        <div class="card border mb-4">
                                            <div
                                                class="card-header border-bottom d-md-flex justify-content-md-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <div class="icon-lg bg-light rounded-circle flex-shrink-0"><i
                                                            class="fa-solid fa-hotel"></i></div>
                                                    <div class="ms-2">
                                                        <h6 class="card-title mb-0">Đặt phòng: {{ $b->ma_tham_chieu }}</h6>
                                                        <ul class="nav nav-divider small">
                                                            <li class="nav-item">Phòng: {{ $roomsCount }}</li>
                                                            <li class="nav-item">Tổng tiền:
                                                                {{ number_format($b->snapshot_total ?? ($b->tong_tien ?? 0), 0, ',', '.') }}
                                                                VND</li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="mt-2 mt-md-0 text-end">
                                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                                    <div class="mt-2">
                                                        <a href="{{ route('account.booking.show', $b->id) }}"
                                                            class="btn btn-primary-soft mb-0">Quản lý đặt phòng</a>
                                                        
                                                        @if(in_array($b->trang_thai, ['dang_cho', 'da_xac_nhan']))
                                                            @php
                                                                // Calculate days until check-in using actual check-in time (14:00)
                                                                $checkInDateTime = \Carbon\Carbon::parse($b->ngay_nhan_phong)->setTime(14, 0, 0);
                                                                $now = \Carbon\Carbon::now();
                                                                $daysUntilCheckIn = (int) $now->diffInDays($checkInDateTime, false);
                                                                
                                                                // Determine deposit type based on deposit_amount
                                                                $totalAmount = $b->snapshot_total ?? ($b->tong_tien ?? 0);
                                                                $paidAmount = $b->deposit_amount ?? 0;
                                                                $depositType = 50; // default
                                                                if ($paidAmount > 0 && $totalAmount > 0) {
                                                                    $percentage = ($paidAmount / $totalAmount) * 100;
                                                                    $depositType = ($percentage >= 90) ? 100 : 50;
                                                                }
                                                                
                                                                // Calculate refund percentage based on policy
                                                                $refundPercentage = 0;
                                                                if ($depositType == 100) {
                                                                    if ($daysUntilCheckIn >= 7) $refundPercentage = 90;
                                                                    elseif ($daysUntilCheckIn >= 3) $refundPercentage = 60;
                                                                    elseif ($daysUntilCheckIn >= 1) $refundPercentage = 40;
                                                                    else $refundPercentage = 20;
                                                                } else {
                                                                    if ($daysUntilCheckIn >= 7) $refundPercentage = 100;
                                                                    elseif ($daysUntilCheckIn >= 3) $refundPercentage = 70;
                                                                    elseif ($daysUntilCheckIn >= 1) $refundPercentage = 30;
                                                                    else $refundPercentage = 0;
                                                                }
                                                                
                                                                $refundAmount = ($paidAmount * $refundPercentage) / 100;
                                                            @endphp
                                                            
                                                            <button type="button" 
                                                                    class="btn btn-danger-soft mb-0 ms-2" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#cancelModal{{ $b->id }}">
                                                                <i class="bi bi-x-circle me-1"></i>Hủy phòng
                                                            </button>
                                                            
                                                            <!-- Cancel Booking Modal -->
                                                            <div class="modal fade" id="cancelModal{{ $b->id }}" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header bg-danger bg-opacity-10 border-0">
                                                                            <h5 class="modal-title text-danger">
                                                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                                Xác nhận hủy đặt phòng
                                                                            </h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body p-4">
                                                                            <!-- Booking Info -->
                                                                            <div class="alert alert-light border mb-4">
                                                                                <div class="d-flex align-items-center mb-2">
                                                                                    <i class="bi bi-info-circle text-primary me-2 fs-5"></i>
                                                                                    <h6 class="mb-0">Thông tin đặt phòng</h6>
                                                                                </div>
                                                                                <div class="row small mt-3">
                                                                                    <div class="col-md-6 mb-2">
                                                                                        <strong>Mã đặt phòng:</strong> {{ $b->ma_tham_chieu }}
                                                                                    </div>
                                                                                    <div class="col-md-6 mb-2">
                                                                                        <strong>Ngày nhận phòng:</strong> {{ $checkInDateTime->format('d/m/Y') }}
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <strong>Số tiền đã thanh toán:</strong> 
                                                                                        <span class="text-danger">{{ number_format($paidAmount, 0, ',', '.') }} ₫</span>
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <strong>Thời gian còn lại:</strong> 
                                                                                        <span class="badge bg-info">{{ $daysUntilCheckIn }} ngày</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Refund Calculation -->
                                                                            <div class="card border-success mb-4">
                                                                                <div class="card-header bg-success bg-opacity-10 border-0">
                                                                                    <h6 class="mb-0 text-success">
                                                                                        <i class="bi bi-calculator me-2"></i>
                                                                                        Số tiền được hoàn trả
                                                                                    </h6>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                                                                        <div>
                                                                                            <small class="text-muted d-block">Hình thức thanh toán</small>
                                                                                            <span class="fw-semibold">{{ $depositType == 100 ? 'Thanh toán 100%' : 'Đặt cọc 50%' }}</span>
                                                                                        </div>
                                                                                        <div class="text-end">
                                                                                            <small class="text-muted d-block">Tỷ lệ hoàn tiền</small>
                                                                                            <span class="badge bg-primary fs-6">{{ $refundPercentage }}%</span>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                                        <span class="text-muted">Số tiền đã trả:</span>
                                                                                        <span class="fw-semibold">{{ number_format($paidAmount, 0, ',', '.') }} ₫</span>
                                                                                    </div>
                                                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                                                        <span class="text-muted">Tỷ lệ hoàn:</span>
                                                                                        <span class="fw-semibold">{{ $refundPercentage }}%</span>
                                                                                    </div>
                                                                                    <hr class="my-3">
                                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                                        <span class="fw-bold text-success fs-5">Bạn sẽ nhận lại:</span>
                                                                                        <span class="fw-bold text-success fs-4">{{ number_format($refundAmount, 0, ',', '.') }} ₫</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Refund Policy Table -->
                                                                            <div class="accordion" id="policyAccordion{{ $b->id }}">
                                                                                <div class="accordion-item border">
                                                                                    <h2 class="accordion-header">
                                                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#policyDetails{{ $b->id }}">
                                                                                            <i class="bi bi-file-text me-2"></i>
                                                                                            Chính sách hoàn tiền chi tiết
                                                                                        </button>
                                                                                    </h2>
                                                                                    <div id="policyDetails{{ $b->id }}" class="accordion-collapse collapse">
                                                                                        <div class="accordion-body">
                                                                                            <div class="row">
                                                                                                <div class="col-md-6">
                                                                                                    <h6 class="text-primary mb-3">Đặt cọc 50%</h6>
                                                                                                    <div class="table-responsive">
                                                                                                        <table class="table table-sm table-bordered">
                                                                                                            <thead class="table-light">
                                                                                                                <tr>
                                                                                                                    <th>Thời gian hủy</th>
                                                                                                                    <th>Hoàn lại</th>
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody class="small">
                                                                                                                <tr class="{{ $depositType == 50 && $daysUntilCheckIn >= 7 ? 'table-success' : '' }}">
                                                                                                                    <td>≥ 7 ngày trước</td>
                                                                                                                    <td><strong>100%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 50 && $daysUntilCheckIn >= 3 && $daysUntilCheckIn < 7 ? 'table-success' : '' }}">
                                                                                                                    <td>3-6 ngày trước</td>
                                                                                                                    <td><strong>70%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 50 && $daysUntilCheckIn >= 1 && $daysUntilCheckIn < 3 ? 'table-success' : '' }}">
                                                                                                                    <td>1-2 ngày trước</td>
                                                                                                                    <td><strong>30%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 50 && $daysUntilCheckIn < 1 ? 'table-danger' : '' }}">
                                                                                                                    <td>< 24 giờ</td>
                                                                                                                    <td><strong>0%</strong></td>
                                                                                                                </tr>
                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="col-md-6">
                                                                                                    <h6 class="text-success mb-3">Thanh toán 100%</h6>
                                                                                                    <div class="table-responsive">
                                                                                                        <table class="table table-sm table-bordered">
                                                                                                            <thead class="table-light">
                                                                                                                <tr>
                                                                                                                    <th>Thời gian hủy</th>
                                                                                                                    <th>Hoàn lại</th>
                                                                                                                </tr>
                                                                                                            </thead>
                                                                                                            <tbody class="small">
                                                                                                                <tr class="{{ $depositType == 100 && $daysUntilCheckIn >= 7 ? 'table-success' : '' }}">
                                                                                                                    <td>≥ 7 ngày trước</td>
                                                                                                                    <td><strong>90%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 100 && $daysUntilCheckIn >= 3 && $daysUntilCheckIn < 7 ? 'table-success' : '' }}">
                                                                                                                    <td>3-6 ngày trước</td>
                                                                                                                    <td><strong>60%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 100 && $daysUntilCheckIn >= 1 && $daysUntilCheckIn < 3 ? 'table-success' : '' }}">
                                                                                                                    <td>1-2 ngày trước</td>
                                                                                                                    <td><strong>40%</strong></td>
                                                                                                                </tr>
                                                                                                                <tr class="{{ $depositType == 100 && $daysUntilCheckIn < 1 ? 'table-warning' : '' }}">
                                                                                                                    <td>< 24 giờ</td>
                                                                                                                    <td><strong>20%</strong></td>
                                                                                                                </tr>
                                                                                                            </tbody>
                                                                                                        </table>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="alert alert-info alert-sm mt-3 mb-0">
                                                                                                <small>
                                                                                                    <i class="bi bi-info-circle me-1"></i>
                                                                                                    <strong>Lưu ý:</strong> Khách hàng thanh toán 100% được ưu đãi tỷ lệ hoàn tiền cao hơn khi hủy phòng.
                                                                                                </small>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Warning Message -->
                                                                            @if($refundPercentage == 0)
                                                                                <div class="alert alert-danger mt-4 mb-0">
                                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                                    <strong>Cảnh báo:</strong> Hủy phòng trong vòng 24 giờ trước check-in sẽ không được hoàn tiền!
                                                                                </div>
                                                                            @elseif($refundPercentage < 50)
                                                                                <div class="alert alert-warning mt-4 mb-0">
                                                                                    <i class="bi bi-exclamation-circle me-2"></i>
                                                                                    <strong>Lưu ý:</strong> Do hủy gần ngày nhận phòng, số tiền hoàn lại sẽ thấp hơn.
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                        <div class="modal-footer border-0 pt-0">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                                <i class="bi bi-arrow-left me-1"></i>Quay lại
                                                                            </button>
                                                                            <form action="{{ route('account.booking.cancel', $b->id) }}" method="POST" class="d-inline">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-danger">
                                                                                    <i class="bi bi-check-circle me-1"></i>Xác nhận hủy phòng
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif


                                                        @if($b->trang_thai === 'dang_cho')
                                                            @php
                                                                $pendingTransaction = $b->giaoDichs->where('trang_thai', 'dang_cho')->whereIn('nha_cung_cap', ['vnpay', 'momo'])->first();
                                                            @endphp
                                                            @if($pendingTransaction)
                                                                <a href="{{ route('account.booking.retry-payment', $b->id) }}" 
                                                                   class="btn btn-success-soft mb-0 ms-2">
                                                                    <i class="bi bi-credit-card me-1"></i>Tiếp tục thanh toán
                                                                </a>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-sm-6 col-md-4">
                                                        <span>Nhận phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_nhan_phong, 'D, d M Y') }}</h6>
                                                    </div>

                                                    <div class="col-sm-6 col-md-4">
                                                        <span>Trả phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_tra_phong, 'D, d M Y') }}</h6>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <span>Liên hệ</span>
                                                        <h6 class="mb-0">
                                                            {{ $b->contact_name ?? ($user->name ?? $user->email) }}</h6>
                                                    </div>
                                                </div>

                                                {{-- Rooms list  --}}
                                                <hr>
                                                <h6 class="mb-2">Phòng</h6>
                                                @if ($b->datPhongItems && $b->datPhongItems->count())
                                                    <ul class="list-unstyled mb-0">
                                                        @foreach ($b->datPhongItems as $it)
                                                            @php
                                                                $roomName =
                                                                    $it->phong && isset($it->phong->name)
                                                                        ? $it->phong->name
                                                                        : ($it->loai_phong &&
                                                                        isset($it->loai_phong->name)
                                                                            ? $it->loai_phong->name
                                                                            : 'Phòng ' . ($it->phong_id ?? 'N/A'));
                                                            @endphp
                                                            <li class="mb-1">
                                                                <i class="bi bi-door-open-fill me-2"></i>
                                                                {{ $roomName }}
                                                                @if (isset($it->so_dem))
                                                                    <small class="text-muted"> — {{ $it->so_dem }}
                                                                        đêm</small>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="text-muted small">Chi tiết phòng chưa được ghi nhận.</div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-info">Không tìm thấy đặt phòng sắp tới.</div>
                                    @endforelse
                                </div>

                                {{-- Tab 2: Cancelled (da_huy) --}}
                                <div class="tab-pane fade" id="tab-2">
                                    <h6 class="mb-3">Đặt phòng đã hủy ({{ $cancelled->count() }})</h6>

                                    @forelse($cancelled as $b)
                                        @php
                                            $meta = is_array($b->snapshot_meta)
                                                ? $b->snapshot_meta
                                                : (json_decode($b->snapshot_meta, true) ?: []);
                                            $label = $statusLabel($b->trang_thai);
                                            $badge = $statusBadge($b->trang_thai);
                                            
                                            // Get refund request
                                            $refundRequest = $b->refundRequests->first();
                                        @endphp
                                        <div class="card border mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0">{{ $b->ma_tham_chieu }}</h6>
                                                    <small class="text-muted">Đã hủy lúc
                                                        {{ $formatDateVi($b->updated_at, 'd M Y H:i') }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <span>Nhận phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_nhan_phong, 'D, d M Y') }}</h6>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <span>Trả phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_tra_phong, 'D, d M Y') }}</h6>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <span>Tổng tiền</span>
                                                        <h6 class="mb-0">
                                                            {{ number_format($b->snapshot_total ?? ($b->tong_tien ?? 0), 0, ',', '.') }}
                                                            VND</h6>
                                                    </div>
                                                </div>
                                                
                                                {{-- Refund Information --}}
                                                @if($refundRequest)
                                                    <hr class="my-3">
                                                    
                                                    {{-- Refund Summary Card --}}
                                                    <div class="card border-0 bg-light-success mb-3">
                                                        <div class="card-body p-3">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-8">
                                                                    <div class="d-flex align-items-start">
                                                                        <div class="icon-lg bg-success bg-opacity-10 rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center">
                                                                            <i class="bi bi-cash-coin text-success fs-4"></i>
                                                                        </div>
                                                                        <div class="ms-3">
                                                                            <h6 class="mb-1">Thông tin hoàn tiền</h6>
                                                                            <div class="d-flex flex-wrap gap-3 mt-2">
                                                                                <div>
                                                                                    <small class="text-muted d-block">Số tiền hoàn trả</small>
                                                                                    <h5 class="mb-0 text-success fw-bold">
                                                                                        {{ number_format($refundRequest->amount, 0, ',', '.') }} ₫
                                                                                    </h5>
                                                                                </div>
                                                                                <div class="vr d-none d-md-block"></div>
                                                                                <div>
                                                                                    <small class="text-muted d-block">Tỷ lệ hoàn tiền</small>
                                                                                    <h5 class="mb-0 text-primary fw-bold">{{ $refundRequest->percentage }}%</h5>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                                                    @php
                                                                        $statusClass = match($refundRequest->status) {
                                                                            'pending' => 'bg-warning text-dark',
                                                                            'approved' => 'bg-info text-white',
                                                                            'completed' => 'bg-success text-white',
                                                                            'rejected' => 'bg-danger text-white',
                                                                            default => 'bg-secondary'
                                                                        };
                                                                        $statusText = match($refundRequest->status) {
                                                                            'pending' => 'Chờ xử lý',
                                                                            'approved' => 'Đã duyệt',
                                                                            'completed' => 'Đã hoàn tiền',
                                                                            'rejected' => 'Từ chối',
                                                                            default => 'N/A'
                                                                        };
                                                                        $statusIcon = match($refundRequest->status) {
                                                                            'pending' => 'bi-clock-history',
                                                                            'approved' => 'bi-check-circle',
                                                                            'completed' => 'bi-patch-check-fill',
                                                                            'rejected' => 'bi-x-circle-fill',
                                                                            default => 'bi-info-circle'
                                                                        };
                                                                    @endphp
                                                                    <span class="badge {{ $statusClass }} px-3 py-2 fs-6">
                                                                        <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusText }}
                                                                    </span>
                                                                    <div class="mt-2">
                                                                        <button class="btn btn-sm btn-primary" type="button" 
                                                                                data-bs-toggle="collapse" 
                                                                                data-bs-target="#refundDetails{{ $b->id }}" 
                                                                                aria-expanded="false">
                                                                            <i class="bi bi-chevron-down"></i> Xem chi tiết
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Expandable Detailed Breakdown --}}
                                                    <div class="collapse" id="refundDetails{{ $b->id }}">
                                                        <div class="card border mb-3">
                                                            <div class="card-header bg-white">
                                                                <h6 class="mb-0">
                                                                    <i class="bi bi-receipt text-primary me-2"></i>
                                                                    Chi tiết thanh toán & hoàn tiền
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                {{-- Financial Breakdown --}}
                                                                <div class="row mb-4">
                                                                    <div class="col-md-6">
                                                                        <div class="bg-light rounded p-3 h-100">
                                                                            <h6 class="mb-3 text-primary">
                                                                                <i class="bi bi-wallet2 me-2"></i>Thông tin thanh toán gốc
                                                                            </h6>
                                                                            <ul class="list-unstyled mb-0">
                                                                                <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                                    <span class="text-muted">
                                                                                        <i class="bi bi-receipt text-muted me-2"></i>Tổng giá trị đặt phòng:
                                                                                    </span>
                                                                                    <strong>{{ number_format($b->snapshot_total ?? ($b->tong_tien ?? 0), 0, ',', '.') }} ₫</strong>
                                                                                </li>
                                                                                <li class="d-flex justify-content-between mb-2">
                                                                                    <span class="text-muted">
                                                                                        <i class="bi bi-credit-card text-muted me-2"></i>Số tiền đã thanh toán:
                                                                                    </span>
                                                                                    <strong class="text-danger">{{ number_format($b->deposit_amount ?? 0, 0, ',', '.') }} ₫</strong>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6 mt-3 mt-md-0">
                                                                        <div class="bg-success bg-opacity-10 rounded p-3 h-100">
                                                                            <h6 class="mb-3 text-success">
                                                                                <i class="bi bi-arrow-return-left me-2"></i>Chi tiết hoàn tiền
                                                                            </h6>
                                                                            <ul class="list-unstyled mb-0">
                                                                                <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                                    <span class="text-muted">
                                                                                        <i class="bi bi-percent text-muted me-2"></i>Tỷ lệ được hoàn:
                                                                                    </span>
                                                                                    <strong class="text-primary">{{ $refundRequest->percentage }}%</strong>
                                                                                </li>
                                                                                <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                                                                    <span class="text-muted">
                                                                                        <i class="bi bi-calculator text-muted me-2"></i>Cách tính:
                                                                                    </span>
                                                                                    <span class="small">{{ number_format($b->deposit_amount ?? 0, 0, ',', '.') }} × {{ $refundRequest->percentage }}%</span>
                                                                                </li>
                                                                                <li class="d-flex justify-content-between">
                                                                                    <span class="fw-semibold text-success">
                                                                                        <i class="bi bi-cash-coin me-2"></i>Số tiền nhận lại:
                                                                                    </span>
                                                                                    <strong class="text-success fs-5">{{ number_format($refundRequest->amount, 0, ',', '.') }} ₫</strong>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Timeline --}}
                                                                <div class="border-top pt-3">
                                                                    <h6 class="mb-3">
                                                                        <i class="bi bi-clock-history text-primary me-2"></i>
                                                                        Tiến trình xử lý
                                                                    </h6>
                                                                    <div class="timeline-refund">
                                                                        <div class="timeline-item {{ $refundRequest->status !== 'rejected' ? 'completed' : 'rejected' }}">
                                                                            <div class="timeline-marker">
                                                                                <i class="bi bi-check-circle-fill"></i>
                                                                            </div>
                                                                            <div class="timeline-content">
                                                                                <h6 class="mb-1">Yêu cầu hoàn tiền</h6>
                                                                                <small class="text-muted">
                                                                                    <i class="bi bi-calendar3 me-1"></i>
                                                                                    {{ $refundRequest->requested_at->format('d/m/Y H:i') }}
                                                                                </small>
                                                                                <p class="mb-0 mt-1 small">Yêu cầu hoàn tiền đã được tạo tự động khi đặt phòng bị hủy</p>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        @if($refundRequest->status === 'rejected')
                                                                            <div class="timeline-item rejected">
                                                                                <div class="timeline-marker">
                                                                                    <i class="bi bi-x-circle-fill"></i>
                                                                                </div>
                                                                                <div class="timeline-content">
                                                                                    <h6 class="mb-1 text-danger">Yêu cầu bị từ chối</h6>
                                                                                    @if($refundRequest->processed_at)
                                                                                        <small class="text-muted">
                                                                                            <i class="bi bi-calendar3 me-1"></i>
                                                                                            {{ $refundRequest->processed_at->format('d/m/Y H:i') }}
                                                                                        </small>
                                                                                    @endif
                                                                                    @if($refundRequest->admin_note)
                                                                                        <div class="alert alert-danger alert-sm mt-2 mb-0 p-2">
                                                                                            <small>
                                                                                                <i class="bi bi-chat-left-text me-1"></i>
                                                                                                <strong>Lý do:</strong> {{ $refundRequest->admin_note }}
                                                                                            </small>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="timeline-item {{ in_array($refundRequest->status, ['approved', 'completed']) ? 'completed' : '' }}">
                                                                                <div class="timeline-marker">
                                                                                    <i class="bi {{ in_array($refundRequest->status, ['approved', 'completed']) ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                                                </div>
                                                                                <div class="timeline-content">
                                                                                    <h6 class="mb-1">Phê duyệt</h6>
                                                                                    @if(in_array($refundRequest->status, ['approved', 'completed']))
                                                                                        <small class="text-muted">
                                                                                            <i class="bi bi-calendar3 me-1"></i>
                                                                                            {{ $refundRequest->processed_at ? $refundRequest->processed_at->format('d/m/Y H:i') : 'Đã phê duyệt' }}
                                                                                        </small>
                                                                                        <p class="mb-0 mt-1 small text-success">
                                                                                            <i class="bi bi-check-circle me-1"></i>
                                                                                            Yêu cầu đã được phê duyệt bởi quản trị viên
                                                                                        </p>
                                                                                    @else
                                                                                        <p class="mb-0 mt-1 small text-muted">Đang chờ quản trị viên xem xét và phê duyệt</p>
                                                                                    @endif
                                                                                    @if($refundRequest->admin_note && in_array($refundRequest->status, ['approved', 'completed']))
                                                                                        <div class="alert alert-info alert-sm mt-2 mb-0 p-2">
                                                                                            <small>
                                                                                                <i class="bi bi-chat-left-text me-1"></i>
                                                                                                <strong>Ghi chú:</strong> {{ $refundRequest->admin_note }}
                                                                                            </small>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            <div class="timeline-item {{ $refundRequest->status === 'completed' ? 'completed' : '' }}">
                                                                                <div class="timeline-marker">
                                                                                    <i class="bi {{ $refundRequest->status === 'completed' ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                                                                                </div>
                                                                                <div class="timeline-content">
                                                                                    <h6 class="mb-1">Hoàn tiền</h6>
                                                                                    @if($refundRequest->status === 'completed')
                                                                                        @if($refundRequest->processed_at)
                                                                                            <small class="text-muted">
                                                                                                <i class="bi bi-calendar3 me-1"></i>
                                                                                                {{ $refundRequest->processed_at->format('d/m/Y H:i') }}
                                                                                            </small>
                                                                                        @endif
                                                                                        <div class="alert alert-success alert-sm mt-2 mb-0 p-2">
                                                                                            <small>
                                                                                                <i class="bi bi-patch-check-fill me-1"></i>
                                                                                                <strong>Hoàn tất!</strong> Số tiền <strong>{{ number_format($refundRequest->amount, 0, ',', '.') }} ₫</strong> đã được hoàn vào tài khoản của bạn.
                                                                                            </small>
                                                                                        </div>
                                                                                        
                                                                                        {{-- Proof Image Display --}}
                                                                                        @if($refundRequest->proof_image_path)
                                                                                            <div class="mt-3 border rounded p-3 bg-white">
                                                                                                <strong class="d-block mb-2 text-primary">
                                                                                                    <i class="bi bi-image me-1"></i> Ảnh chứng minh hoàn tiền:
                                                                                                </strong>
                                                                                                <div class="text-center">
                                                                                                    <a href="{{ $refundRequest->proof_image_url }}" target="_blank">
                                                                                                        <img src="{{ $refundRequest->proof_image_url }}" 
                                                                                                             alt="Proof of refund" 
                                                                                                             class="img-thumbnail" 
                                                                                                             style="max-width: 100%; max-height: 300px; cursor: pointer;">
                                                                                                    </a>
                                                                                                </div>
                                                                                                <div class="text-center mt-2">
                                                                                                    <a href="{{ $refundRequest->proof_image_url }}" download class="btn btn-sm btn-outline-primary">
                                                                                                        <i class="bi bi-download me-1"></i> Tải xuống ảnh
                                                                                                    </a>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endif
                                                                                    @else
                                                                                        <p class="mb-0 mt-1 small text-muted">
                                                                                            @if($refundRequest->status === 'approved')
                                                                                                Đang tiến hành chuyển tiền. Thời gian dự kiến: 1-2 ngày làm việc
                                                                                            @else
                                                                                                Chờ phê duyệt trước khi hoàn tiền
                                                                                            @endif
                                                                                        </p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                {{-- Help Information --}}
                                                              
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <hr class="my-3">
                                                    <div class="alert alert-warning alert-sm mb-0 p-2">
                                                        <small>
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Không có yêu cầu hoàn tiền cho đặt phòng này.
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-info">Không có đặt phòng đã hủy.</div>
                                    @endforelse
                                </div>

                                {{-- Tab 3: Completed (hoan_thanh) --}}
                                <div class="tab-pane fade" id="tab-3">
                                    <h6 class="mb-3">Đặt phòng đã hoàn thành ({{ $completed->count() }})</h6>

                                    @forelse($completed as $b)
                                        @php
                                            $label = $statusLabel($b->trang_thai);
                                            $badge = $statusBadge($b->trang_thai);

                                            $rooms = collect($b->datPhongItems)->pluck('phong')->filter();
                                            if ($rooms->isEmpty()) {
                                                $roomItems = collect($b->hoaDons ?? [])
                                                    ->flatMap(function ($hd) {
                                                        return collect($hd->hoaDonItems ?? [])->filter(function ($it) {
                                                            return ($it->type ?? '') === 'room_booking';
                                                        });
                                                    })
                                                    ->map(function ($it) {
                                                        if (!empty($it->phong)) {
                                                            return $it->phong;
                                                        }

                                                        $loai = $it->loaiPhong ?? null;

                                                        return (object) [
                                                            'ten_phong' =>
                                                                $it->phong?->ten_phong ??
                                                                ($it->name ?? 'Phòng chưa gán'),
                                                            'loaiPhong' =>
                                                                $loai ?:
                                                                (object) [
                                                                    'ten_loai' => $it->loaiPhong?->ten_loai ?? null,
                                                                ],
                                                        ];
                                                    })
                                                    ->filter();
                                                $rooms = $roomItems;
                                            }
                                        @endphp

                                        <div class="card border mb-3">
                                            <div
                                                class="card-header d-flex justify-content-between align-items-center bg-light">
                                                <div>
                                                    <h6 class="mb-0">{{ $b->ma_tham_chieu }}</h6>
                                                    <small class="text-muted">Hoàn thành lúc
                                                        {{ $formatDateVi($b->updated_at, 'd M Y H:i') }}</small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                                </div>
                                            </div>

                                            <div class="card-body">
                                                <div class="row mb-2">
                                                    <div class="col-md-4">
                                                        <span>Nhận phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_nhan_phong, 'D, d M Y') }}</h6>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <span>Trả phòng</span>
                                                        <h6 class="mb-0">
                                                            {{ $formatDateVi($b->ngay_tra_phong, 'D, d M Y') }}</h6>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <span>Tổng tiền</span>
                                                        <h6 class="mb-0">
                                                            {{ number_format($b->snapshot_total ?? ($b->tong_tien ?? 0), 0, ',', '.') }}
                                                            VND</h6>
                                                    </div>
                                                </div>

                                                {{-- Danh sách phòng đã đặt --}}
                                                <div class="border-top pt-2 mt-2">
                                                    <span class="fw-semibold">Phòng đã đặt:</span>
                                                    @if ($rooms->count() > 0)
                                                        <ul class="mt-2 mb-0">
                                                            @foreach ($rooms as $p)
                                                                <li>
                                                                    {{ $p->ten_phong ?? ($p->name ?? 'Phòng chưa gán') }}
                                                                    @if (!empty($p->loaiPhong) && ($p->loaiPhong->ten_loai ?? null))
                                                                        - {{ $p->loaiPhong->ten_loai }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="text-muted mt-2 mb-0">Chưa có phòng nào được gán.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-info">Không có đặt phòng đã hoàn thành.</div>
                                    @endforelse
                                </div>

                            </div>

                        </div>
                    </div>

                </div>
                <!-- Main content END -->
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    /* Refund Information Styling */
    .bg-light-success {
        background-color: #f0fdf4 !important;
    }

    /* Timeline Styles */
    .timeline-refund {
        position: relative;
        padding-left: 0;
        margin: 0;
    }

    .timeline-item {
        position: relative;
        padding-left: 45px;
        padding-bottom: 30px;
        margin-bottom: 0;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 30px;
        bottom: -10px;
        width: 2px;
        background-color: #e5e7eb;
    }

    .timeline-item.completed:not(:last-child)::before {
        background-color: #10b981;
    }

    .timeline-item.rejected:not(:last-child)::before {
        background-color: #ef4444;
    }

    .timeline-marker {
        position: absolute;
        left: 0;
        top: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background-color: #f3f4f6;
        border: 3px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .timeline-item.completed .timeline-marker {
        background-color: #10b981;
        color: #fff;
    }

    .timeline-item.rejected .timeline-marker {
        background-color: #ef4444;
        color: #fff;
    }

    .timeline-marker i {
        font-size: 16px;
        color: #9ca3af;
    }

    .timeline-item.completed .timeline-marker i,
    .timeline-item.rejected .timeline-marker i {
        color: #fff;
    }

    .timeline-content {
        flex: 1;
    }

    .timeline-content h6 {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #1f2937;
    }

    .timeline-content small {
        color: #6b7280;
    }

    .timeline-content p {
        color: #6b7280;
        font-size: 0.875rem;
    }

    /* Icon sizing */
    .icon-lg {
        width: 48px;
        height: 48px;
    }

    /* Accordion FAQ styling */
    .accordion-button:not(.collapsed) {
        color: #0d6efd;
        background-color: transparent;
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }

    /* Alert sizing */
    .alert-sm {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .timeline-item {
            padding-left: 35px;
        }

        .timeline-item:not(:last-child)::before {
            left: 13px;
        }

        .timeline-marker {
            width: 28px;
            height: 28px;
        }

        .timeline-marker i {
            font-size: 14px;
        }
    }
</style>
@endpush
