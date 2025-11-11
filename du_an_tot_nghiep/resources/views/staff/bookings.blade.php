@extends('layouts.staff')

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
                        <tr data-status="{{ $booking->trang_thai }}" data-date="{{ $booking->ngay_nhan_phong?->format('Y-m-d') ?? '' }}">
                            <td class="ps-3 fw-semibold">{{ $booking->id }}</td>
                            <td>
                                @php
                                    $roomCodes = [];
                                    if ($booking->datPhongItems) {
                                        foreach ($booking->datPhongItems as $item) {
                                            if ($item->phong) {
                                                $roomCodes[] = $item->phong->ma_phong;
                                            }
                                        }
                                    }
                                @endphp
                                @if (!empty($roomCodes))
                                    @foreach (array_unique($roomCodes) as $code)
                                        <span class="badge bg-success bg-opacity-75 me-1 small rounded-pill" data-bs-toggle="tooltip" title="Phòng {{ $code }}">
                                            {{ $code }}
                                        </span>
                                    @endforeach
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
                                <span class="badge {{ $statusColors[$booking->trang_thai] ?? 'bg-secondary' }} rounded-pill px-2 py-1 small">
                                    {{ $statusLabels[$booking->trang_thai] ?? ucfirst(str_replace('_', ' ', $booking->trang_thai)) }}
                                </span>
                            </td>
                            <td class="small">{{ Str::limit($booking->ma_tham_chieu ?? 'Chưa có', 15) }}</td>
                            <td class="small">{{ $booking->ngay_nhan_phong?->format('d/m H:i') ?? '-' }}</td>
                            <td class="small">{{ $booking->ngay_tra_phong?->format('d/m H:i') ?? '-' }}</td>
                            <td class="text-end fw-semibold small">{{ number_format($booking->tong_tien, 0, ',', '.') }}đ</td>
                            <td class="text-end fw-semibold small">{{ number_format($booking->deposit_amount, 0, ',', '.') }}đ</td>
                            <td class="text-center position-relative">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li><a class="dropdown-item small" href="#"><i class="bi bi-eye me-1"></i> Xem Chi Tiết</a></li>
                                        @if ($booking->trang_thai === 'dang_cho')
                                            <li>
                                                <form action="{{ route('staff.confirm', $booking->id) }}" method="POST" class="d-inline" id="confirm-form-{{ $booking->id }}">
                                                    @csrf
                                                    <button type="button" class="dropdown-item small text-success" onclick="handleAction('Xác nhận và gán phòng tự động?', this, event)">
                                                        <i class="bi bi-check-circle me-1"></i> Xác Nhận
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('staff.cancel', $booking->id) }}" method="POST" class="d-inline" id="cancel-form-{{ $booking->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="dropdown-item small text-danger" onclick="handleAction('Hủy booking này?', this, event)">
                                                        <i class="bi bi-x-circle me-1"></i> Hủy
                                                    </button>
                                                </form>
                                            </li>
                                        @elseif (in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong', 'dang_o']) && \Carbon\Carbon::parse($booking->ngay_tra_phong)->isToday())
                                            <li>
                                                {{-- <form action="{{ route('staff.checkout.process', $booking->id) }}" method="POST" class="d-inline" id="checkout-form-{{ $booking->id }}">
                                                    @csrf
                                                    <button type="button" class="dropdown-item small text-warning" onclick="handleAction('Xác nhận check-out?', this, event)">
                                                        <i class="bi bi-box-arrow-left me-1"></i> Check-out
                                                    </button>
                                                </form> --}}
                                            </li>
                                            <li><a class="dropdown-item small text-success" href="{{ route('staff.rooms') }}"><i class="bi bi-house me-1"></i> Xem Phòng</a></li>
                                        @elseif ($booking->trang_thai === 'da_huy' || $booking->trang_thai === 'hoan_thanh')
                                            <li><span class="dropdown-item text-muted small disabled">Không có hành động</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
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
                <span>Hiển thị {{ $bookings->firstItem() ?? 0 }} - {{ $bookings->lastItem() ?? 0 }} của {{ $bookings->total() }} booking</span>
                <div>
                    {{ $bookings->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });


    window.handleAction = function(message, button, event) {
        event.preventDefault();
        event.stopPropagation();
        if (confirm(message)) {
            button.closest('form').submit();
        }
      
        const dropdown = button.closest('.dropdown');
        const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
        if (dropdownInstance) {
            dropdownInstance.show();
        }
    };

  
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
            if (visibleRows === 0) {
                noDataRow.style.display = '';
            } else {
                noDataRow.style.display = 'none';
            }
        }
    }

    statusFilter.addEventListener('change', applyFilters);
    dateFilter.addEventListener('change', applyFilters);

   
    applyFilters();
});
</script>

<style>
.table {
    font-size: 0.875rem;
}
.table th {
    font-weight: 600;
    color: #6c757d;
    border-top: none;
}
.table td {
    border-color: rgba(0,0,0,0.04);
    padding: 0.75rem 0.5rem;
}
.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
    transition: background-color 0.15s ease;
}
.card {
    transition: box-shadow 0.2s ease;
}
.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1060;
}
.table-responsive {
    overflow: visible;
}
.form-select-sm, .form-control-sm {
    border-radius: 0.375rem;
}
.no-data-row {
    display: table-row;
}
.position-relative {
    position: relative;
}
</style>
@endsection