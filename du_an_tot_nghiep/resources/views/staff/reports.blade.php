@extends('layouts.admin')

@section('title', 'Thống Kê')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                <i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Thống Kê & Báo Cáo
            </h2>
            <p class="text-muted mb-0">Tổng quan hoạt động khách sạn</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <small class="text-muted d-block">Cập nhật lần cuối</small>
                <strong class="text-primary">{{ now()->format('d/m/Y H:i') }}</strong>
            </div>
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Làm mới
            </button>
        </div>
    </div>

    {{-- I. CÁC CHỈ SỐ KPI --}}
    <div class="row g-3 mb-4">
        {{-- 1. Số phòng trống --}}
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
                    <small class="text-white-50">Lễ tân xem để bố trí khách</small>
                </div>
            </div>
        </div>

        {{-- 2. Số phòng đang có khách --}}
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
                    <small class="text-white-50">Theo dõi phòng đang sử dụng</small>
                </div>
            </div>
        </div>

        {{-- 3. Số phòng chờ dọn / bảo trì --}}
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
                    <small class="text-white-50">Quản lý dọn phòng</small>
                </div>
            </div>
        </div>

        {{-- 4. Số đặt phòng hôm nay --}}
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
                    <small class="text-white-50">Xem khách đến trong ngày</small>
                </div>
            </div>
        </div>

        {{-- 5. Tổng doanh thu hôm nay --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu hôm nay</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($tongDoanhThuHomNay ?? 0, 0) }}đ</h2>
                    <small class="text-white-50">Theo dõi thu nhập</small>
                </div>
            </div>
        </div>

        {{-- 6. Doanh thu tháng này --}}
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-white p-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="kpi-icon-wrapper bg-white bg-opacity-20 rounded-circle p-2">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                    <h6 class="mb-1 text-white-50 small fw-normal">Doanh thu tháng này</h6>
                    <h2 class="mb-0 fw-bold">{{ number_format($doanhThuThangNay ?? 0, 0) }}đ</h2>
                    <small class="text-white-50">Phục vụ quản lý</small>
                </div>
            </div>
        </div>
    </div>

    {{-- II. BIỂU ĐỒ --}}
    <div class="row g-4 mb-4">
        {{-- 1. Biểu đồ doanh thu theo ngày trong tháng (Line Chart) --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm chart-card">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2 text-primary"></i>Doanh Thu Theo Ngày Trong Tháng
                        </h5>
                        <span class="badge bg-primary-subtle text-primary">Tháng {{ now()->month }}/{{ now()->year }}</span>
                    </div>
                    <p class="text-muted small mb-0 mt-1">Cho thấy hiệu suất kinh doanh theo thời gian</p>
                </div>
                <div class="card-body p-4">
                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- 2. Biểu đồ trạng thái phòng (Pie Chart) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm chart-card">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Trạng Thái Phòng
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Trống / Đang ở / Chờ dọn</p>
                </div>
                <div class="card-body p-4">
                    <canvas id="roomStatusChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Biểu đồ loại phòng được đặt nhiều nhất (Bar Chart) --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm chart-card">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Loại Phòng Được Đặt Nhiều Nhất
                            </h5>
                            <p class="text-muted small mb-0 mt-1">Thể hiện nhu cầu từng loại phòng</p>
                        </div>
                        <span class="badge bg-info-subtle text-info">Top 10</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <canvas id="roomTypeChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- III. BẢNG DỮ LIỆU --}}
    <div class="row g-4">
        {{-- 1. Danh sách đặt phòng hôm nay --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-calendar-event-fill me-2 text-primary"></i>Đặt Phòng Hôm Nay
                        </h5>
                        <span class="badge bg-primary">{{ $soDatPhongHomNay ?? 0 }} đơn</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Mã đặt</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Khách hàng</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Phòng</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Giờ đến</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Trạng thái</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Số đêm</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($datPhongHomNay ?? [] as $booking)
                                <tr class="table-row-hover">
                                    <td class="px-3 py-2">
                                        <span class="badge bg-primary rounded-pill">{{ $booking->ma_tham_chieu }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $booking->nguoiDung?->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $booking->nguoiDung?->email ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($booking->datPhongItems ?? [] as $item)
                                                <span class="badge bg-info-subtle text-info">{{ $item->phong?->ma_phong ?? $item->loaiPhong?->ten ?? 'N/A' }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $booking->ngay_nhan_phong ? \Carbon\Carbon::parse($booking->ngay_nhan_phong)->format('H:i') : 'N/A' }}
                                        </small>
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $statusLabels = [
                                                'dang_cho' => ['label' => 'Đang chờ', 'class' => 'warning'],
                                                'da_xac_nhan' => ['label' => 'Đã xác nhận', 'class' => 'info'],
                                                'da_gan_phong' => ['label' => 'Đã gán phòng', 'class' => 'primary'],
                                                'dang_su_dung' => ['label' => 'Đang sử dụng', 'class' => 'success'],
                                                'hoan_thanh' => ['label' => 'Hoàn thành', 'class' => 'secondary'],
                                                'da_huy' => ['label' => 'Đã hủy', 'class' => 'danger']
                                            ];
                                            $status = $statusLabels[$booking->trang_thai] ?? ['label' => $booking->trang_thai, 'class' => 'secondary'];
                                        @endphp
                                        <span class="badge bg-{{ $status['class'] }}">{{ $status['label'] }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $soDem = ($booking->datPhongItems ?? collect())->sum('so_dem') ?? 0;
                                        @endphp
                                        <span class="fw-semibold text-dark">{{ $soDem }} đêm</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                        <p class="mb-0">Không có đặt phòng nào hôm nay</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Danh sách phòng theo trạng thái --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-house-fill me-2 text-primary"></i>Phòng Theo Trạng Thái
                        </h5>
                        <span class="badge bg-secondary">{{ count($phongTheoTrangThai ?? []) }} phòng</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Mã phòng</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Loại phòng</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Tầng</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Trạng thái</th>
                                    <th class="px-3 py-2" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Chờ dọn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($phongTheoTrangThai ?? [] as $phong)
                                <tr class="table-row-hover">
                                    <td class="px-3 py-2">
                                        <span class="fw-semibold text-dark">{{ $phong->ma_phong }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-muted">{{ $phong->loaiPhong?->ten ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="badge bg-secondary-subtle text-secondary">Tầng {{ $phong->tang?->so_tang ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $statusLabels = [
                                                'trong' => ['label' => 'Trống', 'class' => 'success'],
                                                'dang_o' => ['label' => 'Đang ở', 'class' => 'primary'],
                                                'bao_tri' => ['label' => 'Bảo trì', 'class' => 'warning'],
                                                'da_dat' => ['label' => 'Đã đặt', 'class' => 'info'],
                                                'khong_su_dung' => ['label' => 'Không sử dụng', 'class' => 'danger']
                                            ];
                                            $status = $statusLabels[$phong->trang_thai] ?? ['label' => $phong->trang_thai, 'class' => 'secondary'];
                                        @endphp
                                        <span class="badge bg-{{ $status['class'] }}">{{ $status['label'] }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($phong->don_dep)
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Cần dọn
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                        <p class="mb-0">Không có dữ liệu phòng</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Chart.js configuration
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = '#6c757d';

    // 1. Biểu đồ doanh thu theo ngày trong tháng (Line Chart)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: @json($revenueLabels ?? []),
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: @json($revenueByDay ?? []),
                borderColor: 'rgb(79, 172, 254)',
                backgroundColor: 'rgba(79, 172, 254, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgb(79, 172, 254)',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 13,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'K';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });

    // 2. Biểu đồ trạng thái phòng (Pie Chart)
    const roomStatusCtx = document.getElementById('roomStatusChart').getContext('2d');
    const roomStatusChart = new Chart(roomStatusCtx, {
        type: 'pie',
        data: {
            labels: @json($roomStatusLabels ?? []),
            datasets: [{
                data: @json($roomStatusData ?? []),
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(0, 123, 255, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgb(40, 167, 69)',
                    'rgb(0, 123, 255)',
                    'rgb(255, 193, 7)',
                    'rgb(108, 117, 125)',
                    'rgb(220, 53, 69)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' phòng (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // 3. Biểu đồ loại phòng được đặt nhiều nhất (Bar Chart)
    const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
    const roomTypeChart = new Chart(roomTypeCtx, {
        type: 'bar',
        data: {
            labels: @json($roomTypeLabels ?? []),
            datasets: [{
                label: 'Số lượng đặt',
                data: @json($roomTypeData ?? []),
                backgroundColor: 'rgba(79, 172, 254, 0.7)',
                borderColor: 'rgba(79, 172, 254, 1)',
                borderWidth: 2,
                borderRadius: 6
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return 'Số lượng đặt: ' + context.parsed.y + ' lần';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>

<style>
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

    /* Chart Cards */
    .chart-card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .chart-card:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }

    /* Table */
    .table-row-hover {
        transition: background-color 0.2s ease;
    }
    
    .table-row-hover:hover {
        background-color: #f8f9fa !important;
    }
    
    .table thead th {
        border-bottom: 2px solid #dee2e6;
    }
    
    .table tbody tr {
        border-bottom: 1px solid #f0f0f0;
    }

    /* Scrollbar styling */
    .table-responsive::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Badge improvements */
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .kpi-card .card-body h2 {
            font-size: 1.5rem;
        }
        
        .kpi-card .card-body h6 {
            font-size: 0.7rem;
        }
    }
</style>
@endsection
