@extends('layouts.admin')
@section('content')
    @php($isEdit = $item->exists)
    <div class="container-fluid">
        <h1 class="h4 mb-3">{{ $isEdit ? 'Sửa danh mục' : 'Thêm danh mục' }}</h1>
        <form method="POST"
            action="{{ $isEdit ? route('admin.blog.tags.update', $item) : route('admin.blog.tags.store') }}">
            @csrf @if ($isEdit)
                @method('PUT')
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Tên</label>
                        <input name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Slug (tùy chọn)</label>
                        <input name="slug" class="form-control" value="{{ old('slug', $item->slug) }}">
                    </div>
                    <button class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </form>
    </div>
@endsection
