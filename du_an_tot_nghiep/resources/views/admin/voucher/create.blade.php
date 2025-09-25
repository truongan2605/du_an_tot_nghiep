@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Thêm voucher</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('voucher.store') }}" method="POST">
        @csrf
        @include('admin.voucher.form', ['voucher' => null])
        <button type="submit" class="btn btn-success">Thêm</button>
        <a href="{{ route('voucher.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
