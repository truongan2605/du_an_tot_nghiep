@extends('layouts.app')

@section('title', 'Ưu đãi khách hàng thân thiết')

@section('content')
<div class="container py-4">

    <h3 class="mb-4">🎁 Ưu đãi khách hàng thân thiết</h3>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-2">Hạng hiện tại: Vàng</h5>
            <p>Bạn được giảm <strong>10%</strong> cho mọi đơn đặt phòng.</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold">Tiến độ lên hạng tiếp theo (Kim Cương):</h6>

            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-warning" style="width: 70%;">70%</div>
            </div>

            <p class="text-muted">Bạn cần tiêu thêm 3.000.000đ để lên hạng tiếp theo.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold">Các mức hạng</h6>

            <ul class="list-group">
                <li class="list-group-item">🥈 Bạc – tiêu ≥ 1.000.000đ trong 1 đơn hoàn thành</li>
                <li class="list-group-item">🥇 Vàng – tổng chi tiêu ≥ 15.000.000đ</li>
                <li class="list-group-item">👑 Kim Cương – tổng chi tiêu ≥ 50.000.000đ</li>
            </ul>
        </div>
    </div>

</div>
@endsection
