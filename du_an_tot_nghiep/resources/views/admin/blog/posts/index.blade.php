@extends('layouts.admin')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4">Bài viết</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.blog.categories.index') }}" class="btn btn-outline-secondary">Danh mục</a>
                <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary">Thẻ</a>
                <a href="{{ route('admin.blog.posts.trash') }}" class="btn btn-outline-secondary">Thùng rác</a>
                <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary">+ Thêm bài</a>
            </div>
        </div>

        <form class="row g-2 mb-3">
            <div class="col-md-4"><input name="kw" value="{{ request('kw') }}" class="form-control"
                    placeholder="Tìm tiêu đề..."></div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Nháp</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100">Lọc</button></div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tiêu đề</th>
                            <th>Danh mục</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $i => $p)
                            <tr>
                                <td>{{ $posts->firstItem() + $i }}</td>
                                <td><a href="{{ route('admin.blog.posts.edit', $p) }}">{{ $p->title }}</a></td>
                                <td>{{ $p->category?->name ?: '—' }}</td>
                                <td><span
                                        class="badge {{ $p->status === 'published' ? 'bg-success' : 'bg-secondary' }}">{{ $p->status }}</span>
                                </td>
                                <td>{{ $p->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.blog.posts.destroy', $p) }}" method="POST"
                                        onsubmit="return confirm('Chuyển vào thùng rác?')">
                                        @csrf @method('DELETE')
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ route('admin.blog.posts.edit', $p) }}">Sửa</a>
                                        <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $posts->links() }}</div>
        </div>
    </div>
@endsection
