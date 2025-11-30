@extends('layouts.app')

@section('title', 'Ưu đãi khách hàng thân thiết')

@section('content')
<div class="container py-4">

    <h3 class="mb-4">🎁 Ưu đãi khách hàng thân thiết</h3>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-2">Hạng hiện tại: {{ $currentLevel }}</h5>
            <p>Bạn được giảm <strong>{{ number_format($currentDiscount, 1) }}%</strong> cho mọi đơn đặt phòng.</p>
            <p class="text-muted mb-0">Tổng chi tiêu: <strong>{{ number_format($totalSpent, 0, ',', '.') }}đ</strong></p>
        </div>
    </div>

    @if($nextLevelInfo['name'])
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold">Tiến độ lên hạng tiếp theo ({{ $nextLevelInfo['name'] }}):</h6>

            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $progressPercent }}%;" 
                     aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100">
                    {{ number_format($progressPercent, 1) }}%
                </div>
            </div>

            <p class="text-muted mb-0">
                Bạn cần tiêu thêm <strong>{{ number_format($nextLevelInfo['remaining'], 0, ',', '.') }}đ</strong> 
                để lên hạng {{ $nextLevelInfo['name'] }} (giảm {{ number_format($nextLevelInfo['discount'], 1) }}%).
            </p>
            <small class="text-muted">
                Hiện tại: {{ number_format($nextLevelInfo['current'], 0, ',', '.') }}đ / 
                {{ number_format($nextLevelInfo['required'], 0, ',', '.') }}đ
            </small>
        </div>
    </div>
    @else
    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-success mb-0">
                <h6 class="fw-bold mb-2">🎉 Bạn đã đạt hạng cao nhất!</h6>
                <p class="mb-0">Bạn đang ở hạng Kim Cương và được hưởng mức giảm giá tối đa 15% cho mọi đơn đặt phòng.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold">Các mức hạng</h6>

            <ul class="list-group">
                <li class="list-group-item {{ $currentLevel == 'Đồng' ? 'active' : '' }}">
                    🥉 <strong>Đồng</strong> – Giảm 3% (mặc định)
                </li>
                <li class="list-group-item {{ $currentLevel == 'Bạc' ? 'active' : '' }}">
                    🥈 <strong>Bạc</strong> – Giảm 5% (tiêu ≥ 1.000.000đ trong 1 đơn hoàn thành hoặc tổng chi tiêu ≥ 1.000.000đ)
                </li>
                <li class="list-group-item {{ $currentLevel == 'Vàng' ? 'active' : '' }}">
                    🥇 <strong>Vàng</strong> – Giảm 10% (tổng chi tiêu ≥ 15.000.000đ)
                </li>
                <li class="list-group-item {{ $currentLevel == 'Kim Cương' ? 'active' : '' }}">
                    👑 <strong>Kim Cương</strong> – Giảm 15% + Ưu tiên hỗ trợ khách hàng (tổng chi tiêu ≥ 50.000.000đ)
                </li>
            </ul>
        </div>
    </div>

</div>
@endsection
