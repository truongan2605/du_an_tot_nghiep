<?php

namespace App\Http\Controllers;

use App\Models\ThongBao;
use App\Models\User;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendBatchNotificationJob;
use App\Events\NotificationCreated;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerNotificationController extends Controller
{
    /**
     * Hiển thị danh sách thông báo khách hàng
     */
    public function index(Request $request)
    {
        $query = ThongBao::query()
            ->where('kenh', 'in_app')
            ->whereIn('nguoi_nhan_id', function($q) {
                $q->select('id')
                  ->from('users')
                  ->where('vai_tro', 'khach_hang');
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

        // Lấy danh sách khách hàng để filter
        $customers = User::where('vai_tro', 'khach_hang')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.customer-notifications.index', compact('notifications', 'customers'));
    }

    /**
     * Hiển thị form tạo thông báo khách hàng
     */
    public function create()
    {
        $customers = User::where('vai_tro', 'khach_hang')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $channels = ['email', 'in_app'];
        return view('admin.customer-notifications.create', compact('customers', 'channels'));
    }

    /**
     * Lưu thông báo khách hàng mới
     */
    public function store(Request $request)
    {
        // Convert payload from JSON string (textarea) to array before validation
        $rawPayload = $request->input('payload');
        if (is_string($rawPayload) && trim($rawPayload) !== '') {
            $decoded = json_decode($rawPayload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withInput()->withErrors(['payload' => 'Payload phải là JSON hợp lệ.']);
            }
            $request->merge(['payload' => $decoded]);
        } elseif ($rawPayload === '' || $rawPayload === null) {
            $request->merge(['payload' => null]);
        }

        $data = $request->validate([
            'nguoi_nhan_id' => ['nullable', Rule::exists('users', 'id')],
            'kenh' => ['required', Rule::in(['email', 'in_app'])],
            'ten_template' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'send_to_all_customers' => ['sometimes', 'boolean'],
        ]);

        // Validate that either nguoi_nhan_id or send_to_all_customers is provided
        if (empty($data['nguoi_nhan_id']) && empty($data['send_to_all_customers'])) {
            return back()->withErrors(['nguoi_nhan_id' => 'Vui lòng chọn khách hàng hoặc gửi cho tất cả khách hàng.'])->withInput();
        }

        // Set default values
        $data['trang_thai'] = 'pending';
        $data['so_lan_thu'] = 0;

        // If send to all customers
        if (!empty($data['send_to_all_customers'])) {
            try {
                $customers = User::where('vai_tro', 'khach_hang')->get(['id', 'email']);
                $userIds = $customers->pluck('id')->toArray();
                
                if (empty($userIds)) {
                    return back()->withErrors(['send_to_all_customers' => 'Không tìm thấy khách hàng nào.'])->withInput();
                }

                // Prepare notification data for batch processing
                $notificationData = [
                    'kenh' => $data['kenh'],
                    'ten_template' => $data['ten_template'],
                    'payload' => $data['payload'] ?? null,
                ];

                // Dispatch batch notification job
                SendBatchNotificationJob::dispatch($notificationData, $userIds)
                    ->onQueue('notifications')
                    ->delay(now()->addSeconds(5));

                Log::info("Customer batch notification dispatched", [
                    'user_count' => count($userIds),
                    'channel' => $data['kenh']
                ]);

                return redirect()->route('admin.customer-notifications.index')
                    ->with('success', "Đã gửi thông báo cho " . count($userIds) . " khách hàng. Thông báo đang được xử lý trong nền.");

            } catch (\Exception $e) {
                Log::error("Failed to dispatch customer batch notification", [
                    'error' => $e->getMessage()
                ]);
                
                return back()->withErrors(['send_to_all_customers' => 'Có lỗi xảy ra khi gửi thông báo: ' . $e->getMessage()])->withInput();
            }
        }

        // Otherwise create single notification for selected customer
        $notification = ThongBao::create($data);

        // Broadcast notification for real-time updates
        if ($data['kenh'] === 'in_app') {
            broadcast(new NotificationCreated($notification));
        }

        // Dispatch single notification job
        SendNotificationJob::dispatch($notification->id, $notification->nguoi_nhan_id)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(2));

        return redirect()->route('admin.customer-notifications.show', $notification)->with('success', 'Tạo thông báo khách hàng thành công');
    }

    /**
     * Hiển thị chi tiết thông báo khách hàng
     */
    public function show(ThongBao $notification)
    {
        $notification->load('nguoiNhan');
        return view('admin.customer-notifications.show', ['notification' => $notification]);
    }

    /**
     * Hiển thị form chỉnh sửa thông báo khách hàng
     */
    public function edit(ThongBao $notification)
    {
        $customers = User::where('vai_tro', 'khach_hang')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $channels = ['email', 'in_app'];
        return view('admin.customer-notifications.edit', [
            'notification' => $notification,
            'customers' => $customers,
            'channels' => $channels,
        ]);
    }

    /**
     * Cập nhật thông báo khách hàng
     */
    public function update(Request $request, ThongBao $notification)
    {
        // Convert payload from JSON string (textarea) to array before validation
        $rawPayload = $request->input('payload');
        if (is_string($rawPayload) && trim($rawPayload) !== '') {
            $decoded = json_decode($rawPayload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withInput()->withErrors(['payload' => 'Payload phải là JSON hợp lệ.']);
            }
            $request->merge(['payload' => $decoded]);
        } elseif ($rawPayload === '' || $rawPayload === null) {
            $request->merge(['payload' => null]);
        }

        $data = $request->validate([
            'nguoi_nhan_id' => ['nullable', Rule::exists('users', 'id')],
            'kenh' => ['required', Rule::in(['email', 'in_app'])],
            'ten_template' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
        ]);

        // For updates, we don't change the recipient if not provided
        if (empty($data['nguoi_nhan_id'])) {
            unset($data['nguoi_nhan_id']);
        }

        // Don't allow manual changes to system-managed fields
        unset($data['trang_thai']);
        unset($data['so_lan_thu']);
        unset($data['lan_thu_cuoi']);

        $notification->update($data);

        if ($notification->kenh === 'email' && $notification->trang_thai !== 'read') {
            try {
                $user = User::find($notification->nguoi_nhan_id);
                if ($user) {
                    Mail::to($user->email)->send(new ThongBaoEmail($notification));
                    $notification->update([
                        'trang_thai' => 'sent',
                        'so_lan_thu' => ($notification->so_lan_thu ?? 0) + 1,
                        'lan_thu_cuoi' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $notification->update([
                    'trang_thai' => 'failed',
                    'so_lan_thu' => ($notification->so_lan_thu ?? 0) + 1,
                    'lan_thu_cuoi' => now(),
                ]);
            }
        }

        return redirect()->route('admin.customer-notifications.show', $notification)->with('success', 'Cập nhật thông báo khách hàng thành công');
    }

    /**
     * Xóa thông báo khách hàng
     */
    public function destroy(ThongBao $notification)
    {
        $notification->delete();
        return redirect()->route('admin.customer-notifications.index')->with('success', 'Đã xóa thông báo khách hàng');
    }

    /**
     * Gửi lại thông báo khách hàng
     */
    public function resend(ThongBao $notification)
    {
        // Only allow resending failed notifications
        if ($notification->trang_thai !== 'failed') {
            return back()->with('error', 'Chỉ có thể gửi lại thông báo thất bại.');
        }

        // Reset status to pending and increment retry count
        $notification->update([
            'trang_thai' => 'pending',
            'so_lan_thu' => ($notification->so_lan_thu ?? 0) + 1,
            'lan_thu_cuoi' => now()
        ]);

        // Send email if channel is email
        if ($notification->kenh === 'email') {
            try {
                $user = $notification->nguoiNhan;
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new ThongBaoEmail($notification));
                    $notification->update(['trang_thai' => 'sent']);
                } else {
                    $notification->update(['trang_thai' => 'failed']);
                }
            } catch (\Exception $e) {
                $notification->update(['trang_thai' => 'failed']);
                return back()->with('error', 'Gửi lại thất bại: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Đã gửi lại thông báo khách hàng thành công.');
    }
}
