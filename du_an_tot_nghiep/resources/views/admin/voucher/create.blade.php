@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Thêm Voucher</h1>
    <form action="{{ route('admin.voucher.store') }}" method="POST">
        @include('admin.voucher.form')
        <button type="submit" class="btn btn-success">Lưu</button>
    </form>
</div>
@endsection
