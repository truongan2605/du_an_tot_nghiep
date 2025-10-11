@extends('layouts.admin')

@section('title', 'Thêm loại giường')

@section('content')
<div class="container">
    <h2>Thêm loại giường</h2>

    <form action="{{ route('admin.bed-types.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Giới hạn người</label>
            <input type="number" name="capacity" class="form-control" min="1" value="{{ old('capacity', 1) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Giá (VND / đêm)</label>
            <input type="number" step="0.01" name="price" class="form-control" min="0" value="{{ old('price', 0) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control">{{ old('description') }}</textarea>
        </div>

        <button class="btn btn-success" type="submit">Lưu</button>
        <a href="{{ route('admin.bed-types.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
