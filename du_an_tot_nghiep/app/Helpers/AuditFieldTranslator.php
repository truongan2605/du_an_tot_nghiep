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
        'role' => 'Vai trò',
        'password' => 'Mật khẩu',
        'avatar' => 'Ảnh đại diện',
        'cccd_front' => 'CCCD mặt trước',
        'cccd_back' => 'CCCD mặt sau',
        'remember_token' => 'Token ghi nhớ',
        'email_verified_at' => 'Email đã xác thực',
        
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
        'notes' => 'Ghi chú',
        'contact_name' => 'Tên liên hệ',
        'contact_phone' => 'SĐT liên hệ',
        'contact_address' => 'Địa chỉ liên hệ',
        'can_xac_nhan' => 'Cần xác nhận',
        'don_vi_tien' => 'Đơn vị tiền',
        'currency' => 'Đơn vị tiền',
        'can_thanh_toan' => 'Cần thanh toán',
        'source' => 'Nguồn',
        'created_by' => 'Người tạo',
        'checked_in_at' => 'Check-in lúc',
        'checked_in_by' => 'Check-in bởi',
        'checkout_at' => 'Checkout lúc',
        'checkout_by' => 'Checkout bởi',
        'is_checkout_early' => 'Checkout sớm',
        'early_checkout_refund_amount' => 'Tiền hoàn checkout sớm',
        'is_late_checkout' => 'Checkout muộn',
        'late_checkout_fee_amount' => 'Phí checkout muộn',
        
        // Cancellation & Refund fields
        'cancellation_reason' => 'Lý do hủy',
        'cancelled_at' => 'Thời điểm hủy',
        'refund_amount' => 'Số tiền hoàn',
        'refund_percentage' => 'Tỷ lệ hoàn tiền',
        'percentage' => 'Tỷ lệ',
        'processed_by' => 'Người xử lý',
        'processed_at' => 'Thời điểm xử lý',
        'requested_at' => 'Yêu cầu lúc',
        'admin_note' => 'Ghi chú quản trị',
        
        // Room fields
        'ten_phong' => 'Tên phòng',
        'ma_phong' => 'Mã phòng',
        'phong_id' => 'Mã phòng',
        'room_id' => 'Mã phòng',
        'old_room_id' => 'Phòng cũ',
        'new_room_id' => 'Phòng mới',
        'loai_phong_id' => 'Loại phòng',
        'room_type_id' => 'Loại phòng',
        'tang_id' => 'Tầng',
        'floor_id' => 'Tầng',
        'gia_mac_dinh' => 'Giá mặc định',
        'default_price' => 'Giá mặc định',
        'gia' => 'Giá',
        'price' => 'Giá',
        'old_price' => 'Giá cũ',
        'new_price' => 'Giá mới',
        'price_difference' => 'Chênh lệch giá',
        'gia_cuoi_cung' => 'Giá cuối cùng',
        'final_price' => 'Giá cuối cùng',
        'gia_tren_dem' => 'Giá trên đêm',
        'tong_gia' => 'Tổng giá',
        'total_price' => 'Tổng giá',
        'suc_chua' => 'Sức chứa',
        'capacity' => 'Sức chứa',
        'so_giuong' => 'Số giường',
        'beds' => 'Số giường',
        'so_luong' => 'Số lượng',
        'quantity' => 'Số lượng',
        'so_dem' => 'Số đêm',
        'nights' => 'Số đêm',
        'dien_tich' => 'Diện tích',
        'area' => 'Diện tích',
        'mo_ta' => 'Mô tả',
        'description' => 'Mô tả',
        'ten_tang' => 'Tên tầng',
        'floor_name' => 'Tên tầng',
        
        // Room change fields
        'change_reason' => 'Lý do đổi phòng',
        'changed_by_type' => 'Người đổi (loại)',
        'changed_by_user_id' => 'Người đổi',
        'payment_info' => 'Thông tin thanh toán',
        
        // Room reservation fields
        'het_han_luc' => 'Hết hạn lúc',
        'expires_at' => 'Hết hạn lúc',
        'released' => 'Đã giải phóng',
        'released_at' => 'Thời điểm giải phóng',
        'released_by' => 'Người giải phóng',
        
        // Payment/Transaction fields
        'phuong_thuc' => 'Phương thức',
        'payment_method' => 'Phương thức thanh toán',
        'method' => 'Phương thức',
        'so_tien' => 'Số tiền',
        'amount' => 'Số tiền',
        'ma_giao_dich' => 'Mã giao dịch',
        'transaction_id' => 'Mã giao dịch',
        'provider_txn_ref' => 'Mã GD nhà cung cấp',
        'nha_cung_cap' => 'Nhà cung cấp',
        'provider' => 'Nhà cung cấp',
        
        // Booking item fields
        'dat_phong_id' => 'Mã đặt phòng',
        'booking_id' => 'Mã đặt phòng',
        'tong_item' => 'Tổng item',
        'addon_id' => 'Dịch vụ thêm',
        
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
        'ma_voucher' => 'Mã voucher',
        'voucher_code' => 'Mã voucher',
        'voucher_id' => 'Voucher',
        'discount_amount' => 'Số tiền giảm',
        'min_total' => 'Tổng tối thiểu',
        'max_discount' => 'Giảm tối đa',
        'usage_limit' => 'Giới hạn sử dụng',
        'used_count' => 'Đã sử dụng',
        'used_at' => 'Sử dụng lúc',
        
        // Amenity (Tiện nghi) fields
        'tien_nghi_id' => 'Tiện nghi',
        'amenity_id' => 'Tiện nghi',
        'icon' => 'Biểu tượng',
        'applies_to_dat_phong_id' => 'Áp dụng cho ĐP',
        
        // Consumable (Vật dụng) fields
        'vat_dung_id' => 'Vật dụng',
        'consumable_id' => 'Vật dụng',
        'don_gia' => 'Đơn giá',
        'unit_price' => 'Đơn giá',
        'consumed_at' => 'Tiêu thụ lúc',
        'billed_at' => 'Tính tiền lúc',
        'incident_type' => 'Loại sự cố',
        'fee' => 'Phí',
        'resolved_at' => 'Giải quyết lúc',
        'resolved_by' => 'Người giải quyết',
        'reported_at' => 'Báo cáo lúc',
        'reported_by' => 'Người báo cáo',
        
        // Blog fields
        'title' => 'Tiêu đề',
        'slug' => 'Đường dẫn',
        'content' => 'Nội dung',
        'excerpt' => 'Trích đoạn',
        'featured_image' => 'Ảnh đại diện',
        'category_id' => 'Danh mục',
        'author_id' => 'Tác giả',
        'published_at' => 'Xuất bản lúc',
        'is_published' => 'Đã xuất bản',
        'views' => 'Lượt xem',
        'post_id' => 'Bài viết',
        'image' => 'Hình ảnh',
        
        // Notification (Thông báo) fields
        'tieu_de' => 'Tiêu đề',
        'noi_dung' => 'Nội dung',
        'loai_thong_bao' => 'Loại thông báo',
        'notification_type' => 'Loại thông báo',
        'is_read' => 'Đã đọc',
        'read_at' => 'Đọc lúc',
        'data' => 'Dữ liệu',
        'payload' => 'Nội dung chi tiết',
        'kenh' => 'Kênh',
        'channel' => 'Kênh',
        'so_lan_thu' => 'Số lần thử',
        'retry_count' => 'Số lần thử',
        'ten_template' => 'Tên template',
        'template_name' => 'Tên template',
        'nguoi_nhan_id' => 'Người nhận',
        'recipient_id' => 'Người nhận',
        
        // Review (Đánh giá) fields
        'diem_so' => 'Điểm số',
        'rating' => 'Điểm đánh giá',
        'binh_luan' => 'Bình luận',
        'comment' => 'Bình luận',
        
        // Bed type fields
        'ten_loai_giuong' => 'Tên loại giường',
        'bed_type' => 'Loại giường',
        
        // Invoice (Hóa đơn) fields
        'ma_hoa_don' => 'Mã hóa đơn',
        'invoice_number' => 'Số hóa đơn',
        'ngay_lap' => 'Ngày lập',
        'invoice_date' => 'Ngày hóa đơn',
        'tong_tien_hang' => 'Tổng tiền hàng',
        'tong_thanh_toan' => 'Tổng thanh toán',
        
        // Other common
        'nguoi_dung_id' => 'Người dùng',
        'user_id' => 'Người dùng',
        'active' => 'Kích hoạt',
        'is_active' => 'Đang hoạt động',
        'order' => 'Thứ tự',
        'position' => 'Vị trí',
        'url' => 'Đường dẫn',
        'link' => 'Liên kết',
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
        // Booking statuses
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
        
        // Room statuses
        'bao_tri' => 'Bảo trì',
        'trong' => 'Trống',
        'ban' => 'Bận',
        'available' => 'Có sẵn',
        'occupied' => 'Đang sử dụng',
        'maintenance' => 'Bảo trì',
        
        // Refund statuses
        'pending' => 'Chờ xử lý',
        'approved' => 'Đã duyệt',
        'completed' => 'Hoàn thành',
        'rejected' => 'Từ chối',
        
        // Payment statuses
        'paid' => 'Đã thanh toán',
        'unpaid' => 'Chưa thanh toán',
        'partial' => 'Thanh toán một phần',
        'refunded' => 'Đã hoàn tiền',
        
        // General statuses
        'active' => 'Hoạt động',
        'inactive' => 'Không hoạt động',
        'draft' => 'Nháp',
        'published' => 'Đã xuất bản',
        'cancelled' => 'Đã hủy',
        'processing' => 'Đang xử lý',
    ];

    protected static $modelMap = [
        // Booking related
        'DatPhong' => 'Đặt phòng',
        'DatPhongItem' => 'Chi tiết đặt phòng',
        'DatPhongAddon' => 'Dịch vụ thêm',
        'GiuPhong' => 'Giữ phòng',
        'RoomChange' => 'Đổi phòng',
        
        // User & Auth
        'User' => 'Người dùng',
        
        // Room & Facilities
        'Phong' => 'Phòng',
        'LoaiPhong' => 'Loại phòng',
        'Tang' => 'Tầng',
        'PhongImage' => 'Hình ảnh phòng',
        'PhongDaDat' => 'Phòng đã đặt',
        'BedType' => 'Loại giường',
        
        // Amenities
        'TienNghi' => 'Tiện nghi',
        'LoaiPhongTienNghi' => 'Tiện nghi loại phòng',
        'PhongTienNghiOverride' => 'Ghi đè tiện nghi phòng',
        
        // Consumables & Incidents
        'VatDung' => 'Vật dụng',
        'PhongVatDung' => 'Vật dụng phòng',
        'PhongVatDungInstance' => 'Thực thể vật dụng',
        'PhongVatDungConsumption' => 'Tiêu thụ vật dụng',
        'VatDungIncident' => 'Sự cố vật dụng',
        
        // Payment & Financial
        'GiaoDich' => 'Giao dịch',
        'HoaDon' => 'Hóa đơn',
        'HoaDonItem' => 'Chi tiết hóa đơn',
        'HoanTien' => 'Hoàn tiền',
        'RefundRequest' => 'Yêu cầu hoàn tiền',
        
        // Voucher
        'Voucher' => 'Voucher',
        'VoucherUsage' => 'Sử dụng voucher',
        'UserVoucher' => 'Voucher người dùng',
        
        // Reviews & Wishlist
        'DanhGia' => 'Đánh giá',
        'Wishlist' => 'Yêu thích',
        
        // Blog
        'BlogPost' => 'Bài viết',
        'BlogCategory' => 'Danh mục bài viết',
        'BlogTag' => 'Thẻ bài viết',
        'BlogPostPhoto' => 'Ảnh bài viết',
        
        // Notifications
        'ThongBao' => 'Thông báo',
        
        // Audit
        'AuditLog' => 'Nhật ký thao tác',
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
