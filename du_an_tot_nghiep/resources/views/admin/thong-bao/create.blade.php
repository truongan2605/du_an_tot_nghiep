@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Tạo thông báo</h1>
        <a href="{{ route('admin.thong-bao.index') }}" class="px-3 py-2 border rounded">Quay lại</a>
    </div>

    <form method="POST" action="{{ route('admin.thong-bao.store') }}" class="bg-white p-4 rounded shadow space-y-4">
        @csrf
        @include('admin.thong-bao.form')
        <div class="pt-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Lưu</button>
        </div>
    </form>
</div>
@endsection


