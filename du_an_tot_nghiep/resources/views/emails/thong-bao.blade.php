<p>Xin chào {{ optional($thongBao->nguoiNhan)->name }},</p>

<p>{{ $thongBao->payload['message'] ?? 'Bạn có thông báo mới.' }}</p>

@isset($thongBao->payload['link'])
<p><a href="{{ $thongBao->payload['link'] }}">Xem chi tiết</a></p>
@endisset

<p>Trân trọng,</p>
<p>Hệ thống</p>




