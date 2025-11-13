@extends('layouts.admin')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4">Danh mục</h1>
            <a href="{{ route('admin.blog.tags.create') }}" class="btn btn-primary">+ Thêm</a>
        </div>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên</th>
                        <th>Slug</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $i => $it)
                        <tr>
                            <td>{{ $items->firstItem() + $i }}</td>
                            <td>{{ $it->name }}</td>
                            <td>{{ $it->slug }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.blog.tags.edit', $it) }}"
                                    class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form action="{{ route('admin.blog.tags.destroy', $it) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Xóa?')">
                                    @csrf @method('DELETE') <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="card-footer">{{ $items->links() }}</div>
        </div>
    </div>
@endsection
