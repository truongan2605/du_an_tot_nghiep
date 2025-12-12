@extends('layouts.admin')

@section('title', 'Tổng Quan Booking')

@section('content')
    <div class="container-fluid px-3 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="h5 mb-0 fw-bold text-dark">Tổng Quan Booking</h3>
                <small class="text-muted">Quản lý và theo dõi các booking hiện tại</small>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="statusFilter">
                    <option value="">Tất cả Trạng Thái</option>
                    <option value="dang_cho">Chờ Xác Nhận</option>
                    <option value="da_xac_nhan">Đã Xác Nhận Đặt Cọc</option>
                    <option value="dang_su_dung">Đang sử dụng</option>
                    <option value="da_huy">Đã Hủy</option>
                    <option value="hoan_thanh">Hoàn Thành</option>
                </select>
                <input type="date" class="form-control form-control-sm" id="dateFilter" style="width: 140px;">
            </div>
        </div>

        {{-- CÁC CHỈ SỐ KPI --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-house-door-fill fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Số phòng trống</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($soPhongTrong ?? 0) }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Phòng đang có khách</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($soPhongDangCoKhach ?? 0) }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-tools fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Chờ dọn / Bảo trì</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($soPhongChoDonBaoTri ?? 0) }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-calendar-check-fill fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Đặt phòng hôm nay</h6>
                        <h2 class="mb-0 fw-bold">{{ number_format($soDatPhongHomNay ?? 0) }}</h2>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-cash-coin fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu hôm nay</h6>
                        <h2 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ number_format($tongDoanhThuHomNay ?? 0, 0) }}đ</h2>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="card-body text-white p-3">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                        </div>
                        <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu tháng này</h6>
                        <h2 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ number_format($doanhThuThangNay ?? 0, 0) }}đ</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm rounded-3 border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-3" style="width: 60px;">ID</th>
                                <th class="py-3" style="width: 120px;">Phòng</th>
                                <th class="py-3" style="width: 150px;">Khách Hàng</th>
                                <th class="py-3 text-center" style="width: 100px;">Trạng Thái</th>
                                <th class="py-3" style="width: 120px;">Mã đặt phòng</th>
                                <th class="py-3" style="width: 140px;">Ngày Nhận</th>
                                <th class="py-3" style="width: 140px;">Ngày Trả</th>
                                <th class="py-3 text-end" style="width: 100px;">Tổng Tiền</th>
                                <th class="py-3 text-end" style="width: 100px;">Đặt Cọc</th>
                                <th class="py-3 text-center" style="width: 120px;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            @forelse ($bookings as $booking)
                                <tr data-status="{{ $booking->trang_thai }}"
                                    data-date="{{ $booking->ngay_nhan_phong?->format('Y-m-d') ?? '' }}">
                                    <td class="ps-3 fw-semibold">{{ $booking->id }}</td>
                                    <td>
                                        @php
                                            $roomCodes = [];

                                            // 1) ưu tiên dat_phong_item (nếu còn)
                                            if (
                                                !empty($booking->datPhongItems) &&
                                                $booking->datPhongItems->isNotEmpty()
                                            ) {
                                                foreach ($booking->datPhongItems as $item) {
                                                    if (!empty($item->phong) && !empty($item->phong->ma_phong)) {
                                                        $roomCodes[] = $item->phong->ma_phong;
                                                    } elseif (!empty($item->phong_id)) {
                                                        $roomCodes[] = 'Phòng #' . $item->phong_id;
                                                    }
                                                }
                                            } else {
                                                // 2) nếu đã xóa dat_phong_item thì lấy từ hoa_don_items loại 'room' (hoặc 'room_booking')
                                                if (!empty($booking->hoaDons)) {
                                                    foreach ($booking->hoaDons as $hd) {
                                                        if (empty($hd->hoaDonItems)) {
                                                            continue;
                                                        }
                                                        foreach ($hd->hoaDonItems as $it) {
                                                            if (in_array($it->type, ['room', 'room_booking'])) {
                                                                if (
                                                                    !empty($it->phong) &&
                                                                    !empty($it->phong->ma_phong)
                                                                ) {
                                                                    $roomCodes[] = $it->phong->ma_phong;
                                                                } elseif (!empty($it->phong_id)) {
                                                                    $roomCodes[] = 'Phòng #' . $it->phong_id;
                                                                } elseif (!empty($it->name)) {
                                                                    $roomCodes[] = $it->name;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            $roomCodes = array_values(array_unique(array_filter($roomCodes)));
                                        @endphp

                                        @if (!empty($roomCodes))
                                            @foreach ($roomCodes as $code)
                                                <span class="badge bg-success bg-opacity-75 me-1 small rounded-pill"
                                                    data-bs-toggle="tooltip" title="Phòng {{ $code }}">
                                                    {{ $code }}
                                                </span>
                                            @endforeach
                                            @if ($booking->roomChanges->isNotEmpty())
                                                <span class="badge bg-warning text-dark small rounded-pill" 
                                                    data-bs-toggle="tooltip" 
                                                    title="Có {{ $booking->roomChanges->count() }} lần đổi phòng">
                                                    <i class="bi bi-arrow-left-right"></i> {{ $booking->roomChanges->count() }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">Chưa gán</span>
                                        @endif

                                    </td>
                                    <td class="small">{{ Str::limit($booking->nguoiDung?->name ?? 'Ẩn danh', 20) }}</td>
                                    <td class="text-center">
                                        @php
                                            $statusColors = [
                                                'dang_cho' => 'bg-warning text-dark',
                                                'da_xac_nhan' => 'bg-primary text-white',
                                                'dang_su_dung' => 'bg-info text-white',
                                                'da_huy' => 'bg-secondary text-white',
                                                'hoan_thanh' => 'bg-primary text-white',
                                            ];
                                            $statusLabels = [
                                                'dang_cho' => 'Chờ Xác Nhận',
                                                'dang_cho_xac_nhan' => 'Đang chờ xác nhận',
                                                'da_xac_nhan' => 'Đã Xác Nhận Đặt Cọc',
                                                'da_huy' => 'Đã Hủy',
                                                'dang_su_dung' => 'Đang sử dụng',
                                                'hoan_thanh' => 'Hoàn Thành',
                                            ];
                                        @endphp
                                        <span
                                            class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }} rounded-pill px-2 py-1 small">
                                            {{ $statusLabels[$booking->trang_thai] ?? ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                                        </span>
                                    </td>
                                    <td class="small">{{ Str::limit($booking->ma_tham_chieu ?? 'Chưa có', 15) }}</td>
                                    <td class="small">
                                        @if($booking->ngay_nhan_phong)
                                            {{ \Carbon\Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0)->format('d/m H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($booking->ngay_tra_phong)
                                            {{ \Carbon\Carbon::parse($booking->ngay_tra_phong)->setTime(12, 0)->format('d/m H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold small">
                                        {{ number_format($booking->tong_tien, 0, ',', '.') }}đ</td>
                                    <td class="text-end fw-semibold small">
                                        {{ number_format($booking->deposit_amount, 0, ',', '.') }}đ</td>
                                    <td class="text-center position-relative">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-outline-secondary btn-sm rounded-pill px-2 dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button class="dropdown-item small quick-view-btn" 
                                                        data-booking-id="{{ $booking->id }}"
                                                        type="button">
                                                        <i class="bi bi-lightning me-1"></i> Xem Nhanh
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item small quick-note-btn" 
                                                        data-booking-id="{{ $booking->id }}"
                                                        type="button">
                                                        <i class="bi bi-pencil-square me-1"></i> Ghi Chú Nhanh
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item small"
                                                        href="{{ route('staff.bookings.show', $booking->id) }}"><i
                                                            class="bi bi-eye me-1"></i> Xem Chi Tiết</a></li>
                                                @if ($booking->roomChanges->isNotEmpty())
                                                    <li>
                                                        <button class="dropdown-item small toggle-room-changes" 
                                                            data-booking-id="{{ $booking->id }}"
                                                            type="button">
                                                            <i class="bi bi-clock-history me-1"></i> Lịch Sử Đổi Phòng
                                                        </button>
                                                    </li>
                                                @endif
                                                @if (in_array($booking->trang_thai, ['dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan']))
                                                    <li>
                                                        <form action="{{ route('staff.cancel', $booking->id) }}"
                                                            method="POST" class="d-inline"
                                                            id="cancel-form-{{ $booking->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="dropdown-item small text-danger"
                                                                onclick="handleAction('Hủy booking này?', this, event)">
                                                                <i class="bi bi-x-circle me-1"></i> Hủy
                                                            </button>
                                                        </form>
                                                    </li>
                                                @elseif (in_array($booking->trang_thai, ['dang_su_dung']) && \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                                                    <li>
                                                        {{-- Uncomment / implement checkout route if you add it --}}
                                                    </li>
                                                    <li><a class="dropdown-item small text-success"
                                                            href="{{ route('staff.rooms') }}"><i
                                                                class="bi bi-house me-1"></i> Xem Phòng</a></li>
                                                @elseif (in_array($booking->trang_thai, ['da_huy', 'hoan_thanh']))
                                                    <li><span class="dropdown-item text-muted small disabled">Không có hành
                                                            động</span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                {{-- Room Change History Row (Hidden by default) --}}
                                @if ($booking->roomChanges->isNotEmpty())
                                    <tr class="room-changes-row" id="room-changes-{{ $booking->id }}" style="display: none;">
                                        <td colspan="10" class="p-0">
                                            <div class="bg-light border-top border-bottom px-4 py-3">
                                                <h6 class="mb-3 text-primary">
                                                    <i class="bi bi-clock-history me-2"></i>Lịch Sử Đổi Phòng
                                                </h6>
                                                <div class="timeline">
                                                    @foreach ($booking->roomChanges->sortByDesc('created_at') as $change)
                                                        <div class="timeline-item mb-3 d-flex">
                                                            <div class="timeline-icon me-3">
                                                                @if ($change->isUpgrade())
                                                                    <span class="badge bg-success rounded-circle p-2">
                                                                        <i class="bi bi-arrow-up"></i>
                                                                    </span>
                                                                @elseif ($change->isDowngrade())
                                                                    <span class="badge bg-danger rounded-circle p-2">
                                                                        <i class="bi bi-arrow-down"></i>
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-info rounded-circle p-2">
                                                                        <i class="bi bi-arrow-left-right"></i>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="timeline-content flex-grow-1">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <div>
                                                                        <strong>{{ $change->oldRoom->ma_phong ?? 'N/A' }}</strong>
                                                                        <i class="bi bi-arrow-right mx-2"></i>
                                                                        <strong>{{ $change->newRoom->ma_phong ?? 'N/A' }}</strong>
                                                                        @if ($change->isUpgrade())
                                                                            <span class="badge bg-success ms-2">Nâng cấp</span>
                                                                        @elseif ($change->isDowngrade())
                                                                            <span class="badge bg-danger ms-2">Hạ cấp</span>
                                                                        @else
                                                                            <span class="badge bg-info ms-2">Cùng giá</span>
                                                                        @endif
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        {{ $change->created_at->format('d/m/Y H:i') }}
                                                                    </small>
                                                                </div>
                                                                <div class="small text-muted">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <strong>Giá cũ:</strong> {{ number_format($change->old_price, 0, ',', '.') }}đ/đêm<br>
                                                                            <strong>Giá mới:</strong> {{ number_format($change->new_price, 0, ',', '.') }}đ/đêm<br>
                                                                            <strong>Số đêm:</strong> {{ $change->nights }}
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>Người thực hiện:</strong> 
                                                                            {{ $change->changed_by_type === 'customer' ? 'Khách hàng' : 'Nhân viên' }}
                                                                            ({{ $change->changedByUser->name ?? 'N/A' }})<br>
                                                                            <strong>Trạng thái:</strong> 
                                                                            @if ($change->status === 'completed')
                                                                                <span class="badge bg-success">Hoàn thành</span>
                                                                            @elseif ($change->status === 'pending')
                                                                                <span class="badge bg-warning">Đang xử lý</span>
                                                                            @else
                                                                                <span class="badge bg-danger">Thất bại</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    @if ($change->change_reason)
                                                                        <div class="mt-2">
                                                                            <strong>Lý do:</strong> {{ $change->change_reason }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox display-6 mb-2 opacity-50"></i>
                                        <p class="mb-0">Không có booking nào.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light border-0 py-2">
                <div class="d-flex justify-content-between align-items-center small text-muted">
                    <span>Hiển thị {{ $bookings->firstItem() ?? 0 }} - {{ $bookings->lastItem() ?? 0 }} của
                        {{ $bookings->total() }} booking</span>
                    <div>
                        {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick View Modal --}}
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title" id="quickViewModalLabel">
                        <i class="bi bi-lightning-fill me-2 text-primary"></i>
                        Xem Nhanh Booking
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="quickViewContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-3 text-muted">Đang tải thông tin booking...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Đóng
                    </button>
                    <a href="#" class="btn btn-primary btn-sm" id="viewFullDetailsBtn">
                        <i class="bi bi-eye me-1"></i>Xem Chi Tiết Đầy Đủ
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Note Modal --}}
    <div class="modal fade" id="quickNoteModal" tabindex="-1" aria-labelledby="quickNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title" id="quickNoteModalLabel">
                        <i class="bi bi-pencil-square me-2 text-warning"></i>
                        Ghi Chú Nhanh
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickNoteForm">
                        <input type="hidden" id="noteBookingId" name="booking_id">
                        <div class="mb-3">
                            <label for="bookingReference" class="form-label fw-bold">Booking:</label>
                            <input type="text" class="form-control" id="bookingReference" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="noteContent" class="form-label fw-bold">Ghi chú: <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="noteContent" name="note" rows="4" 
                                placeholder="Nhập ghi chú cho booking này..." required></textarea>
                            <div class="form-text">Ghi chú sẽ được lưu vào lịch sử của booking</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveNoteBtn">
                        <i class="bi bi-check-lg me-1"></i>Lưu Ghi Chú
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Init tooltips (dùng forEach)
            var tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(el) {
                try {
                    new bootstrap.Tooltip(el);
                } catch (e) {
                    console.error('Tooltip init error', e);
                }
            });

            // Explicitly init all dropdown toggles so they are ready immediately
            var ddToggles = Array.from(document.querySelectorAll('.dropdown-toggle'));
            ddToggles.forEach(function(btn) {
                try {
                    bootstrap.Dropdown.getOrCreateInstance(btn);
                } catch (e) {
                    console.error('Dropdown init error', e);
                }
            });

            window.handleAction = function(message, button, event) {
                event.preventDefault();
                event.stopPropagation();

                // find the dropdown toggle (closest ancestor .dropdown then its toggle)
                const dropdown = button.closest('.dropdown');
                const toggle = dropdown ? dropdown.querySelector('.dropdown-toggle') : null;

                if (!confirm(message)) {
                    if (toggle) {
                        const inst = bootstrap.Dropdown.getOrCreateInstance(toggle);
                        inst.hide();
                    }
                    return;
                }

                const form = button.closest('form');
                if (form) {
                    form.submit();
                } else {
                    if (toggle) {
                        const inst = bootstrap.Dropdown.getOrCreateInstance(toggle);
                        inst.hide();
                    }
                }
            };

            // Quick View functionality
            document.querySelectorAll('.quick-view-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.bookingId;
                    const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                    const contentDiv = document.getElementById('quickViewContent');
                    const viewFullBtn = document.getElementById('viewFullDetailsBtn');
                    
                    // Update link to full details page
                    viewFullBtn.href = `/staff/bookings/${bookingId}`;
                    
                    // Show loading state
                    contentDiv.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-3 text-muted">Đang tải thông tin booking...</p>
                        </div>
                    `;
                    
                    modal.show();
                    
                    // Fetch booking details via AJAX
                    fetch(`/staff/bookings/${bookingId}/quick-view`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                contentDiv.innerHTML = formatQuickViewContent(data.booking);
                            } else {
                                contentDiv.innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        ${data.message || 'Không thể tải thông tin booking'}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            contentDiv.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Có lỗi xảy ra khi tải thông tin. Vui lòng thử lại.
                                </div>
                            `;
                        });
                    
                    // Close dropdown
                    const dropdown = this.closest('.dropdown');
                    if (dropdown) {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        if (toggle) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    }
                });
            });

            // Quick Note functionality
            document.querySelectorAll('.quick-note-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.bookingId;
                    const row = this.closest('tr');
                    const bookingRef = row.querySelector('td:nth-child(5)').textContent.trim();
                    
                    document.getElementById('noteBookingId').value = bookingId;
                    document.getElementById('bookingReference').value = bookingRef;
                    document.getElementById('noteContent').value = '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('quickNoteModal'));
                    modal.show();
                    
                    // Close dropdown
                    const dropdown = this.closest('.dropdown');
                    if (dropdown) {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        if (toggle) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    }
                });
            });

            // Save Note functionality
            document.getElementById('saveNoteBtn').addEventListener('click', function() {
                const bookingId = document.getElementById('noteBookingId').value;
                const noteContent = document.getElementById('noteContent').value.trim();
                const saveBtn = this;
                
                if (!noteContent) {
                    alert('Vui lòng nhập nội dung ghi chú');
                    return;
                }
                
                // Disable button and show loading
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...';
                
                // Send AJAX request
                fetch(`/staff/bookings/${bookingId}/add-note`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ note: noteContent })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('✅ Ghi chú đã được lưu thành công!');
                        
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('quickNoteModal')).hide();
                        
                        // Reset form
                        document.getElementById('quickNoteForm').reset();
                    } else {
                        alert('❌ ' + (data.message || 'Không thể lưu ghi chú'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Có lỗi xảy ra khi lưu ghi chú. Vui lòng thử lại.');
                })
                .finally(() => {
                    // Re-enable button
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Lưu Ghi Chú';
                });
            });

            // Helper function to format quick view content
            function formatQuickViewContent(booking) {
                const statusColors = {
                    'dang_cho': 'warning',
                    'da_xac_nhan': 'primary',
                    'dang_su_dung': 'info',
                    'da_huy': 'secondary',
                    'hoan_thanh': 'success'
                };
                
                const statusLabels = {
                    'dang_cho': 'Chờ Xác Nhận',
                    'da_xac_nhan': 'Đã Xác Nhận',
                    'dang_su_dung': 'Đang Sử Dụng',
                    'da_huy': 'Đã Hủy',
                    'hoan_thanh': 'Hoàn Thành'
                };
                
                return `
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${booking.ma_tham_chieu}</h6>
                                    <small class="text-muted">ID: ${booking.id}</small>
                                </div>
                                <span class="badge bg-${statusColors[booking.trang_thai] || 'secondary'}">
                                    ${statusLabels[booking.trang_thai] || booking.trang_thai}
                                </span>
                            </div>
                            <hr>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small">Khách hàng</label>
                            <div class="fw-bold">${booking.customer_name || 'N/A'}</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small">Email / SĐT</label>
                            <div class="fw-bold small">${booking.customer_email || 'N/A'}</div>
                            <div class="fw-bold small">${booking.customer_phone || 'N/A'}</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small">Ngày nhận phòng</label>
                            <div class="fw-bold">
                                <i class="bi bi-calendar-check text-success me-1"></i>
                                ${booking.ngay_nhan_phong}
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small">Ngày trả phòng</label>
                            <div class="fw-bold">
                                <i class="bi bi-calendar-x text-danger me-1"></i>
                                ${booking.ngay_tra_phong}
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="text-muted small">Phòng</label>
                            <div class="d-flex flex-wrap gap-2">
                                ${booking.rooms.map(room => `
                                    <span class="badge bg-success bg-opacity-75">${room}</span>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="text-muted small">Tổng tiền</label>
                            <div class="fw-bold text-primary fs-5">${formatMoney(booking.tong_tien)}</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="text-muted small">Đã cọc</label>
                            <div class="fw-bold text-success">${formatMoney(booking.deposit_amount)}</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="text-muted small">Còn lại</label>
                            <div class="fw-bold text-danger">${formatMoney(booking.tong_tien - booking.deposit_amount)}</div>
                        </div>
                        
                        ${booking.notes && booking.notes.length > 0 ? `
                            <div class="col-12">
                                <label class="text-muted small">Ghi chú gần nhất</label>
                                <div class="alert alert-info mb-0">
                                    <small>${booking.notes[0].content}</small>
                                    <div class="text-muted mt-1" style="font-size: 0.75rem;">
                                        ${booking.notes[0].created_at} - ${booking.notes[0].created_by}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            
            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
            }

            // Toggle room changes history
            document.querySelectorAll('.toggle-room-changes').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.bookingId;
                    const historyRow = document.getElementById(`room-changes-${bookingId}`);
                    
                    if (historyRow) {
                        if (historyRow.style.display === 'none') {
                            historyRow.style.display = '';
                        } else {
                            historyRow.style.display = 'none';
                        }
                    }
                    
                    // Close the dropdown
                    const dropdown = this.closest('.dropdown');
                    if (dropdown) {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        if (toggle) {
                            const inst = bootstrap.Dropdown.getOrCreateInstance(toggle);
                            inst.hide();
                        }
                    }
                });
            });

            const statusFilter = document.getElementById('statusFilter');
            const dateFilter = document.getElementById('dateFilter');
            const tableRows = document.querySelectorAll('#bookingsTableBody tr[data-status]');
            const noDataRow = document.querySelector('#bookingsTableBody .no-data-row');

            function applyFilters() {
                const statusValue = statusFilter.value;
                const dateValue = dateFilter.value;
                let visibleRows = 0;

                tableRows.forEach(row => {
                    const rowStatus = row.dataset.status;
                    const rowDate = row.dataset.date;
                    const matchesStatus = !statusValue || rowStatus === statusValue;
                    const matchesDate = !dateValue || rowDate === dateValue;

                    if (matchesStatus && matchesDate) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noDataRow) {
                    noDataRow.style.display = visibleRows === 0 ? '' : 'none';
                }
            }

            statusFilter.addEventListener('change', applyFilters);
            dateFilter.addEventListener('change', applyFilters);

            applyFilters();
        });
    </script>


    <style>
        /* giữ nguyên style như bạn đã có */
        .table {
            font-size: 0.875rem;
        }

        .table th {
            font-weight: 600;
            color: #6c757d;
            border-top: none;
        }

        .table td {
            border-color: rgba(0, 0, 0, 0.04);
            padding: 0.75rem 0.5rem;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
            transition: background-color 0.15s ease;
        }

        .card {
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .pagination .page-link {
            color: #0d6efd;
            border: 1px solid #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .dropdown-menu {
            min-width: 140px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 1060;
        }

        .table-responsive {
            overflow: visible;
        }

        .form-select-sm,
        .form-control-sm {
            border-radius: 0.375rem;
        }

        .no-data-row {
            display: table-row;
        }

        .position-relative {
            position: relative;
        }

        /* Timeline Styles for Room Change History */
        .timeline {
            position: relative;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-icon {
            flex-shrink: 0;
        }

        .timeline-content {
            background: white;
            border-radius: 0.375rem;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }

        .room-changes-row {
            background-color: #f8f9fa;
        }

        .room-changes-row td {
            border: none !important;
        }

        /* KPI Cards */
        .kpi-card {
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
        }
        
        .kpi-icon-wrapper {
            transition: transform 0.3s ease;
        }
        
        .kpi-card:hover .kpi-icon-wrapper {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .kpi-card .card-body h2 {
                font-size: 1.3rem;
            }
            
            .kpi-card .card-body h6 {
                font-size: 0.7rem;
            }
        }
    </style>
@endsection
