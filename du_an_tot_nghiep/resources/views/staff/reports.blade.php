@extends('layouts.admin')

@section('title', 'Báo Cáo Nhân Viên')

@section('content')
<div class="p-4">
    <h2 class="mb-4 fw-bold text-dark">Báo Cáo Nhân Viên</h2>

    <div class="row g-4">
      
        <div class="col-md-4">
            <div class="card shadow-lg border-0 text-white report-card" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrapper bg-white text-primary rounded-circle d-flex justify-content-center align-items-center" style="width:50px; height:50px;">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-white mb-1">Doanh Thu Tháng</h6>
                        <h3 class="fw-bold">{{ number_format($monthlyRevenue, 0) }} VND</h3>
                    </div>
                </div>
            </div>
        </div>

      
        <div class="col-md-4">
            <div class="card shadow-lg border-0 text-white report-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrapper bg-white text-success rounded-circle d-flex justify-content-center align-items-center" style="width:50px; height:50px;">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-white mb-1">Booking Tháng</h6>
                        <h3 class="fw-bold">{{ $bookingsThisMonth }}</h3>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="card shadow-lg border-0 text-white report-card" style="background: linear-gradient(135deg, #ff7e5f, #feb47b);">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrapper bg-white text-danger rounded-circle d-flex justify-content-center align-items-center" style="width:50px; height:50px;">
                        <i class="bi bi-house fs-4"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-white mb-1">Phòng Trống</h6>
                        <h3 class="fw-bold">{{ $availableRooms }}</h3>
                    </div>
                </div>
            </div>
        </div>

      
        <div class="col-md-4">
            <div class="card shadow-lg border-0 text-white report-card" style="background: linear-gradient(135deg, #ff7e5f, #feb47b);">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-wrapper bg-white text-danger rounded-circle d-flex justify-content-center align-items-center" style="width:50px; height:50px;">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-white mb-1">Doanh Thu Đặt Cọc Tháng</h6>
                        <h3 class="fw-bold">{{ number_format($monthlyDeposit ?? 0, 0) }} VND</h3>
                    </div>
                </div>
            </div>
        </div>
    </div> 

 
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header fw-bold">Biểu Đồ Doanh Thu & Booking</div>
                <div class="card-body">
                    <canvas id="reportChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .report-card {
        border-radius: 1rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.25);
    }
    .icon-wrapper i {
        font-size: 1.4rem;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    const reportChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4'],
            datasets: [
                {
                    label: 'Doanh Thu', 
                    data: @json($weeklyRevenue ?? []),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderRadius: 5
                },
                {
                    label: 'Doanh Thu Đặt Cọc', 
                    data: @json($weeklyDeposit ?? []),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderRadius: 5
                },
                {
                    label: 'Booking',
                    data: @json($weeklyBookings),
                    backgroundColor: 'rgba(255, 206, 86, 0.7)',
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { stacked: false },
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endsection