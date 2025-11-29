-- Thêm cột checked_in_by vào bảng dat_phong
-- Chạy script SQL này trong phpMyAdmin hoặc MySQL client nếu artisan migrate không hoạt động

ALTER TABLE `dat_phong` 
ADD COLUMN `checked_in_by` BIGINT UNSIGNED NULL AFTER `checked_in_at`;

ALTER TABLE `dat_phong` 
ADD CONSTRAINT `dat_phong_checked_in_by_foreign` 
FOREIGN KEY (`checked_in_by`) 
REFERENCES `users`(`id`) 
ON DELETE SET NULL;
