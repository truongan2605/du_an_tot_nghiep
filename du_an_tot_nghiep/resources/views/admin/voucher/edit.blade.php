@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Sửa voucher</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('voucher.update', $voucher->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.voucher.form', ['voucher' => $voucher])
        <button type="submit" class="btn btn-success">Cập nhật</button>
        <a href="{{ route('voucher.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
