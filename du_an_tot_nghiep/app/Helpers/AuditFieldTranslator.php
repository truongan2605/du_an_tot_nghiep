<?php

namespace App\Helpers;

use Carbon\Carbon;

class AuditFieldTranslator
{
    protected static $fieldMap = [
        // Common fields
        'id' => 'ID',
        'created_at' => 'Ngày tạo',
        'updated_at' => 'Ngày cập nhật',
        'deleted_at' => 'Ngày xóa',
        
        // User fields
        'ho_ten' => 'Họ tên',
        'name' => 'Tên',
        'email' => 'Email',
        'sdt' => 'Số điện thoại',
        'phone' => 'Điện thoại',
        'dia_chi' => 'Địa chỉ',
        'address' => 'Địa chỉ',
        'vai_tro' => 'Vai trò',
        'password' => 'Mật khẩu',
        
        // Booking fields  
        'trang_thai' => 'Trạng thái',
        'status' => 'Trạng thái',
        'ma_tham_chieu' => 'Mã tham chiếu',
        'ngay_nhan_phong' => 'Ngày nhận phòng',
        'ngay_tra_phong' => 'Ngày trả phòng',
        'so_khach' => 'Số khách',
        'tong_tien' => 'Tổng tiền',
        'total_amount' => 'Tổng tiền',
        'deposit_amount' => 'Tiền đặt cọc',
        'deposit_percentage' => '% Đặt cọc',
        'ghi_chu' => 'Ghi chú',
        'note' => 'Ghi chú',
        'contact_name' => 'Tên liên hệ',
        'contact_phone' => 'SĐT liên hệ',
        'contact_address' => 'Địa chỉ liên hệ',
        
        // Room fields
        'ten_phong' => 'Tên phòng',
        'ma_phong' => 'Mã phòng',
        'loai_phong_id' => 'Loại phòng',
        'tang_id' => 'Tầng',
        'gia_mac_dinh' => 'Giá mặc định',
        'gia_cuoi_cung' => 'Giá cuối cùng',
        'suc_chua' => 'Sức chứa',
        'so_giuong' => 'Số giường',
        'dien_tich' => 'Diện tích',
        'mo_ta' => 'Mô tả',
        'description' => 'Mô tả',
        
        // Payment fields
        'phuong_thuc' => 'Phương thức',
        'payment_method' => 'Phương thức thanh toán',
        'so_tien' => 'Số tiền',
        'amount' => 'Số tiền',
        'ma_giao_dich' => 'Mã giao dịch',
        'transaction_id' => 'Mã giao dịch',
        
        // Voucher fields
        'code' => 'Mã',
        'ten' => 'Tên',
        'gia_tri' => 'Giá trị',
        'value' => 'Giá trị',
        'loai' => 'Loại',
        'type' => 'Loại',
        'ngay_bat_dau' => 'Ngày bắt đầu',
        'start_date' => 'Ngày bắt đầu',
        'ngay_ket_thuc' => 'Ngày kết thúc',
        'end_date' => 'Ngày kết thúc',
        
        // Other common
        'nguoi_dung_id' => 'Người dùng',
        'user_id' => 'Người dùng',
        'active' => 'Kích hoạt',
        'is_active' => 'Đang hoạt động',
    ];

    protected static $statusMap = [
        'dang_cho' => 'Đang chờ',
        'dang_cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'da_huy' => 'Đã hủy',
        'hoan_thanh' => 'Hoàn thành',
        'dang_su_dung' => 'Đang sử dụng',
        'that_bai' => 'Thất bại',
        'thanh_cong' => 'Thành công',
        'cho_xu_ly' => 'Chờ xử lý',
        'dang_xu_ly' => 'Đang xử lý',
        'khong_su_dung' => 'Không sử dụng',
        'bao_tri' => 'Bảo trì',
        'trong' => 'Trống',
        'ban' => 'Bận',
    ];

    protected static $modelMap = [
        'DatPhong' => 'Đặt phòng',
        'User' => 'Người dùng',
        'Phong' => 'Phòng',
        'GiaoDich' => 'Giao dịch',
        'Voucher' => 'Voucher',
        'LoaiPhong' => 'Loại phòng',
        'Tang' => 'Tầng',
        'TienNghi' => 'Tiện nghi',
        'DatPhongItem' => 'Chi tiết đặt phòng',
        'GiuPhong' => 'Giữ phòng',
    ];

    public static function translateField($field)
    {
        return self::$fieldMap[$field] ?? $field;
    }

    public static function translateValue($field, $value)
    {
        if ($value === null || $value === '') {
            return '—';
        }

        // Translate status values
        if (in_array($field, ['trang_thai', 'status']) && isset(self::$statusMap[$value])) {
            return self::$statusMap[$value];
        }

        // Format dates
        if (str_contains($field, 'date') || str_contains($field, 'ngay') || in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
            try {
                return Carbon::parse($value)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Format money
        if (in_array($field, ['tong_tien', 'so_tien', 'gia', 'deposit_amount', 'amount', 'gia_mac_dinh', 'gia_cuoi_cung', 'gia_tri', 'value']) && is_numeric($value)) {
            return number_format($value, 0, ',', '.') . ' ₫';
        }

        // Format boolean
        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        // Format deposit percentage
        if ($field === 'deposit_percentage' && is_numeric($value)) {
            return $value . '%';
        }

        return $value;
    }

    public static function translateModel($modelClass)
    {
        $basename = class_basename($modelClass);
        return self::$modelMap[$basename] ?? $basename;
    }

    public static function getEventBadgeClass($event)
    {
        return match($event) {
            'created' => 'bg-success',
            'updated' => 'bg-primary',
            'deleted' => 'bg-danger',
            'restored' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    public static function getEventIcon($event)
    {
        return match($event) {
            'created' => 'fa-plus-circle',
            'updated' => 'fa-edit',
            'deleted' => 'fa-trash-alt',
            'restored' => 'fa-undo',
            default => 'fa-circle'
        };
    }

    public static function getEventLabel($event)
    {
        return match($event) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'restored' => 'Khôi phục',
            default => ucfirst($event)
        };
    }
}
