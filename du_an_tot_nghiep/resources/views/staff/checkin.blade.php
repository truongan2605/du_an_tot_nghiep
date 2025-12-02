@extends('layouts.admin')

@section('title', 'Check-in Bookings')

@section('content')
<div class="container-fluid px-2 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center">
            <div class="bg-primary rounded-circle p-2 me-2 shadow-sm">
                <i class="bi bi-calendar-check text-white fs-5"></i>
            </div>
            <div>
                <h5 class="h6 mb-0 fw-bold text-dark">Booking Check-in</h5>
                <small class="text-muted">Sẵn sàng xử lý</small>
            </div>
        </div>
        <div class="d-flex gap-1">
            <input type="text" class="form-control form-control-sm shadow-sm" id="searchInput" placeholder="Tìm mã TC..." style="width: 140px; min-width: 120px;">
            <select class="form-select form-select-sm shadow-sm" id="statusFilter" style="width: 110px; min-width: 100px;">
                <option value="">Tất cả</option>
                <option value="hom_nay">Hôm nay</option>
                <option value="sap-toi">Sắp tới</option>
                <option value="qua-han">Quá hạn</option>
            </select>
        </div>
    </div>

   
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow mb-3 rounded-3" role="alert" style="font-size: 0.875rem; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>
                {{ session('error') }}
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow mb-3 rounded-3" role="alert"
                style="font-size: 0.875rem; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 text-success"></i>
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow mb-3 rounded-3" role="alert"
                style="font-size: 0.875rem; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @endif

        <!-- Compact Stats -->
        <div class="row mb-3 g-2">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow h-100 overflow-hidden position-relative"
                    style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: white;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50">Hôm nay</small>
                                <div class="fw-bold fs-4">{{ $bookings->where('checkin_status', 'Hôm nay')->count() }}</div>
                            </div>
                            <i class="bi bi-calendar-check fs-2 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow h-100 overflow-hidden position-relative"
                    style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: #000;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">Sắp tới</small>
                                <div class="fw-bold fs-4">{{ $bookings->where('checkin_status', 'Sắp tới')->count() }}</div>
                            </div>
                            <i class="bi bi-clock-history fs-2 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow h-100 overflow-hidden position-relative"
                    style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50">Quá hạn</small>
                                <div class="fw-bold fs-4">
                                    {{ $bookings->where('checkin_status', '!=', 'Hôm nay')->where('checkin_status', '!=', 'Sắp tới')->count() }}
                                </div>
                            </div>
                            <i class="bi bi-exclamation-circle fs-2 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow h-100 overflow-hidden position-relative"
                    style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50">Tổng</small>
                                <div class="fw-bold fs-4">{{ $bookings->count() }}</div>
                            </div>
                            <i class="bi bi-people fs-2 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-lg border-0 rounded-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light border-bottom border-primary border-2">
                            <tr>
                                <th class="ps-2 py-2 fw-semibold text-dark" style="width: 50px; font-size: 0.85rem;">ID</th>
                                <th class="py-2 fw-semibold text-dark" style="min-width: 90px; font-size: 0.85rem;">Mã TC
                                </th>
                                <th class="py-2 fw-semibold text-dark" style="min-width: 120px; font-size: 0.85rem;">Khách
                                </th>
                                <th class="py-2 fw-semibold text-dark" style="min-width: 80px; font-size: 0.85rem;">Ngày
                                </th>
                                <th class="py-2 fw-semibold text-center text-dark"
                                    style="width: 100px; font-size: 0.85rem;">Trạng thái</th>
                                <th class="py-2 fw-semibold text-end text-dark"
                                    style="min-width: 80px; font-size: 0.85rem;">Tổng</th>
                                <th class="py-2 fw-semibold text-end text-dark d-none d-sm-table-cell"
                                    style="min-width: 70px; font-size: 0.85rem;">Đã TT</th>
                                <th class="py-2 fw-semibold text-end text-dark d-none d-md-table-cell"
                                    style="min-width: 70px; font-size: 0.85rem;">Còn</th>
                                <th class="py-2 fw-semibold text-center text-dark d-none d-sm-table-cell"
                                    style="width: 80px; font-size: 0.85rem;">TT</th>
                                <th class="pe-2 py-2 fw-semibold text-center text-dark"
                                    style="width: 140px; font-size: 0.85rem;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            @forelse ($bookings as $booking)
                                @php
                                    $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
                                    $isTodayOrPast = $checkinDate->isToday() || $checkinDate->isPast();

                                    $meta = is_array($booking->snapshot_meta)
                                        ? $booking->snapshot_meta
                                        : json_decode($booking->snapshot_meta, true) ?? [];
                                    $hasCCCD =
                                        !empty($meta['checkin_cccd_front']) ||
                                        !empty($meta['checkin_cccd_back']) ||
                                        !empty($meta['checkin_cccd']); // Backward compatibility

                                    $hasDonDep = collect($booking->datPhongItems ?? [])
                                        ->pluck('phong')
                                        ->filter()
                                        ->pluck('don_dep')
                                        ->contains(true);

                                    $canCheckin = $booking->remaining <= 0 && !$hasDonDep;
                                @endphp

                                <tr class="border-bottom">
                                    <td class="ps-2 small text-secondary">{{ $booking->id }}</td>
                                    <td class="fw-semibold small text-primary">{{ $booking->ma_tham_chieu }}</td>
                                    <td class="small text-truncate" style="max-width: 100px;"
                                        title="{{ $booking->nguoiDung->name ?? 'Ẩn danh' }}">
                                        {{ Str::limit($booking->nguoiDung->name ?? 'Ẩn danh', 12) }}
                                    </td>
                                    <td class="small">{{ $checkinDate->format('d/m') }}</td>
                                    <td class="text-center">
                                        @if ($booking->checkin_status === 'Hôm nay')
                                            <span
                                                class="badge bg-success rounded-pill px-2 py-1 small fw-semibold shadow-sm">
                                                <i class="bi bi-check-lg me-1"></i>Hôm nay
                                            </span>
                                        @elseif ($booking->checkin_status === 'Sắp tới')
                                            <span
                                                class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-semibold shadow-sm">
                                                <i class="bi bi-clock me-1"></i>Sắp {{ $booking->checkin_date_diff }}
                                            </span>
                                        @else
                                            <span
                                                class="badge bg-danger rounded-pill px-2 py-1 small fw-semibold shadow-sm">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Quá
                                                {{ $booking->checkin_date_diff }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold small text-dark">
                                        {{ number_format($booking->tong_tien) }}đ</td>
                                    <td class="text-end text-success small fw-medium d-none d-sm-table-cell">
                                        {{ number_format($booking->paid) }}đ</td>
                                    <td class="text-end fw-semibold small d-none d-md-table-cell">
                                        @if ($booking->remaining < 0)
                                            <span class="badge bg-success rounded-pill px-2 py-1 small fw-semibold shadow-sm" 
                                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                                  title="Đã thanh toán đủ (Booking đã downgrade, khách nhận voucher {{ number_format(abs($booking->remaining)) }}đ)">
                                                <i class="bi bi-check-circle me-1"></i>Đã TT đủ
                                            </span>
                                        @elseif ($booking->remaining > 0)
                                            <span class="text-danger">{{ number_format($booking->remaining) }}đ</span>
                                        @else
                                            <span class="text-success">0đ</span>
                                        @endif
                                    </td>

                                    <td class="text-center d-none d-sm-table-cell">
                                        @php
                                            $hasDowngrade = \App\Models\RoomChange::where('dat_phong_id', $booking->id)
                                                ->where('status', 'completed')
                                                ->whereRaw('price_difference < 0')
                                                ->exists();
                                        @endphp
                                        
                                        @if ($hasDowngrade)
                                            <span class="badge bg-info rounded-pill px-2 py-1 small fw-semibold shadow-sm"
                                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                                  title="Khách hàng đã đổi xuống phòng rẻ hơn và nhận voucher hoàn tiền">
                                                <i class="bi bi-arrow-down-circle me-1"></i>Đã downgrade
                                            </span>
                                        @elseif ($booking->trang_thai === 'da_xac_nhan')
                                            <span
                                                class="badge bg-info rounded-pill px-2 py-1 small fw-semibold shadow-sm">Xác
                                                nhận</span>
                                        @elseif ($booking->trang_thai === 'da_gan_phong')
                                            <span
                                                class="badge bg-primary rounded-pill px-2 py-1 small fw-semibold shadow-sm">Gán
                                                phòng</span>
                                        @endif
                                    </td>

                                    <td class="pe-2 text-center">
                                        {{-- Nếu còn nợ --}}
                                        @if ($booking->remaining > 0)
                                            @if ($isTodayOrPast)
                                                <div class="d-flex flex-column gap-1 align-items-center">
                                                    @if ($hasDonDep)
                                                        <div class="alert alert-warning mb-0 small">
                                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                            Có phòng đang dọn dẹp ở đơn này
                                                        </div>
                                                    @else
                                                        @if (!$hasCCCD)
                                                            <button type="button"
                                                                class="btn btn-info btn-sm px-2 py-1 rounded-pill fw-semibold shadow-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#cccdModal{{ $booking->id }}"
                                                                title="Nhập CCCD/CMND">
                                                                <i class="bi bi-card-text me-1"></i>Nhập CCCD
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-pill fw-semibold shadow-sm"
                                                                disabled data-bs-toggle="tooltip" data-bs-placement="top"
                                                                title="Vui lòng nhập CCCD/CMND trước khi thanh toán">
                                                                <i class="bi bi-lock me-1"></i>Chưa thể thanh toán
                                                            </button>
                                                        @else
                                                            <span
                                                                class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-1 small">
                                                                <i class="bi bi-check-circle me-1"></i>Đã có CCCD
                                                            </span>
                                                            <form action="{{ route('payment.remaining', $booking->id) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden"
                                                                    style="width: 120px;">
                                                                    <select name="nha_cung_cap"
                                                                        class="form-select form-select-sm border-0 px-2"
                                                                        required>
                                                                        <option value="">Chọn</option>
                                                                       <option value="tien_mat">Tiền mặt</option>
                                                                        <option value="vnpay">VNPAY</option>
                                                                        <option value="momo">MoMo</option>
                                                                    </select>
                                                                    <button type="submit"
                                                                        class="btn btn-warning border-0 px-2"
                                                                        title="Thanh toán phần còn lại">
                                                                        <i class="bi bi-arrow-right"></i>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    @endif
                                                </div>
                                            @else
                                                <button
                                                    class="btn btn-outline-secondary px-3 py-1 rounded-pill fw-semibold shadow-sm"
                                                    disabled data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Chưa đến ngày nhận phòng ({{ $checkinDate->format('d/m/Y') }})">
                                                    <i class="bi bi-clock me-1"></i>Chờ
                                                </button>
                                            @endif

                                            {{-- Nếu đã trả hết --}}
                                        @else
                                            @if ($isTodayOrPast)
                                                @if ($canCheckin)
                                                    <button type="button"
                                                        class="btn btn-success px-3 py-1 rounded-pill fw-semibold shadow-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#checkinModal{{ $booking->id }}"
                                                        data-booking-id="{{ $booking->id }}">
                                                        <i class="bi bi-check-circle me-1"></i>Check-in
                                                    </button>
                                                @else
                                                    <button
                                                        class="btn btn-outline-secondary px-3 py-1 rounded-pill fw-semibold shadow-sm"
                                                        disabled data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="{{ $hasDonDep ? 'Không thể check-in — một hoặc nhiều phòng đang dọn dẹp' : 'Không thể check-in' }}">
                                                        <i class="bi bi-slash-circle me-1"></i>Không thể check-in
                                                    </button>
                                                    @if ($hasDonDep)
                                                        <div class="mt-1"><span
                                                                class="badge bg-warning text-dark small">Phòng đang dọn
                                                                dẹp</span></div>
                                                    @endif
                                                @endif
                                            @else
                                                <button
                                                    class="btn btn-outline-secondary px-3 py-1 rounded-pill fw-semibold shadow-sm"
                                                    disabled data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="Chưa đến ngày nhận phòng ({{ $checkinDate->format('d/m/Y') }})">
                                                    <i class="bi bi-clock me-1"></i>Chờ
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 mb-3 d-block text-muted"></i>
                                        <h6 class="mb-2 fw-semibold">Không có booking</h6>
                                        <small class="text-muted">Thay đổi bộ lọc để xem thêm dữ liệu.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>
            </div>
            @if (method_exists($bookings, 'links'))
                <div class="card-footer bg-light border-0 py-2">
                    <div class="d-flex justify-content-between align-items-center small text-muted flex-wrap gap-1">
                        <span>{{ $bookings->firstItem() ?? 0 }} - {{ $bookings->lastItem() ?? 0 }} /
                            {{ $bookings->total() }} kết quả</span>
                        {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Nhập CCCD (cho booking còn nợ) --}}
    @foreach ($bookings as $booking)
        @php
            $meta = is_array($booking->snapshot_meta)
                ? $booking->snapshot_meta
                : json_decode($booking->snapshot_meta, true) ?? [];
            $hasCCCD =
                !empty($meta['checkin_cccd_front']) ||
                !empty($meta['checkin_cccd_back']) ||
                !empty($meta['checkin_cccd']); // Backward compatibility
        @endphp
        @if (
            $booking->remaining > 0 &&
                (\Carbon\Carbon::parse($booking->ngay_nhan_phong)->isToday() ||
                    \Carbon\Carbon::parse($booking->ngay_nhan_phong)->isPast()))
            <div class="modal fade" id="cccdModal{{ $booking->id }}" tabindex="-1"
                aria-labelledby="cccdModalLabel{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="cccdModalLabel{{ $booking->id }}">
                                <i class="bi bi-card-text me-2"></i>Nhập CCCD/CMND
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Đóng"></button>
                        </div>
                        <form action="{{ route('staff.saveCCCD') }}" method="POST" id="cccdForm{{ $booking->id }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Thông tin Booking</label>
                                    <div class="card bg-light p-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Mã booking:</small>
                                                <strong class="text-primary">{{ $booking->ma_tham_chieu }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Khách hàng:</small>
                                                <strong>{{ $booking->nguoiDung?->name ?? 'N/A' }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Còn nợ:</small>
                                                <strong
                                                    class="text-danger">{{ number_format($booking->remaining) }}đ</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Tổng tiền:</small>
                                                <strong
                                                    class="text-success">{{ number_format($booking->tong_tien) }}đ</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $computedAdults = $meta['computed_adults'] ?? ($meta['adults'] ?? 1);
                                    $children = $meta['children'] ?? 0;
                                    $cccdList = $meta['checkin_cccd_list'] ?? [];
                                    $existingCCCDCount =
                                        !empty($cccdList) && is_array($cccdList) ? count($cccdList) : 0;

                                    // Hiển thị CCCD đã có (nếu có)
                                    $hasCCCDList = $existingCCCDCount > 0;
                                @endphp

                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Thông tin:</strong> Số người lớn trong phòng:
                                    <strong>{{ $computedAdults }}</strong>
                                    @if ($children > 0)
                                        <br><small class="text-muted">(Trẻ em: {{ $children }} người - không cần
                                            CCCD)</small>
                                    @endif
                                </div>

                                @if ($hasCCCDList)
                                    <div class="alert alert-success mb-3">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>Đã có ảnh CCCD ({{ $existingCCCDCount }} người):</strong>
                                        <div class="row g-2 mt-2">
                                            @foreach ($cccdList as $index => $cccdItem)
                                                <div class="col-12 col-md-6 mb-2">
                                                    <small class="text-muted d-block mb-1">Người
                                                        {{ $index + 1 }}:</small>
                                                    <div class="row g-2">
                                                        @if (!empty($cccdItem['front']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['front']))
                                                            <div class="col-6">
                                                                <small class="text-muted d-block mb-1">Mặt trước:</small>
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['front']) }}"
                                                                    alt="Mặt trước CCCD người {{ $index + 1 }}"
                                                                    class="img-thumbnail w-100"
                                                                    style="max-height: 150px; cursor: pointer; object-fit: contain;"
                                                                    onclick="window.open(this.src, '_blank')">
                                                            </div>
                                                        @endif
                                                        @if (!empty($cccdItem['back']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['back']))
                                                            <div class="col-6">
                                                                <small class="text-muted d-block mb-1">Mặt sau:</small>
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['back']) }}"
                                                                    alt="Mặt sau CCCD người {{ $index + 1 }}"
                                                                    class="img-thumbnail w-100"
                                                                    style="max-height: 150px; cursor: pointer; object-fit: contain;"
                                                                    onclick="window.open(this.src, '_blank')">
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <br><small>Bạn có thể cập nhật ảnh CCCD mới nếu cần.</small>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        Số lượng CCCD cần nhập <span class="text-danger">*</span>
                                    </label>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <input type="number" class="form-control" id="cccdCount{{ $booking->id }}"
                                                name="cccd_count" min="1" max="20"
                                                value="{{ $existingCCCDCount > 0 ? $existingCCCDCount : $computedAdults }}"
                                                required>
                                            <small class="form-text text-muted">
                                                Nhập số lượng CCCD cần nhập
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-list-ul me-1"></i>Chọn nhanh
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 1); return false;">1
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 2); return false;">2
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 3); return false;">3
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 4); return false;">4
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 5); return false;">5
                                                            người</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, {{ $computedAdults }}); return false;">
                                                            <i
                                                                class="bi bi-star-fill me-1 text-warning"></i>{{ $computedAdults }}
                                                            người (Gợi ý)
                                                        </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-primary w-100"
                                                onclick="confirmCCCDCount({{ $booking->id }})">
                                                <i class="bi bi-check-circle me-1"></i>Xác nhận
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Gợi ý: {{ $computedAdults }} người lớn trong phòng
                                        @if ($children > 0)
                                            (Trẻ em: {{ $children }} người - không cần CCCD)
                                        @endif
                                    </small>
                                </div>

                                <div class="mb-3" id="cccdUploadSection{{ $booking->id }}" style="display: none;"
                                    data-existing-count="{{ $existingCCCDCount }}">
                                    <label class="form-label fw-semibold">
                                        Ảnh CCCD/CMND <span class="text-danger">*</span>
                                    </label>
                                    <div id="cccdInputsContainer{{ $booking->id }}">
                                        {{-- Inputs sẽ được tạo động bằng JavaScript --}}
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Vui lòng chụp hoặc upload ảnh mặt trước và mặt sau của CCCD/CMND.
                                        @if ($children > 0)
                                            <br>Lưu ý: Trẻ em ({{ $children }} người) không cần CCCD.
                                        @endif
                                    </small>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-info">
                                    <i class="bi bi-save me-1"></i>Lưu CCCD
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Modal Check-in với CCCD --}}
    @foreach ($bookings as $booking)
        @php
            $checkinDate = \Carbon\Carbon::parse($booking->ngay_nhan_phong);
            $isTodayOrPast = $checkinDate->isToday() || $checkinDate->isPast();

            $hasDonDep = collect($booking->datPhongItems ?? [])
                ->pluck('phong')
                ->filter()
                ->pluck('don_dep')
                ->contains(true);

            $checkinMeta = is_array($booking->snapshot_meta)
                ? $booking->snapshot_meta
                : json_decode($booking->snapshot_meta, true) ?? [];
            $checkinHasCCCD =
                !empty($checkinMeta['checkin_cccd_front']) ||
                !empty($checkinMeta['checkin_cccd_back']) ||
                !empty($checkinMeta['checkin_cccd']); // Backward compatibility
            
            // Lấy thông tin số người lớn và trẻ em
            $checkinComputedAdults = $checkinMeta['computed_adults'] ?? ($checkinMeta['adults'] ?? 1);
            $checkinChildren = $checkinMeta['children'] ?? 0;
            $checkinCCCDList = $checkinMeta['checkin_cccd_list'] ?? [];
            $checkinExistingCCCDCount =
                !empty($checkinCCCDList) && is_array($checkinCCCDList) ? count($checkinCCCDList) : 0;
            $checkinHasCCCDList = $checkinExistingCCCDCount > 0;
        @endphp

        @if ($booking->remaining <= 0 && $isTodayOrPast && !$hasDonDep)
            <div class="modal fade" id="checkinModal{{ $booking->id }}" tabindex="-1"
                aria-labelledby="checkinModalLabel{{ $booking->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="checkinModalLabel{{ $booking->id }}">
                                <i class="bi bi-check-circle me-2"></i>Xác nhận Check-in
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Đóng"></button>
                        </div>

                        <form action="{{ route('staff.processCheckin') }}" method="POST"
                            id="checkinForm{{ $booking->id }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Thông tin Booking</label>
                                    <div class="card bg-light p-3">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Mã booking:</small>
                                                <strong class="text-primary">{{ $booking->ma_tham_chieu }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Khách hàng:</small>
                                                <strong>{{ $booking->nguoiDung?->name ?? 'N/A' }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Ngày nhận:</small>
                                                <strong>{{ $checkinDate->format('d/m/Y') }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Tổng tiền:</small>
                                                <strong
                                                    class="text-success">{{ number_format($booking->tong_tien) }}đ</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Thông tin:</strong> Số người lớn trong phòng:
                                    <strong>{{ $checkinComputedAdults }}</strong>
                                    @if ($checkinChildren > 0)
                                        <br><small class="text-muted">(Trẻ em: {{ $checkinChildren }} người - không cần
                                            CCCD)</small>
                                    @endif
                                </div>

                                @if ($checkinHasCCCDList)
                                    <div class="alert alert-success mb-3">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <strong>Đã có ảnh CCCD ({{ $checkinExistingCCCDCount }} người):</strong>
                                        <div class="row g-2 mt-2">
                                            @foreach ($checkinCCCDList as $index => $cccdItem)
                                                <div class="col-12 col-md-6 mb-2">
                                                    <small class="text-muted d-block mb-1">Người
                                                        {{ $index + 1 }}:</small>
                                                    <div class="row g-2">
                                                        @if (!empty($cccdItem['front']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['front']))
                                                            <div class="col-6">
                                                                <small class="text-muted d-block mb-1">Mặt trước:</small>
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['front']) }}"
                                                                    alt="Mặt trước CCCD người {{ $index + 1 }}"
                                                                    class="img-thumbnail w-100"
                                                                    style="max-height: 150px; cursor: pointer; object-fit: contain;"
                                                                    onclick="window.open(this.src, '_blank')">
                                                            </div>
                                                        @endif
                                                        @if (!empty($cccdItem['back']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($cccdItem['back']))
                                                            <div class="col-6">
                                                                <small class="text-muted d-block mb-1">Mặt sau:</small>
                                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($cccdItem['back']) }}"
                                                                    alt="Mặt sau CCCD người {{ $index + 1 }}"
                                                                    class="img-thumbnail w-100"
                                                                    style="max-height: 150px; cursor: pointer; object-fit: contain;"
                                                                    onclick="window.open(this.src, '_blank')">
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <br><small>Bạn có thể xác nhận check-in hoặc cập nhật ảnh CCCD mới nếu cần.</small>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        Số lượng CCCD cần nhập <span class="text-danger">*</span>
                                    </label>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <input type="number" class="form-control" id="checkinCCCDCount{{ $booking->id }}"
                                                name="cccd_count" min="1" max="20"
                                                value="{{ $checkinExistingCCCDCount > 0 ? $checkinExistingCCCDCount : $checkinComputedAdults }}"
                                                required>
                                            <small class="form-text text-muted">
                                                Nhập số lượng CCCD cần nhập
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-list-ul me-1"></i>Chọn nhanh
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 1, 'checkin'); return false;">1
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 2, 'checkin'); return false;">2
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 3, 'checkin'); return false;">3
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 4, 'checkin'); return false;">4
                                                            người</a></li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, 5, 'checkin'); return false;">5
                                                            người</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item" href="#"
                                                            onclick="setCCCDCount({{ $booking->id }}, {{ $checkinComputedAdults }}, 'checkin'); return false;">
                                                            <i
                                                                class="bi bi-star-fill me-1 text-warning"></i>{{ $checkinComputedAdults }}
                                                            người (Gợi ý)
                                                        </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-primary w-100"
                                                onclick="confirmCCCDCount({{ $booking->id }}, 'checkin')">
                                                <i class="bi bi-check-circle me-1"></i>Xác nhận
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Gợi ý: {{ $checkinComputedAdults }} người lớn trong phòng
                                        @if ($checkinChildren > 0)
                                            (Trẻ em: {{ $checkinChildren }} người - không cần CCCD)
                                        @endif
                                    </small>
                                </div>

                                <div class="mb-3" id="checkinCCCDUploadSection{{ $booking->id }}" style="display: none;"
                                    data-existing-count="{{ $checkinExistingCCCDCount }}">
                                    <label class="form-label fw-semibold">
                                        Ảnh CCCD/CMND <span class="text-danger">*</span>
                                    </label>
                                    <div id="checkinCCCDInputsContainer{{ $booking->id }}">
                                        {{-- Inputs sẽ được tạo động bằng JavaScript --}}
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Vui lòng chụp hoặc upload ảnh mặt trước và mặt sau của CCCD/CMND.
                                        @if ($checkinChildren > 0)
                                            <br>Lưu ý: Trẻ em ({{ $checkinChildren }} người) không cần CCCD.
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i>Xác nhận Check-in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script>
        function previewCCCDImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (!preview) return;

            // Tìm previewImg element dựa trên previewId
            let previewImgId = previewId;
            if (previewId.includes('Front')) {
                // Hỗ trợ cả checkin và payment
                previewImgId = previewId.replace('previewFront', 'previewImgFront')
                    .replace('previewCheckinFront', 'previewImgCheckinFront')
                    .replace('previewcheckinFront', 'previewImgcheckinFront');
            } else if (previewId.includes('Back')) {
                previewImgId = previewId.replace('previewBack', 'previewImgBack')
                    .replace('previewCheckinBack', 'previewImgCheckinBack')
                    .replace('previewcheckinBack', 'previewImgcheckinBack');
            } else {
                previewImgId = previewId.replace('preview', 'previewImg');
                if (previewId.includes('Checkin') || previewId.includes('checkin')) {
                    previewImgId = previewId.replace('previewCheckin', 'previewImgCheckin')
                        .replace('previewcheckin', 'previewImgcheckin');
                }
            }

            const previewImg = document.getElementById(previewImgId);

            if (input.files && input.files[0] && previewImg) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else if (preview) {
                preview.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Modal thanh toán
            @if (session('show_payment_modal'))
                const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
                document.getElementById('modalPaymentAmount').textContent = '{{ session('payment_amount') }}';
                modal.show();
                setTimeout(() => modal._isShown && modal.hide(), 5000);
            @endif

            // Tooltip
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

            // Tìm kiếm + lọc
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const tableRows = document.querySelectorAll('#bookingsTableBody tr:not(.no-data-row)');
            const noDataRow = document.querySelector('.no-data-row');

            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                let visible = 0;

                tableRows.forEach(row => {
                    const maTC = row.cells[1]?.textContent.toLowerCase() || '';
                    const khach = row.cells[2]?.textContent.toLowerCase() || '';
                    const status = row.cells[4]?.querySelector('.badge')?.textContent.toLowerCase() || '';

                    const matchSearch = !searchTerm || maTC.includes(searchTerm) || khach.includes(
                        searchTerm);
                    const matchStatus = !statusValue || status.includes(statusValue.replace(/-/g, ' '));

                    row.style.display = matchSearch && matchStatus ? '' : 'none';
                    if (matchSearch && matchStatus) visible++;
                });

                if (noDataRow) noDataRow.style.display = visible === 0 ? '' : 'none';
            }

            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);

            // Hover
            document.querySelectorAll('tbody tr').forEach(row => {
                row.addEventListener('mouseenter', () => {
                    row.style.transform = 'translateY(-1px)';
                    row.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                });
                row.addEventListener('mouseleave', () => {
                    row.style.transform = '';
                    row.style.boxShadow = '';
                });
            });
        });
    </script>

    <style>
        .table th {
            font-weight: 600;
            color: #495057;
            border-top: none;
            padding: 0.75rem 0.5rem;
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .table td {
            border-color: rgba(0, 0, 0, 0.05);
            padding: 0.75rem 0.5rem;
            vertical-align: middle;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.06);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card {
            border-radius: 1rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .btn {
            transition: all 0.2s ease;
            font-size: 0.8rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
        }

        .input-group {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .pagination .page-link {
            border-radius: 0.5rem;
            margin: 0 2px;
            font-size: 0.85rem;
            color: #0d6efd;
            border: 1px solid #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
            border-color: #0d6efd;
        }

        .no-data-row {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .badge {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        @media (max-width: 768px) {

            .table th,
            .table td {
                padding: 0.5rem 0.25rem !important;
                font-size: 0.8rem;
            }

            .input-group {
                width: 100% !important;
            }
        }
    </style>

    <script>
        // Functions cho CCCD upload - expose ngay lập tức vào global scope
        // Không dùng IIFE để đảm bảo hàm có sẵn khi onclick được gọi
        
        window.setCCCDCount = function(bookingId, count, type = 'payment') {
            const prefix = type === 'checkin' ? 'checkinCCCDCount' : 'cccdCount';
            const countInput = document.getElementById(prefix + bookingId);
            if (countInput) {
                countInput.value = count;
            }
        };

        window.confirmCCCDCount = function(bookingId, type = 'payment') {
                console.log('confirmCCCDCount called with bookingId:', bookingId, 'type:', type);

                const prefix = type === 'checkin' ? 'checkinCCCDCount' : 'cccdCount';
                const countInput = document.getElementById(prefix + bookingId);
                if (!countInput) {
                    console.error('Không tìm thấy input số lượng với ID: ' + prefix + bookingId);
                    alert('Không tìm thấy input số lượng');
                    return;
                }

                const count = parseInt(countInput.value);
                console.log('Số lượng CCCD:', count);

                if (count < 1 || isNaN(count)) {
                    alert('Vui lòng nhập số lượng lớn hơn 0');
                    countInput.focus();
                    return;
                }

                // Hiển thị phần nhập ảnh
                const uploadSectionId = type === 'checkin' ? 'checkinCCCDUploadSection' : 'cccdUploadSection';
                const uploadSection = document.getElementById(uploadSectionId + bookingId);
                console.log('Upload section element:', uploadSection);

                if (uploadSection) {
                    uploadSection.style.display = 'block';
                    uploadSection.style.visibility = 'visible';
                    console.log('Đã hiển thị phần upload, display:', uploadSection.style.display);
                    console.log('Upload section element:', uploadSection);

                    // Đảm bảo container cũng được hiển thị
                    const containerId = type === 'checkin' ? 'checkinCCCDInputsContainer' : 'cccdInputsContainer';
                    const container = document.getElementById(containerId + bookingId);
                    if (container) {
                        container.style.display = 'block';
                        console.log('Container cũng đã được hiển thị');
                    }

                    // Scroll đến phần nhập ảnh
                    setTimeout(() => {
                        uploadSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }, 100);
                } else {
                    console.error('Không tìm thấy phần upload với ID: ' + uploadSectionId + bookingId);
                    console.error('Đang tìm element với ID:', uploadSectionId + bookingId);
                    alert('Không tìm thấy phần upload ảnh. Vui lòng tải lại trang.');
                    return;
                }

                // Tạo các input upload
                console.log('Gọi updateCCCDInputs với bookingId:', bookingId, 'count:', count, 'type:', type);
                window.updateCCCDInputs(bookingId, count, type);
            };

        window.updateCCCDInputs = function(bookingId, count, type = 'payment') {
            console.log('updateCCCDInputs called with bookingId:', bookingId, 'count:', count, 'type:', type);

            const containerId = type === 'checkin' ? 'checkinCCCDInputsContainer' : 'cccdInputsContainer';
            const container = document.getElementById(containerId + bookingId);
            if (!container) {
                console.error('Không tìm thấy container cho booking ID:', bookingId);
                alert('Không tìm thấy container upload. Vui lòng tải lại trang.');
                return;
            }

            console.log('Container found, clearing...');
            container.innerHTML = '';

            if (count < 1 || isNaN(count)) {
                container.innerHTML = '<div class="alert alert-warning">Vui lòng nhập số lượng lớn hơn 0</div>';
                return;
            }

            console.log('Tạo', count, 'input upload...');
            const prefix = type === 'checkin' ? 'checkin' : '';
            for (let i = 0; i < count; i++) {
                const card = document.createElement('div');
                card.className = 'card mb-3 border-primary';
                card.innerHTML = `
            <div class="card-header bg-light">
                <strong>Người ${i + 1}</strong>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="cccd${prefix}Front${bookingId}_${i}" class="form-label small">
                            Mặt trước <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="cccd${prefix}Front${bookingId}_${i}"
                               name="cccd_image_front_${i}" 
                               accept="image/*" 
                               required
                               onchange="previewCCCDImage(this, 'preview${prefix}Front${bookingId}_${i}')">
                        <div id="preview${prefix}Front${bookingId}_${i}" class="mt-2" style="display: none;">
                            <img id="previewImg${prefix}Front${bookingId}_${i}" src="" alt="Preview mặt trước" class="img-thumbnail w-100" style="max-height: 200px; object-fit: contain;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="cccd${prefix}Back${bookingId}_${i}" class="form-label small">
                            Mặt sau <span class="text-danger">*</span>
                        </label>
                        <input type="file" 
                               class="form-control" 
                               id="cccd${prefix}Back${bookingId}_${i}"
                               name="cccd_image_back_${i}" 
                               accept="image/*" 
                               required
                               onchange="previewCCCDImage(this, 'preview${prefix}Back${bookingId}_${i}')">
                        <div id="preview${prefix}Back${bookingId}_${i}" class="mt-2" style="display: none;">
                            <img id="previewImg${prefix}Back${bookingId}_${i}" src="" alt="Preview mặt sau" class="img-thumbnail w-100" style="max-height: 200px; object-fit: contain;">
                        </div>
                    </div>
                </div>
            </div>
        `;
                container.appendChild(card);
            }

            console.log('Đã tạo xong', count, 'input upload trong container');
            console.log('Container HTML length:', container.innerHTML.length);
        };

        // Reset phần upload khi modal được mở - sử dụng event delegation
        document.addEventListener('show.bs.modal', function(event) {
            const modal = event.target;
            let bookingId = null;
            let type = 'payment';
            
            // Kiểm tra nếu là modal CCCD (payment)
            if (modal.id && modal.id.startsWith('cccdModal')) {
                bookingId = modal.id.replace('cccdModal', '');
                type = 'payment';
            }
            // Kiểm tra nếu là modal Check-in
            else if (modal.id && modal.id.startsWith('checkinModal')) {
                bookingId = modal.id.replace('checkinModal', '');
                type = 'checkin';
            }
            
            if (bookingId) {
                // Ẩn phần upload khi modal mở
                const uploadSectionId = type === 'checkin' ? 'checkinCCCDUploadSection' : 'cccdUploadSection';
                const uploadSection = document.getElementById(uploadSectionId + bookingId);
                if (uploadSection) {
                    uploadSection.style.display = 'none';
                }

                // Xóa các input đã tạo
                const containerId = type === 'checkin' ? 'checkinCCCDInputsContainer' : 'cccdInputsContainer';
                const container = document.getElementById(containerId + bookingId);
                if (container) {
                    container.innerHTML = '';
                }
            }
        });

        // Đảm bảo các function đã được định nghĩa
        console.log('CCCD functions initialized:', {
            confirmCCCDCount: typeof window.confirmCCCDCount,
            setCCCDCount: typeof window.setCCCDCount,
            updateCCCDInputs: typeof window.updateCCCDInputs
        });

        // Khởi tạo tooltips cho tất cả các elements có data-bs-toggle="tooltip"
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    {{-- Các hàm JavaScript cũ đã được thay thế bởi hàm mới trong IIFE ở trên (dòng 950-997) --}}
    {{-- Hàm mới hỗ trợ cả checkin và payment với parameter type --}}

@endsection
