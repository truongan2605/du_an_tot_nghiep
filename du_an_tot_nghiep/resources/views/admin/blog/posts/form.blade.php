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

    {{-- Upload anh trong noi dung cua bai viet --}}
    {{-- @push('scripts')
        <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#editor',
                height: 520,
                menubar: false,
                plugins: 'image link media code lists table paste autolink',
                toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | code',
                paste_data_images: true,
                relative_urls: false,
                remove_script_host: false,
                document_base_url: '{{ config('app.url') }}/',
                setup: (editor) => {
                    editor.on('change', () => editor.save());
                },
                images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route('admin.blog.posts.upload-image') }}');
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                    xhr.upload.onprogress = (e) => progress(e.loaded / e.total * 100);

                    xhr.onload = () => {
                        if (xhr.status < 200 || xhr.status >= 300) return reject('HTTP Error: ' + xhr
                            .status);
                        let json = {};
                        try {
                            json = JSON.parse(xhr.responseText);
                        } catch (e) {}
                        if (!json || typeof json.location !== 'string') return reject('Invalid JSON: ' + xhr
                            .responseText);
                        resolve(json.location);
                    };

                    xhr.onerror = () => reject('Image upload failed due to a XHR Transport error.');

                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                }),
            });
        </script>
    @endpush --}}
@endsection
