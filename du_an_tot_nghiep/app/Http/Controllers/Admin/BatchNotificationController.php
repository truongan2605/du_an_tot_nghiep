<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchNotificationController extends Controller
{
    /**
     * Hiển thị danh sách batch notifications
     */
    public function index(Request $request)
    {
        $query = ThongBao::query()
            ->whereNotNull('batch_id')
            ->with('nguoiNhan')
            ->orderBy('created_at', 'desc');

        // Lọc theo batch_id
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Lọc theo trạng thái
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $notifications = $query->paginate(20);

        // Lấy danh sách batch_id để filter
        $batchIds = ThongBao::whereNotNull('batch_id')
            ->select('batch_id')
            ->distinct()
            ->orderBy('batch_id', 'desc')
            ->pluck('batch_id');

        // Thống kê theo batch
        $batchStats = [];
        foreach ($batchIds as $batchId) {
            $stats = ThongBao::where('batch_id', $batchId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN trang_thai = "sent" THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN trang_thai = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN trang_thai = "pending" THEN 1 ELSE 0 END) as pending,
                    MIN(created_at) as started_at,
                    MAX(lan_thu_cuoi) as last_activity
                ')
                ->first();

            $batchStats[$batchId] = $stats;
        }

        return view('admin.batch-notifications.index', compact('notifications', 'batchIds', 'batchStats'));
    }

    /**
     * API: Lấy thống kê batch theo ID
     */
    public function getBatchStats($batchId)
    {
        $stats = ThongBao::where('batch_id', $batchId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN trang_thai = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN trang_thai = "pending" THEN 1 ELSE 0 END) as pending,
                MIN(created_at) as started_at,
                MAX(lan_thu_cuoi) as last_activity
            ')
            ->first();

        $notifications = ThongBao::where('batch_id', $batchId)
            ->with('nguoiNhan')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'stats' => $stats,
            'notifications' => $notifications,
            'is_complete' => $stats->pending == 0
        ]);
    }

    /**
     * API: Lấy tiến trình batch
     */
    public function getBatchProgress($batchId)
    {
        $stats = ThongBao::where('batch_id', $batchId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN trang_thai = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN trang_thai = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->first();

        $progress = $stats->total > 0 ? (($stats->sent + $stats->failed) / $stats->total) * 100 : 0;

        return response()->json([
            'total' => $stats->total,
            'sent' => $stats->sent,
            'failed' => $stats->failed,
            'pending' => $stats->pending,
            'progress' => round($progress, 2),
            'is_complete' => $stats->pending == 0
        ]);
    }

    /**
     * Retry failed notifications in a batch
     */
    public function retryBatch($batchId)
    {
        $failedNotifications = ThongBao::where('batch_id', $batchId)
            ->where('trang_thai', 'failed')
            ->get();

        $retryCount = 0;
        foreach ($failedNotifications as $notification) {
            // Reset status and retry
            $notification->update([
                'trang_thai' => 'pending',
                'so_lan_thu' => 0,
                'error_message' => null
            ]);

            // Dispatch retry job
            \App\Jobs\SendNotificationJob::dispatch($notification->id, $notification->nguoi_nhan_id)
                ->onQueue('notifications')
                ->delay(now()->addSeconds(5));

            $retryCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Đã gửi lại {$retryCount} thông báo thất bại",
            'retry_count' => $retryCount
        ]);
    }

    /**
     * Xóa batch notifications
     */
    public function deleteBatch($batchId)
    {
        $deletedCount = ThongBao::where('batch_id', $batchId)->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa {$deletedCount} thông báo trong batch",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Hiển thị chi tiết batch
     */
    public function show($batchId)
    {
        $notifications = ThongBao::where('batch_id', $batchId)
            ->with('nguoiNhan')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = ThongBao::where('batch_id', $batchId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN trang_thai = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN trang_thai = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN trang_thai = "pending" THEN 1 ELSE 0 END) as pending,
                MIN(created_at) as started_at,
                MAX(lan_thu_cuoi) as last_activity
            ')
            ->first();

        return view('admin.batch-notifications.show', compact('notifications', 'stats', 'batchId'));
    }
}