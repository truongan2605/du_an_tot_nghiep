
@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Chi tiết voucher</h1>

    <div class="card">
        <div class="card-body">
            <p><strong>Mã:</strong> {{ $voucher->code }}</p>
            <p><strong>Loại:</strong> {{ $voucher->type }}</p>
            <p><strong>Giá trị:</strong> {{ $voucher->value }}</p>
            <p><strong>Ngày bắt đầu:</strong> {{ $voucher->start_date }}</p>
            <p><strong>Ngày kết thúc:</strong> {{ $voucher->end_date }}</p>
        </div>
    </div>

    <a href="{{ route('voucher.index') }}" class="btn btn-secondary mt-3">Quay lại</a>
</div>
@endsection
