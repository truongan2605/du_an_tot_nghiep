<p>Dear {{ $user_name }},</p>
<p>Your booking with code {{ $dat_phong->ma_tham_chieu }} has been successfully paid with the amount {{ number_format($tong_tien) }} VND.</p>
<p>Check-in date: {{ $dat_phong->ngay_nhan_phong }}</p>