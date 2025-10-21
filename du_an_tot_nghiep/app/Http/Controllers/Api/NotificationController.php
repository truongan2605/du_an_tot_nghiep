<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with pagination (Admin only)
     */
    public function index(Request $request)
    {
        // Only allow admin access to full notification list
        $user = Auth::user();
        if (!$user || $user->vai_tro !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $user = Auth::user();
        
        $query = ThongBao::where('nguoi_nhan_id', $user->id)
            ->where('kenh', 'in_app')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('trang_thai', $request->status);
        }

        // Filter by template
        if ($request->filled('template')) {
            $query->where('ten_template', 'like', '%' . $request->template . '%');
        }

        // Search in payload
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ten_template', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ]
        ]);
    }

    /**
     * Get notification details
     */
    public function show($id)
    {
        $notification = ThongBao::where('id', $id)
            ->where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = ThongBao::where('id', $id)
            ->where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        $notification->update(['trang_thai' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu thông báo là đã đọc',
            'data' => $notification
        ]);
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:thong_bao,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $updated = ThongBao::whereIn('id', $request->notification_ids)
            ->where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->update(['trang_thai' => 'read']);

        return response()->json([
            'success' => true,
            'message' => "Đã đánh dấu {$updated} thông báo là đã đọc"
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $updated = ThongBao::where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->where('trang_thai', '!=', 'read')
            ->update(['trang_thai' => 'read']);

        return response()->json([
            'success' => true,
            'message' => "Đã đánh dấu {$updated} thông báo là đã đọc"
        ]);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount()
    {
        $count = ThongBao::where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->where('trang_thai', '!=', 'read')
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Get recent notifications (for dropdown)
     */
    public function getRecent(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $notifications = ThongBao::where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = ThongBao::where('id', $id)
            ->where('nguoi_nhan_id', Auth::id())
            ->where('kenh', 'in_app')
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thông báo'
        ]);
    }

    /**
     * Get notification statistics
     */
    public function getStats()
    {
        $user = Auth::user();
        
        $stats = [
            'total' => ThongBao::where('nguoi_nhan_id', $user->id)
                ->where('kenh', 'in_app')
                ->count(),
            'unread' => ThongBao::where('nguoi_nhan_id', $user->id)
                ->where('kenh', 'in_app')
                ->where('trang_thai', '!=', 'read')
                ->count(),
            'read' => ThongBao::where('nguoi_nhan_id', $user->id)
                ->where('kenh', 'in_app')
                ->where('trang_thai', 'read')
                ->count(),
            'pending' => ThongBao::where('nguoi_nhan_id', $user->id)
                ->where('kenh', 'in_app')
                ->where('trang_thai', 'pending')
                ->count(),
            'failed' => ThongBao::where('nguoi_nhan_id', $user->id)
                ->where('kenh', 'in_app')
                ->where('trang_thai', 'failed')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
