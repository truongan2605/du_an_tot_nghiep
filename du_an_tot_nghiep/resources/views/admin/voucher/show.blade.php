@extends('layouts.admin')

@section('content')
    <div class="container">
        <h1>Chi tiết Voucher</h1>
        <p><b>Tên:</b> {{ $voucher->name }}</p>
        <p><b>Mã:</b> {{ $voucher->code }}</p>
        <p><b>Loại:</b> {{ $voucher->type }}</p>
        <p><b>Giá trị:</b> {{ $voucher->gia_tri_hien_thi ?? $voucher->value }}</p>
        <p><b>Điểm đổi:</b> {{ $voucher->points_required ?? '-' }}</p>
        <p><b>Số lượng:</b> {{ $voucher->qty }}</p>
        <p><b>Lượt/Người:</b> {{ $voucher->usage_limit_per_user }}</p>
        <p><b>Ngày bắt đầu:</b> {{ optional($voucher->start_date)->format('Y-m-d') }}</p>
        <p><b>Ngày kết thúc:</b> {{ optional($voucher->end_date)->format('Y-m-d') }}</p>
        <a href="{{ route('admin.voucher.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
@endsection
