@extends('layouts.app')

@section('content')
<div class="container text-center mt-5">
    <h2 class="text-danger">❌ Thanh toán thất bại!</h2>
    <p>{{ $message ?? 'Giao dịch không thành công.' }}</p>
    <p>Mã lỗi: {{ $code ?? 'N/A' }}</p>
    <a href="/" class="btn btn-secondary mt-3">Thử lại</a>
</div>
@endsection
