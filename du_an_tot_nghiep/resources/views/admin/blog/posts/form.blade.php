@extends('layouts.admin')
@section('content')
    @php($isEdit = $post->exists)
    <div class="container-fluid">
        <h1 class="h4 mb-3">{{ $isEdit ? 'Sửa bài viết' : 'Thêm bài viết' }}</h1>
        <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary">← Quay lại</a>

        <form method="POST" enctype="multipart/form-data"
            action="{{ $isEdit ? route('admin.blog.posts.update', $post) : route('admin.blog.posts.store') }}">
            @csrf @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Tiêu đề</label>
                                <input name="title" class="form-control" value="{{ old('title', $post->title) }}"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug (bỏ trống để tự tạo)</label>
                                <input name="slug" class="form-control" value="{{ old('slug', $post->slug) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tóm tắt</label>
                                {{-- ✅ tên đúng là excerpt --}}
                                <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea id="editor" name="content" class="form-control" rows="12">{{ old('content', $post->content) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="draft" {{ old('status', $post->status) == 'draft' ? 'selected' : '' }}>
                                        Nháp
                                    </option>
                                    <option value="published"
                                        {{ old('status', $post->status) == 'published' ? 'selected' : '' }}>
                                        Xuất bản</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category_id" class="form-select">
                                    <option value="">— Không —</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}"
                                            {{ (int) old('category_id', $post->category_id) === $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Thẻ</label>
                                <select name="tags[]" class="form-select" multiple>
                                    @php($sel = old('tags', $post->exists ? $post->tags->pluck('id')->all() : []))
                                    @foreach ($tags as $t)
                                        <option value="{{ $t->id }}"
                                            {{ in_array($t->id, $sel) ? 'selected' : '' }}>
                                            {{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ảnh cover</label>
                                {{-- ✅ tên đúng là cover_image --}}
                                <input type="file" name="cover_image" class="form-control">
                                @if ($post->cover_image)
                                    <img src="{{ asset('storage/' . $post->cover_image) }}" class="img-fluid rounded mt-2"
                                        alt="">
                                @endif
                            </div>

                            {{-- [NEW] Album hình ảnh nhiều ảnh --}}
                            <div class="mb-3">
                                <label class="form-label">Album hình ảnh (có thể chọn nhiều)</label>
                                <input type="file" name="photo_albums[]" class="form-control" multiple accept="image/*">

                                @if ($post->photoAlbums && $post->photoAlbums->count())
                                    <div class="row mt-2 g-2">
                                        @foreach ($post->photoAlbums as $photo)
                                            <div class="col-4 position-relative">
                                                <img src="{{ asset('storage/' . $photo->image) }}"
                                                    class="img-fluid rounded shadow-sm" alt="">
                                                {{-- <form action="{{ route('admin.blog.posts.delete-photo', $photo->id) }}"
                                                    method="POST" class="position-absolute top-0 end-0"
                                                    onsubmit="return confirm('Xóa ảnh này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger m-1 p-1">
                                                        Xóa ảnh <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form> --}}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Title</label>
                                {{-- ✅ meta_title --}}
                                <input name="meta_title" class="form-control"
                                    value="{{ old('meta_title', $post->meta_title) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Description</label>
                                {{-- ✅ meta_description --}}
                                <input name="meta_description" class="form-control"
                                    value="{{ old('meta_description', $post->meta_description) }}">
                            </div>

                            <button class="btn btn-primary w-100">{{ $isEdit ? 'Cập nhật' : 'Tạo bài' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection
