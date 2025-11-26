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
                                                : (json_decode($b->snapshot_meta, true) ?:
                                                []);
                                            $label = $statusLabel($b->trang_thai);
                                            $badge = $statusBadge($b->trang_thai);
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
                                                    @if($b->trang_thai == 'hoan_thanh')
    <a href="{{ route('account.danhgia.create', $b->id) }}"
       class="btn btn-warning w-100 mt-2">
        Đánh giá phòng
    </a>
@endif

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
  
</a>
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
