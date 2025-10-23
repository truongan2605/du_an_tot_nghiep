<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use App\Models\User;
use App\Jobs\SendBatchNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminNotificationController extends Controller
{
    /**
     * Hiển thị danh sách thông báo cho admin/nhân viên
     */
    public function index(Request $request)
    {
        $query = ThongBao::query()
            ->where('kenh', 'in_app')
            ->whereIn('nguoi_nhan_id', function($q) {
                $q->select('id')
                  ->from('users')
                  ->whereIn('vai_tro', ['admin', 'nhan_vien']);
            })
            ->with('nguoiNhan')
            ->orderBy('created_at', 'desc');

        // Lọc theo trạng thái
        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // Lọc theo người nhận
        if ($request->filled('nguoi_nhan_id')) {
            $query->where('nguoi_nhan_id', $request->nguoi_nhan_id);
        }

        // Tìm kiếm theo từ khóa
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ten_template', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%")
                  ->orWhereHas('nguoiNhan', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $notifications = $query->paginate(15);

        // Lấy danh sách admin/nhân viên để filter
        $users = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->select('id', 'name', 'email', 'vai_tro')
            ->get();

        // Thống kê
        $stats = [
            'total' => ThongBao::where('kenh', 'in_app')
                ->whereIn('nguoi_nhan_id', function($q) {
                    $q->select('id')->from('users')->whereIn('vai_tro', ['admin', 'nhan_vien']);
                })->count(),
            'unread' => ThongBao::where('kenh', 'in_app')
                ->whereIn('nguoi_nhan_id', function($q) {
                    $q->select('id')->from('users')->whereIn('vai_tro', ['admin', 'nhan_vien']);
                })->where('trang_thai', 'pending')->count(),
            'sent' => ThongBao::where('kenh', 'in_app')
                ->whereIn('nguoi_nhan_id', function($q) {
                    $q->select('id')->from('users')->whereIn('vai_tro', ['admin', 'nhan_vien']);
                })->where('trang_thai', 'sent')->count(),
            'failed' => ThongBao::where('kenh', 'in_app')
                ->whereIn('nguoi_nhan_id', function($q) {
                    $q->select('id')->from('users')->whereIn('vai_tro', ['admin', 'nhan_vien']);
                })->where('trang_thai', 'failed')->count(),
        ];

        return view('admin.admin-notifications.index', compact('notifications', 'users', 'stats'));
    }

    /**
     * Hiển thị form tạo thông báo mới
     */
    public function create()
    {
        // Lấy danh sách admin và nhân viên
        $users = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->select('id', 'name', 'email', 'vai_tro')
            ->get();

        // Templates có sẵn
        $templates = [
            'system_maintenance' => 'Bảo trì hệ thống',
            'new_booking' => 'Đặt phòng mới',
            'payment_received' => 'Thanh toán nhận được',
            'booking_cancelled' => 'Hủy đặt phòng',
            'system_alert' => 'Cảnh báo hệ thống',
            'daily_report' => 'Báo cáo hàng ngày',
            'monthly_summary' => 'Tóm tắt tháng',
        ];

        return view('admin.admin-notifications.create', compact('users', 'templates'));
    }

    /**
     * Lưu thông báo mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'ten_template' => 'required|string|max:255',
            'payload' => 'required|json',
            'nguoi_nhan_ids' => 'required|array|min:1',
            'nguoi_nhan_ids.*' => 'exists:users,id',
        ]);

        // Validate JSON payload
        $payload = json_decode($request->payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withInput()->withErrors(['payload' => 'Payload phải là JSON hợp lệ.']);
        }

        try {
            // Prepare notification data for batch processing
            $notificationData = [
                'kenh' => 'in_app',
                'ten_template' => $request->ten_template,
                'payload' => $request->payload,
            ];

            // Dispatch batch notification job
            SendBatchNotificationJob::dispatch($notificationData, $request->nguoi_nhan_ids)
                ->onQueue('notifications')
                ->delay(now()->addSeconds(3));

            Log::info("Admin batch notification dispatched", [
                'user_count' => count($request->nguoi_nhan_ids),
                'template' => $request->ten_template
            ]);

            return redirect()->route('admin.admin-notifications.index')
                ->with('success', 'Đã gửi thông báo cho ' . count($request->nguoi_nhan_ids) . ' người dùng. Thông báo đang được xử lý trong nền.');

        } catch (\Exception $e) {
            Log::error("Failed to dispatch admin batch notification", [
                'error' => $e->getMessage(),
                'user_ids' => $request->nguoi_nhan_ids
            ]);
            
            return back()->withErrors(['payload' => 'Có lỗi xảy ra khi gửi thông báo: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Hiển thị chi tiết thông báo
     */
    public function show($id)
    {
        $notification = ThongBao::with('nguoiNhan')->findOrFail($id);
        
        // Kiểm tra quyền xem (chỉ admin hoặc người nhận)
        if (Auth::user()->vai_tro !== 'admin' && $notification->nguoi_nhan_id !== Auth::id()) {
            abort(403, 'Không có quyền xem thông báo này');
        }

        return view('admin.admin-notifications.show', compact('notification'));
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead($id)
    {
        $notification = ThongBao::findOrFail($id);
        
        // Kiểm tra quyền
        if (Auth::user()->vai_tro !== 'admin' && $notification->nguoi_nhan_id !== Auth::id()) {
            abort(403, 'Không có quyền thực hiện hành động này');
        }

        $notification->update(['trang_thai' => 'read']);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu thông báo đã đọc');
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead()
    {
        ThongBao::where('kenh', 'in_app')
            ->where('nguoi_nhan_id', Auth::id())
            ->where('trang_thai', '!=', 'read')
            ->update(['trang_thai' => 'read']);

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã đánh dấu tất cả thông báo đã đọc');
    }

    /**
     * Xóa thông báo
     */
    public function destroy($id)
    {
        $notification = ThongBao::findOrFail($id);
        
        // Chỉ admin mới có thể xóa
        if (Auth::user()->vai_tro !== 'admin') {
            abort(403, 'Chỉ admin mới có thể xóa thông báo');
        }

        $notification->delete();

        return back()->with('success', 'Đã xóa thông báo thành công');
    }

    /**
     * API: Lấy số thông báo chưa đọc
     */
    public function getUnreadCount()
    {
        $count = ThongBao::where('kenh', 'in_app')
            ->where('nguoi_nhan_id', Auth::id())
            ->where('trang_thai', '!=', 'read')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * API: Lấy danh sách thông báo gần đây
     */
    public function getRecentNotifications()
    {
        $notifications = ThongBao::where('kenh', 'in_app')
            ->where('nguoi_nhan_id', Auth::id())
            ->with('nguoiNhan')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Model already handles payload conversion, no need to transform

        return response()->json($notifications);
    }
}
