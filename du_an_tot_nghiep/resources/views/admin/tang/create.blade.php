@extends('layouts.admin')

@section('title', 'Tạo Tầng Mới - Admin Panel')

@section('content')
    <h1 class="mb-4">Tạo Tầng Mới</h1>
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <form action="{{ route('admin.tang.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Số Tầng (Unique)</label>
            <input type="number" name="so_tang" class="form-control" required value="{{ old('so_tang') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Tên Tầng</label>
            <input type="text" name="ten" class="form-control" required value="{{ old('ten') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Ghi Chú (Optional)</label>
            <textarea name="ghi_chu" class="form-control">{{ old('ghi_chu') }}</textarea>
        </div>
        <button type="submit" class="btn btn-success">Tạo</button>
        <a href="{{ route('admin.tang.index') }}" class="btn btn-secondary">Quay Lại</a>
    </form>
@endsection