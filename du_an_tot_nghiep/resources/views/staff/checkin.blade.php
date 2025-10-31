@extends('layouts.staff')

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

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow mb-3 rounded-3" role="alert" style="font-size: 0.875rem; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 text-success"></i>
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow mb-3 rounded-3" role="alert" style="font-size: 0.875rem; background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>
                {{ session('error') }}
            </div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Compact Stats -->
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow h-100 overflow-hidden position-relative" style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: white;">
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
            <div class="card border-0 shadow h-100 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: #000;">
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
            <div class="card border-0 shadow h-100 overflow-hidden position-relative" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">Quá hạn</small>
                            <div class="fw-bold fs-4">{{ $bookings->where('checkin_status', '!=', 'Hôm nay')->where('checkin_status', '!=', 'Sắp tới')->count() }}</div>
                        </div>
                        <i class="bi bi-exclamation-circle fs-2 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow h-100 overflow-hidden position-relative" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white;">
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
                            <th class="py-2 fw-semibold text-dark" style="min-width: 90px; font-size: 0.85rem;">Mã TC</th>
                            <th class="py-2 fw-semibold text-dark" style="min-width: 120px; font-size: 0.85rem;">Khách</th>
                            <th class="py-2 fw-semibold text-dark" style="min-width: 80px; font-size: 0.85rem;">Ngày</th>
                            <th class="py-2 fw-semibold text-center text-dark" style="width: 100px; font-size: 0.85rem;">Trạng thái</th>
                            <th class="py-2 fw-semibold text-end text-dark" style="min-width: 80px; font-size: 0.85rem;">Tổng</th>
                            <th class="py-2 fw-semibold text-end text-dark d-none d-sm-table-cell" style="min-width: 70px; font-size: 0.85rem;">Đã TT</th>
                            <th class="py-2 fw-semibold text-end text-dark d-none d-md-table-cell" style="min-width: 70px; font-size: 0.85rem;">Còn</th>
                            <th class="py-2 fw-semibold text-center text-dark d-none d-sm-table-cell" style="width: 80px; font-size: 0.85rem;">TT</th>
                            <th class="pe-2 py-2 fw-semibold text-center text-dark" style="width: 140px; font-size: 0.85rem;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody">
                        @forelse ($bookings as $booking)
                            <tr class="border-bottom">
                                <td class="ps-2 small text-secondary">{{ $booking->id }}</td>
                                <td class="fw-semibold small text-primary">{{ $booking->ma_tham_chieu }}</td>
                                <td class="small text-truncate" style="max-width: 100px;" title="{{ $booking->nguoiDung->name ?? 'Ẩn danh' }}">
                                    {{ Str::limit($booking->nguoiDung->name ?? 'Ẩn danh', 12) }}
                                </td>
                                <td class="small">{{ \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('d/m') }}</td>
                                <td class="text-center">
                                    @if ($booking->checkin_status === 'Hôm nay')
                                        <span class="badge bg-success rounded-pill px-2 py-1 small fw-semibold shadow-sm"><i class="bi bi-check-lg me-1"></i>Hôm nay</span>
                                    @elseif ($booking->checkin_status === 'Sắp tới')
                                        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-semibold shadow-sm"><i class="bi bi-clock me-1"></i>Sắp {{ $booking->checkin_date_diff }}</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-2 py-1 small fw-semibold shadow-sm"><i class="bi bi-exclamation-triangle me-1"></i>Quá {{ $booking->checkin_date_diff }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold small text-dark">{{ number_format($booking->tong_tien) }}đ</td>
                                <td class="text-end text-success small fw-medium d-none d-sm-table-cell">{{ number_format($booking->paid) }}đ</td>
                                <td class="text-end fw-semibold text-danger small d-none d-md-table-cell">{{ number_format($booking->remaining) }}đ</td>
                                <td class="text-center d-none d-sm-table-cell">
                                    @if ($booking->trang_thai === 'da_xac_nhan')
                                        <span class="badge bg-info rounded-pill px-2 py-1 small fw-semibold shadow-sm">Xác nhận</span>
                                    @elseif ($booking->trang_thai === 'da_gan_phong')
                                        <span class="badge bg-primary rounded-pill px-2 py-1 small fw-semibold shadow-sm">Gán phòng</span>
                                    @endif
                                </td>
                                <td class="pe-2 text-center">
                                    @if ($booking->remaining > 0)
                                        <form action="{{ route('payment.remaining', $booking->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden" style="width: 120px;">
                                                <select name="nha_cung_cap" class="form-select form-select-sm border-0 px-2" required style="background: white;">
                                                    <option value="">Chọn</option>
                                                    <option value="tien_mat">Tiền mặt</option>
                                                    <option value="vnpay">VNPAY</option>
                                                </select>
                                                <button type="submit" class="btn btn-warning border-0 px-2" title="Thanh toán">
                                                    <i class="bi bi-arrow-right"></i>
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        @if (\Carbon\Carbon::parse($booking->ngay_nhan_phong)->isToday() || \Carbon\Carbon::parse($booking->ngay_nhan_phong)->isPast())
                                            <form action="{{ route('staff.processCheckin') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                                                <button type="submit" class="btn btn-success px-3 py-1 rounded-pill fw-semibold shadow-sm"
                                                    onclick="return confirm('Check-in {{ $booking->ma_tham_chieu }}?')">
                                                    <i class="bi bi-check-circle me-1"></i>Check-in
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-outline-secondary px-3 py-1 rounded-pill fw-semibold shadow-sm" disabled title="Chưa tới ngày">
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
                <span>{{ $bookings->firstItem() ?? 0 }} - {{ $bookings->lastItem() ?? 0 }} / {{ $bookings->total() }} kết quả</span>
                {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('#bookingsTableBody tr:not(.no-data-row)');
    const noDataRow = document.querySelector('.no-data-row');

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value;
        let visibleRows = 0;

        tableRows.forEach(row => {
            const maTC = row.cells[1]?.textContent.toLowerCase() || '';
            const khachHang = row.cells[2]?.textContent.toLowerCase() || '';
            const statusCell = row.cells[4]?.querySelector('.badge')?.textContent.toLowerCase() || '';

            const matchesSearch = !searchTerm || maTC.includes(searchTerm) || khachHang.includes(searchTerm);
            const matchesStatus = !statusValue || statusCell.includes(statusValue.replace(/-/g, ' '));

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noDataRow) noDataRow.style.display = visibleRows === 0 ? '' : 'none';
    }

    searchInput.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);


    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.transform = 'translateY(-1px)';
            row.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        });
        row.addEventListener('mouseleave', () => {
            row.style.transform = 'translateY(0)';
            row.style.boxShadow = 'none';
        });
    });
});
</script>

<style>
.table th { font-weight: 600; color: #495057; border-top: none; padding: 0.75rem 0.5rem; background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%); }
.table td { border-color: rgba(0,0,0,0.05); padding: 0.75rem 0.5rem; vertical-align: middle; font-size: 0.85rem; transition: all 0.2s ease; }
.table-hover tbody tr:hover { background-color: rgba(13,110,253,0.06); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.card { border-radius: 1rem; transition: all 0.3s ease; overflow: hidden; }
.card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; }
.btn { transition: all 0.2s ease; font-size: 0.8rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important; }
.input-group { box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 0.5rem; overflow: hidden; }
.pagination .page-link { border-radius: 0.5rem; margin: 0 2px; font-size: 0.85rem; color: #0d6efd; border: 1px solid #dee2e6; }
.pagination .page-item.active .page-link { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); border-color: #0d6efd; }
.no-data-row { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); }
.badge { box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s ease; }
.badge:hover { transform: scale(1.05); }
@media (max-width: 768px) {
    .table th, .table td { padding: 0.5rem 0.25rem !important; font-size: 0.8rem; }
    .badge { font-size: 0.7rem; padding: 0.3em 0.5em; }
    .input-group { width: 100% !important; }
    .row.g-2 > div { margin-bottom: 0.75rem; }
}
@media (max-width: 576px) {
    .table th:nth-child(7), .table td:nth-child(7),
    .table th:nth-child(8), .table td:nth-child(8),
    .table th:nth-child(9), .table td:nth-child(9) { display: none; }
}
</style>
@endsection