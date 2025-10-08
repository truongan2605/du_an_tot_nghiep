<p>Kính gửi {{ $user_name }},</p>
<p>Booking của bạn với mã {{ $dat_phong->ma_tham_chieu }} đã thanh toán thành công với số tiền {{ number_format($tong_tien) }} VND.</p>
<p>Ngày nhận phòng: {{ $dat_phong->ngay_nhan_phong }}</p>