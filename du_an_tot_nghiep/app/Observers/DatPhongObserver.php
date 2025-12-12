<?php

namespace App\Observers;

use App\Models\DatPhong;

class DatPhongObserver
{
    /**
     * Handle the DatPhong "created" event.
     */
    public function created(DatPhong $datPhong): void
    {
        //
    }

    /**
     * Handle the DatPhong "updated" event.
     */
    public function updated(DatPhong $datPhong): void
    {
        // Kiểm tra nếu booking vừa được đánh dấu là hoàn thành
        if ($datPhong->isDirty('trang_thai') && $datPhong->trang_thai === 'hoan_thanh') {
            $this->updateUserLoyaltyStatus($datPhong);
        }
    }

    /**
     * Cập nhật total_spent và hạng thành viên cho user khi booking hoàn thành
     */
    private function updateUserLoyaltyStatus(DatPhong $datPhong): void
    {
        if (!$datPhong->nguoi_dung_id) {
            return;
        }

        $user = \App\Models\User::find($datPhong->nguoi_dung_id);
        if (!$user) {
            return;
        }

        // Chỉ cập nhật nếu booking chưa được tính vào total_spent trước đó
        // (tránh tính lại khi booking được cập nhật nhiều lần)
        // Kiểm tra bằng cách xem booking này đã được tính chưa (có thể dùng một flag hoặc kiểm tra total_spent)
        
        // Tính lại total_spent từ tất cả booking đã hoàn thành
        $user->refreshLoyaltyStatus();
    }

    /**
     * Handle the DatPhong "deleted" event.
     */
    public function deleted(DatPhong $datPhong): void
    {
        //
    }

    /**
     * Handle the DatPhong "restored" event.
     */
    public function restored(DatPhong $datPhong): void
    {
        //
    }

    /**
     * Handle the DatPhong "force deleted" event.
     */
    public function forceDeleted(DatPhong $datPhong): void
    {
        //
    }
}
