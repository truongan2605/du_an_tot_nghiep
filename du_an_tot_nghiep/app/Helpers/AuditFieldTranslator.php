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
        'meta' => 'Dữ liệu bổ sung',
        
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
        'can_xac_nhan' => 'Cần xác nhận',
        
        // Cancellation & Refund fields
        'cancellation_reason' => 'Lý do hủy',
        'cancelled_at' => 'Thời điểm hủy',
        'refund_amount' => 'Số tiền hoàn',
        'refund_percentage' => 'Tỷ lệ hoàn tiền',
        'processed_by' => 'Người xử lý',
        'processed_at' => 'Thời điểm xử lý',
        
        // Room fields
        'ten_phong' => 'Tên phòng',
        'ma_phong' => 'Mã phòng',
        'phong_id' => 'Mã phòng',
        'room_id' => 'Mã phòng',
        'loai_phong_id' => 'Loại phòng',
        'tang_id' => 'Tầng',
        'gia_mac_dinh' => 'Giá mặc định',
        'gia_cuoi_cung' => 'Giá cuối cùng',
        'gia_tren_dem' => 'Giá trên đêm',
        'suc_chua' => 'Sức chứa',
        'so_giuong' => 'Số giường',
        'so_luong' => 'Số lượng',
        'so_dem' => 'Số đêm',
        'dien_tich' => 'Diện tích',
        'mo_ta' => 'Mô tả',
        'description' => 'Mô tả',
        
        // Room reservation fields
        'het_han_luc' => 'Hết hạn lúc',
        'released' => 'Đã giải phóng',
        'released_at' => 'Thời điểm giải phóng',
        'released_by' => 'Người giải phóng',
        
        // Payment fields
        'phuong_thuc' => 'Phương thức',
        'payment_method' => 'Phương thức thanh toán',
        'so_tien' => 'Số tiền',
        'amount' => 'Số tiền',
        'ma_giao_dich' => 'Mã giao dịch',
        'transaction_id' => 'Mã giao dịch',
        'provider_txn_ref' => 'Mã GD nhà cung cấp',
        
        // Booking item fields
        'dat_phong_id' => 'Mã đặt phòng',
        'booking_id' => 'Mã đặt phòng',
        'tong_item' => 'Tổng item',
        
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

    /**
     * Technical fields that should be hidden from audit display
     * These are internal/system fields that don't provide value to end users
     */
    protected static $blacklistedFields = [
        'id',
        'created_at',
        'updated_at', 
        'deleted_at',
        'spec_signature_hash',
        'snapshot_meta',
        'snapshot_total',
        'snapshot_rooms',
        'snapshot_addons',
        'remember_token',
        'email_verified_at',
        'password',
        'ip_address',
        'user_agent',
        'session_id',
        'properties', // Audit log internal field
        'old_values', // Raw old values
        'new_values', // Raw new values
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

        // Format dates (bao gồm cả các trường _at và het_han_luc)
        if (str_contains($field, 'date') || str_contains($field, 'ngay') || str_contains($field, '_at') || 
            in_array($field, ['created_at', 'updated_at', 'deleted_at', 'cancelled_at', 'processed_at', 'released_at', 'het_han_luc'])) {
            try {
                return Carbon::parse($value)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Format money (bao gồm cả refund_amount và gia_tren_dem)
        if (in_array($field, ['tong_tien', 'so_tien', 'gia', 'deposit_amount', 'amount', 'gia_mac_dinh', 'gia_cuoi_cung', 'gia_tri', 'value', 'refund_amount', 'gia_tren_dem']) && is_numeric($value)) {
            return number_format($value, 0, ',', '.') . ' ₫';
        }

        // Format boolean (bao gồm can_xac_nhan và released)
        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }
        
        // Handle numeric boolean (0 or 1) cho các trường như can_xac_nhan, released
        if (in_array($field, ['can_xac_nhan', 'released', 'is_active', 'active']) && ($value === 0 || $value === 1 || $value === '0' || $value === '1')) {
            return $value ? 'Có' : 'Không';
        }

        // Format percentages (deposit_percentage và refund_percentage)
        if (in_array($field, ['deposit_percentage', 'refund_percentage']) && is_numeric($value)) {
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

    /**
     * Check if a field should be hidden from audit display
     */
    public static function isBlacklisted($field)
    {
        return in_array($field, self::$blacklistedFields);
    }
}
