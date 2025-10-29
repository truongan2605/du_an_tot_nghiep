# 🔧 HƯỚNG DẪN SỬA LỖI MIGRATION

## 🚨 **VẤN ĐỀ:**
Khi chạy `php artisan migrate` trên máy khác, gặp lỗi:
```
SQLSTATE[HY000]: General error: 1553 Cannot drop index 'unique_notification_per_user_template_time': needed in a foreign key constraint
```

## ✅ **GIẢI PHÁP:**

### **Bước 1: Kiểm tra trạng thái migration**
```bash
php artisan migrate:status
```

### **Bước 2: Sửa migration có vấn đề**
Nếu gặp lỗi với migration `2025_10_04_052039_add_phong_id_to_giu_phong_table`:

**Mở file:** `database/migrations/2025_10_04_052039_add_phong_id_to_giu_phong_table.php`

**Thay thế method `up()`:**
```php
public function up()
{
    Schema::table('giu_phong', function (Blueprint $table) {
        if (!Schema::hasColumn('giu_phong', 'phong_id')) {
            $table->unsignedBigInteger('phong_id')->after('loai_phong_id')->nullable();
            $table->foreign('phong_id')->references('id')->on('phong')->onDelete('cascade');
        }
    });
}
```

### **Bước 3: Xóa migration có vấn đề**
```bash
# Xóa migration cũ có vấn đề
rm database/migrations/2025_10_26_083437_remove_unique_constraint_from_thong_bao_table.php
```

### **Bước 4: Chạy migration**
```bash
php artisan migrate
```

## 🎯 **KẾT QUẢ:**
- ✅ Migration chạy thành công
- ✅ Unique constraint được xóa an toàn
- ✅ Foreign key được xử lý đúng cách

## 📝 **GHI CHÚ:**
- Migration `2025_10_27_140324_fix_thong_bao_unique_constraint_issue` sẽ tự động xử lý vấn đề unique constraint
- Không cần lo lắng về việc mất dữ liệu
- Hệ thống sẽ hoạt động bình thường sau khi migration hoàn thành

## 🚀 **SAU KHI MIGRATION:**
- Thông báo có thể được gửi cho nhiều user
- Không còn bị giới hạn bởi unique constraint
- Hệ thống notification hoạt động ổn định
