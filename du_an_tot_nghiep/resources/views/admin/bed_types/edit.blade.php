@extends('layouts.admin')

@section('title', 'Sửa loại giường')

@section('content')
<div class="container">
    <h2>Sửa loại giường</h2>

    <form action="{{ route('admin.bed-types.update', $bedType->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Tên</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $bedType->name) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Giới hạn người</label>
            <input type="number" name="capacity" class="form-control" min="1" value="{{ old('capacity', $bedType->capacity) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Giá (VND / đêm)</label>
            <input type="number" step="0.01" name="price" class="form-control" min="0" value="{{ old('price', $bedType->price) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Mô tả</label>
            <textarea name="description" class="form-control">{{ old('description', $bedType->description) }}</textarea>
        </div>

        <button class="btn btn-primary" type="submit">Cập nhật</button>
        <a href="{{ route('admin.bed-types.index') }}" class="btn btn-secondary">Hủy</a>
    </form>
</div>
@endsection
