@extends('layouts.admin')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4">Thùng rác</h1>
            <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary">← Quay lại</a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tiêu đề</th>
                            <th>Xóa lúc</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $i => $p)
                            <tr>
                                <td>{{ $posts->firstItem() + $i }}</td>
                                <td>{{ $p->title }}</td>
                                <td>{{ $p->deleted_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.blog.posts.restore', $p->id) }}" method="POST"
                                        class="d-inline">@csrf
                                        <button class="btn btn-sm btn-outline-success">Khôi phục</button>
                                    </form>
                                    <form action="{{ route('admin.blog.posts.force', $p->id) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Xóa vĩnh viễn</button>
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
