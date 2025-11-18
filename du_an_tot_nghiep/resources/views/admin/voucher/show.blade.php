@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Chi tiết Voucher</h1>
    <p><b>Tên:</b> {{ $voucher->name }}</p>
    <p><b>Mã:</b> {{ $voucher->code }}</p>
    <p><b>Loại:</b> {{ $voucher->type }}</p>
    <p><b>Giá trị:</b> {{ $voucher->value }}</p>
    <p><b>Số lượng:</b> {{ $voucher->qty }}</p>
    <p><b>Lượt/Người:</b> {{ $voucher->usage_limit_per_user }}</p>
    <p><b>Ngày bắt đầu:</b> {{ $voucher->start_date }}</p>
    <p><b>Ngày kết thúc:</b> {{ $voucher->end_date }}</p>
    <a href="{{ route('admin.voucher.index') }}" class="btn btn-secondary">Quay lại</a>
</div>
@endsection
