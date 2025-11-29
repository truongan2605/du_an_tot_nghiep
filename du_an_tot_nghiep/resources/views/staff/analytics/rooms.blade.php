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

    {{-- Month/Year Filter --}}
    <div class="card shadow-sm rounded-4 border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('staff.analytics.rooms') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tháng</label>
                    <select name="month" class="form-select">
                        @foreach($monthOptions as $value => $label)
                            <option value="{{ $value }}" @selected($month == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Năm</label>
                    <select name="year" class="form-select">
                        @foreach($yearOptions as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Lọc
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <div class="badge bg-info fs-6 px-3 py-2">
                        {{ $selectedDate->locale('vi')->translatedFormat('F Y') }}
                    </div>
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
