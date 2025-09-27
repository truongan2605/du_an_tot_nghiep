@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Sửa Voucher</h1>
    <form action="{{ route('voucher.update', $voucher->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.voucher.form')
        <button type="submit" class="btn btn-success">Cập nhật</button>
    </form>
</div>
@endsection
