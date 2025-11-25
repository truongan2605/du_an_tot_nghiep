@extends('admin.layouts.app')
@section('content')
<div class="p-6 bg-white rounded shadow">
    <h2 class="text-xl font-bold mb-4">Phòng thuộc loại: {{ $loaiPhong->ten_loai }}</h2>

    <table class="min-w-full bg-white border rounded shadow">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="px-4 py-2 border">Tên phòng</th>
                <th class="px-4 py-2 border">Số lượng đánh giá</th>
                <th class="px-4 py-2 border">Rating trung bình</th>
                <th class="px-4 py-2 border">Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach($phongs as $p)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border">{{ $p->ten_phong }}</td>
                <td class="px-4 py-2 border">{{ $p->danhGias()->count() }}</td>
                <td class="px-4 py-2 border">
                    @php
                        $avg = $p->danhGias_avg_rating ?? 0;
                    @endphp
                    <div class="flex items-center">
                        <span class="mr-2">{{ number_format($avg,1) }}/5</span>
                        <div class="flex text-yellow-400">
                            @for($i=1;$i<=5;$i++)
                                @if($i <= round($avg))
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09L5.644 12 .766 7.91l6.057-.88L10 2l3.177 5.03 6.057.88-4.878 4.09 1.522 5.09z"/></svg>
                                @else
                                    <svg class="w-4 h-4 fill-gray-300" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09L5.644 12 .766 7.91l6.057-.88L10 2l3.177 5.03 6.057.88-4.878 4.09 1.522 5.09z"/></svg>
                                @endif
                            @endfor
                        </div>
                    </div>
                </td>
                <td class="px-4 py-2 border">
                    <a href="{{ route('admin.danhgia.show', $p->id) }}" class="text-blue-500 hover:underline">Xem đánh giá</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $phongs->links() }}
    </div>
</div>
@endsection
