@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Cập nhật thông báo #{{ $thongBao->id }}</h1>
        <a href="{{ route('admin.thong-bao.show', $thongBao) }}" class="px-3 py-2 border rounded">Xem</a>
    </div>

    <form method="POST" action="{{ route('admin.thong-bao.update', $thongBao) }}" class="bg-white p-4 rounded shadow space-y-4">
        @csrf
        @method('PUT')
        @include('admin.thong-bao.form')
        <div class="pt-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Cập nhật</button>
        </div>
    </form>
</div>
@endsection


