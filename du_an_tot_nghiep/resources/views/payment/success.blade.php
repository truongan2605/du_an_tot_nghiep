@extends('layouts.app')

@section('content')
<div class="container text-center mt-5">
   
    <div class="card shadow-lg p-5 mx-auto" style="max-width: 500px; border: 2px solid #28a745; border-radius: 15px;">
        <h2 class="text-success mb-4" style="font-weight: 700;">
            <i class="fas fa-check-circle me-2"></i> 🎉 Thanh toán thành công!
        </h2>
        
        <p class="lead mb-4">
            Cảm ơn bạn đã thanh toán. Chi tiết đơn đặt phòng của bạn:
        </p>
        
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <strong>Mã đơn đặt phòng:</strong>
            <span class="badge bg-primary fs-6">#{{ $dat_phong->id ?? 'N/A' }}</span>
        </div>
   
        @if ($dat_phong ?? false)
            <p class="text-muted">
                Chúng tôi đã gửi xác nhận đến email của bạn.
            </p>
        @endif
        
      
        <a href="{{ url('/') }}" class="btn btn-primary mt-4 py-2" style="font-size: 1.1rem; border-radius: 8px;">
            Về trang chủ
        </a>
        <a href="{{ url('account/bookings') }}" class="btn btn-primary mt-4 py-2" style="font-size: 1.1rem; border-radius: 8px;">
            Về trang đặt phòng của bạn 
        </a>
    </div>
</div>
@endsection
