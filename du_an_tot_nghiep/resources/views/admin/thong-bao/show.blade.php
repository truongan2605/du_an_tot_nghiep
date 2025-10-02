@extends('layouts.admin')

@section('content')
<div class="container mx-auto p-4 space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold">Thông báo #{{ $thongBao->id }}</h1>
        <div class="space-x-2">
            <a href="{{ route('admin.thong-bao.edit', $thongBao) }}" class="px-3 py-2 border rounded">Sửa</a>
            <a href="{{ route('admin.thong-bao.index') }}" class="px-3 py-2 border rounded">Danh sách</a>
        </div>
    </div>

    <div class="bg-white shadow rounded p-4">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-gray-500 text-sm">Người nhận</dt>
                <dd class="font-medium">{{ optional($thongBao->nguoiNhan)->name }} (ID: {{ $thongBao->nguoi_nhan_id }})</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Kênh</dt>
                <dd class="font-medium">{{ $thongBao->kenh }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Template</dt>
                <dd class="font-medium">{{ $thongBao->ten_template }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Trạng thái</dt>
                <dd class="font-medium">{{ $thongBao->trang_thai }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Số lần thử</dt>
                <dd class="font-medium">{{ $thongBao->so_lan_thu ?? 0 }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 text-sm">Lần thử cuối</dt>
                <dd class="font-medium">{{ $thongBao->lan_thu_cuoi?->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-gray-500 text-sm">Payload</dt>
                <dd>
                    <pre class="bg-gray-50 p-3 rounded overflow-auto text-xs">{{ json_encode($thongBao->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection


