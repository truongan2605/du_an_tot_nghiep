@extends('layouts.admin')

@section('title', 'Room Analytics')

@section('content')
<div class="container-fluid px-3 py-4">
    {{-- Header & Filter --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-0 fw-bold text-dark">
                <i class="bi bi-graph-up me-2"></i>Thống Kê Phòng & Booking
            </h2>
            <p class="small text-muted mb-0">Phân tích chi tiết theo loại phòng và từng phòng</p>
        </div>
        <a href="{{ route('staff.analytics.rooms.pdf', ['month' => $month, 'year' => $year]) }}" class="btn btn-danger" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>Export PDF
        </a>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('staff.analytics.rooms') }}" id="filterForm" class="row g-3 align-items-end">
                {{-- Time Range Selection --}}
                <div class="col-12">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="time_range_type" id="timeRangeMonth" value="month" 
                            @if(!$startDate || !$endDate) checked @endif>
                        <label class="btn btn-outline-primary" for="timeRangeMonth">
                            <i class="bi bi-calendar-month me-1"></i>Theo Tháng/Năm
                        </label>
                        <input type="radio" class="btn-check" name="time_range_type" id="timeRangeCustom" value="custom"
                            @if($startDate && $endDate) checked @endif>
                        <label class="btn btn-outline-primary" for="timeRangeCustom">
                            <i class="bi bi-calendar-range me-1"></i>Tùy Chỉnh
                        </label>
                    </div>
                </div>
                
                {{-- Month/Year Filter --}}
                <div class="col-md-3 time-filter-month">
                    <label class="form-label small fw-semibold">Tháng</label>
                    <select name="month" class="form-select">
                        @foreach($monthOptions as $value => $label)
                            <option value="{{ $value }}" @selected($month == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 time-filter-month">
                    <label class="form-label small fw-semibold">Năm</label>
                    <select name="year" class="form-select">
                        @foreach($yearOptions as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Custom Date Range --}}
                <div class="col-md-3 time-filter-custom" style="display: none;">
                    <label class="form-label small fw-semibold">Từ ngày</label>
                    <input type="date" name="start_date" class="form-select" value="{{ $startDate ?? '' }}" max="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3 time-filter-custom" style="display: none;">
                    <label class="form-label small fw-semibold">Đến ngày</label>
                    <input type="date" name="end_date" class="form-select" value="{{ $endDate ?? '' }}" max="{{ now()->format('Y-m-d') }}">
                </div>
                
                {{-- Room Type Filter --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Loại phòng</label>
                    <select name="room_type_id" class="form-select">
                        <option value="">Tất cả loại phòng</option>
                        @foreach($allRoomTypes as $rt)
                            <option value="{{ $rt->id }}" @selected($roomTypeId == $rt->id)>{{ $rt->ten }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Room Filter --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Phòng</label>
                    <select name="room_id" class="form-select" id="roomFilter">
                        <option value="">Tất cả phòng</option>
                        @foreach($allRooms as $r)
                            <option value="{{ $r->id }}" 
                                data-room-type="{{ $r->loai_phong_id }}"
                                @selected($roomId == $r->id)>
                                {{ $r->ma_phong }} - {{ $r->loaiPhong->ten ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Lọc
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('staff.analytics.rooms') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>Xóa
                    </a>
                </div>
                <div class="col-md-4 text-end">
                    @if($startDate && $endDate)
                        <div class="badge bg-info fs-6 px-3 py-2">
                            {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                        </div>
                    @else
                        <div class="badge bg-info fs-6 px-3 py-2">
                            {{ $selectedDate->locale('vi')->translatedFormat('F Y') }}
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Stats by Room Type --}}
    <div class="row g-3 mb-4">
        @forelse($roomTypeStats as $roomType)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-gradient-primary-subtle">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="fw-bold text-primary mb-0">{{ $roomType->ten }}</h5>
                            <span class="badge bg-primary rounded-pill">{{ $roomType->total_rooms }} phòng</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="small text-muted mb-1">Bookings</div>
                                <h4 class="mb-0 fw-bold">{{ $roomType->total_bookings }}</h4>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted mb-1">Tỷ lệ lấp đầy</div>
                                <h4 class="mb-0 fw-bold 
                                    @if($roomType->occupancy_rate >= 70) text-success
                                    @elseif($roomType->occupancy_rate >= 40) text-warning
                                    @else text-danger
                                    @endif">
                                    {{ $roomType->occupancy_rate }}%
                                </h4>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted mb-1">Doanh thu</div>
                                <h5 class="mb-0 text-success fw-bold">{{ number_format($roomType->total_revenue, 0) }} ₫</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning">Không có dữ liệu trong tháng này</div>
            </div>
        @endforelse
    </div>

    {{-- Bar Chart --}}
    @if(count($roomTypeStats) > 0)
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart text-primary me-2"></i>Bookings theo Loại Phòng</h6>
        </div>
        <div class="card-body p-3">
            <div style="position: relative; height: 300px;">
                <canvas id="roomTypeChart"></canvas>
            </div>
        </div>
    </div>
    @endif

    {{-- All Rooms Table --}}
    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-header bg-light border-0 py-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table text-success me-2"></i>Chi Tiết Từng Phòng</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle" id="roomsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">Phòng</th>
                            <th>Loại</th>
                            <th class="text-center">Bookings</th>
                            <th class="text-center">Tỷ lệ (%)</th>
                            <th class="text-end">Doanh thu</th>
                            <th class="text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roomStats as $room)
                            <tr>
                                <td class="px-3 fw-medium">{{ $room->ma_phong }}</td>
                                <td><span class="badge bg-secondary">{{ $room->ten }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill">{{ $room->booking_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge 
                                        @if($room->occupancy_rate >= 70) bg-success
                                        @elseif($room->occupancy_rate >= 40) bg-warning
                                        @else bg-danger
                                        @endif">
                                        {{ $room->occupancy_rate }}%
                                    </span>
                                </td>
                                <td class="text-end text-success fw-medium">{{ number_format($room->revenue, 0) }} ₫</td>
                                <td class="text-center">
                                    <span class="badge 
                                        @if($room->trang_thai == 'trong') bg-success
                                        @elseif($room->trang_thai == 'ban') bg-primary
                                        @else bg-warning text-dark
                                        @endif">
                                        {{ ucfirst($room->trang_thai) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between month/year and custom date range
    const timeRangeRadios = document.querySelectorAll('input[name="time_range_type"]');
    const monthFilters = document.querySelectorAll('.time-filter-month');
    const customFilters = document.querySelectorAll('.time-filter-custom');
    
    timeRangeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'month') {
                monthFilters.forEach(el => el.style.display = 'block');
                customFilters.forEach(el => el.style.display = 'none');
                document.querySelector('input[name="start_date"]').value = '';
                document.querySelector('input[name="end_date"]').value = '';
            } else {
                monthFilters.forEach(el => el.style.display = 'none');
                customFilters.forEach(el => el.style.display = 'block');
            }
        });
    });
    
    // Set initial state based on which radio is checked
    const selectedTimeRange = document.querySelector('input[name="time_range_type"]:checked');
    if (selectedTimeRange) {
        if (selectedTimeRange.value === 'custom') {
            monthFilters.forEach(el => el.style.display = 'none');
            customFilters.forEach(el => el.style.display = 'block');
        } else {
            monthFilters.forEach(el => el.style.display = 'block');
            customFilters.forEach(el => el.style.display = 'none');
        }
    }
    
    // Filter rooms by room type
    const roomTypeSelect = document.querySelector('select[name="room_type_id"]');
    const roomSelect = document.getElementById('roomFilter');
    const allRoomOptions = Array.from(roomSelect.querySelectorAll('option'));
    
    function filterRoomsByType() {
        const selectedRoomTypeId = roomTypeSelect.value;
        
        // Clear current selection if it doesn't match
        if (selectedRoomTypeId && roomSelect.value) {
            const selectedOption = roomSelect.querySelector(`option[value="${roomSelect.value}"]`);
            if (selectedOption && selectedOption.dataset.roomType !== selectedRoomTypeId) {
                roomSelect.value = '';
            }
        }
        
        // Show/hide room options based on room type
        allRoomOptions.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block'; // Always show "Tất cả phòng"
            } else if (!selectedRoomTypeId || option.dataset.roomType === selectedRoomTypeId) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    // Apply filter on change
    roomTypeSelect.addEventListener('change', filterRoomsByType);
    
    // Apply filter on page load if room type is already selected
    if (roomTypeSelect.value) {
        filterRoomsByType();
    }
    
    // Validate date range and clean up form data
    const filterForm = document.getElementById('filterForm');
    filterForm.addEventListener('submit', function(e) {
        const timeRangeType = document.querySelector('input[name="time_range_type"]:checked').value;
        
        if (timeRangeType === 'custom') {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Vui lòng chọn đầy đủ từ ngày và đến ngày');
                return false;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                e.preventDefault();
                alert('Ngày bắt đầu không được lớn hơn ngày kết thúc');
                return false;
            }
            
            // Remove month/year from form if using custom range
            document.querySelector('select[name="month"]').disabled = true;
            document.querySelector('select[name="year"]').disabled = true;
        } else {
            // Remove custom dates from form if using month/year
            document.querySelector('input[name="start_date"]').disabled = true;
            document.querySelector('input[name="end_date"]').disabled = true;
        }
    });
    
    // Room Type Bar Chart
    const chartCanvas = document.getElementById('roomTypeChart');
    if (chartCanvas) {
        const labels = @json($chartLabels);
        const data = @json($chartData);
        
        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Số lượng booking',
                    data: data,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Bookings: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

<style>
.bg-gradient-primary-subtle {
    background: linear-gradient(135deg, #f8f9ff 0%, #e7f0ff 100%);
}
</style>
@endsection
