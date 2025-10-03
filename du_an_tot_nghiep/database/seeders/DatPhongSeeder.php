<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\DatPhong;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatPhongSeeder extends Seeder
{
    /**
     * Chạy các hạt giống cơ sở dữ liệu để tạo các bản ghi Đặt Phòng mẫu.
     */
    public function run(): void
    {
        
        $khachHang = User::where('email', 'customer@example.com')->first();
        $nhanVien = User::where('email', 'staff@hotel.com')->first();
        
        
        if (!$khachHang || !$nhanVien) {
            $this->command->warn('Không tìm thấy người dùng (customer@example.com hoặc staff@hotel.com). Vui lòng chạy HotelSeeder hoặc kiểm tra lại email.');
            return;
        }

        $khachHangId = $khachHang->id;
        $nhanVienId = $nhanVien->id;


        $tongTienCoBan = 1500000.00;
        $giamGia = 150000.00;
        
        // 2. Đặt phòng Đã Xác Nhận (trong tương lai) - Thanh toán Chuyển khoản
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO025A'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'da_xac_nhan',
                'ngay_nhan_phong' => Carbon::now()->addDays(5),
                'ngay_tra_phong' => Carbon::now()->addDays(7),
                'so_khach' => 2,
                'tong_tien' => $tongTienCoBan,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'chuyen_khoan', 
                'discount_amount' => $giamGia,
                'snapshot_total' => $tongTienCoBan - $giamGia,
                'source' => 'web', // Hợp lệ theo ENUM
                'ghi_chu' => 'Yêu cầu phòng tầng cao.',
            ]
        );

        // 3. Đặt phòng Hoàn Thành (trong quá khứ) - Thanh toán Tiền mặt
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK2025B'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'hoan_thanh',
                'ngay_nhan_phong' => Carbon::now()->subDays(10),
                'ngay_tra_phong' => Carbon::now()->subDays(7),
                'so_khach' => 1,
                'tong_tien' => 900000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => false, 
                'created_by' => $nhanVienId, 
                'phuong_thuc' => 'tien_mat', 
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 900000.00,
                'source' => 'staff',
                'ghi_chu' => 'Khách hàng cũ, đã thanh toán tại quầy.',
            ]
        );

        // 4. Đặt phòng Đang Chờ (chờ xác nhận) - Thanh toán VNPay
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK002025C'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK051021'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK0510'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK0510'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK0510'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05102025C'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BO'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK051020'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
        DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05102'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOOK05102'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOK05'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO5102'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO51021000'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO510210001'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
         DatPhong::firstOrCreate(
            ['ma_tham_chieu' => 'BOO510210001TA'],
            [
                'nguoi_dung_id' => $khachHangId,
                'trang_thai' => 'dang_cho',
                'ngay_nhan_phong' => Carbon::now()->addDays(15),
                'ngay_tra_phong' => Carbon::now()->addDays(17),
                'so_khach' => 3,
                'tong_tien' => 3200000.00,
                'don_vi_tien' => 'VND',
                'can_thanh_toan' => true,
                'created_by' => $khachHangId,
                'phuong_thuc' => 'vnpay', // Hợp lệ theo ENUM
                'ma_voucher' => null,
                'discount_amount' => 0.00,
                'snapshot_total' => 3200000.00,
                'source' => 'web',
                'ghi_chu' => 'Khách yêu cầu xác nhận gấp.',
            ]
        );
    }
}
