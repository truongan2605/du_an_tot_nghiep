<?php

namespace App\Http\Controllers;

use App\Models\ThongBao;
use App\Models\User;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendBatchNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InternalNotificationController extends Controller
{
    /**
     * Hiển thị danh sách thông báo nội bộ
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

        // Lọc theo vai trò
        if ($request->filled('vai_tro')) {
            $query->whereHas('nguoiNhan', function($q) use ($request) {
                $q->where('vai_tro', $request->vai_tro);
            });
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
        $staff = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'vai_tro']);

        return view('admin.internal-notifications.index', compact('notifications', 'staff'));
    }

    /**
     * Hiển thị form tạo thông báo nội bộ
     */
    public function create()
    {
        $staff = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'vai_tro']);
        
        $channels = ['email', 'in_app'];
        $roles = ['admin', 'nhan_vien'];
        return view('admin.internal-notifications.create', compact('staff', 'channels', 'roles'));
    }

    /**
     * Lưu thông báo nội bộ mới
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
            'vai_tro_broadcast' => ['sometimes', 'array'],
            'vai_tro_broadcast.*' => ['in:admin,nhan_vien'],
        ]);

        // Validate that either nguoi_nhan_id or vai_tro_broadcast is provided
        if (empty($data['nguoi_nhan_id']) && empty($data['vai_tro_broadcast'])) {
            return back()->withErrors(['nguoi_nhan_id' => 'Vui lòng chọn người nhận hoặc vai trò để gửi thông báo.'])->withInput();
        }

        // Set default values
        $data['trang_thai'] = 'pending';
        $data['so_lan_thu'] = 0;

        // If broadcast by role is provided, create multiple notifications using batch processing
        $roles = collect($data['vai_tro_broadcast'] ?? [])->unique()->values();
        if ($roles->isNotEmpty()) {
            try {
                $targets = User::whereIn('vai_tro', $roles)->get(['id', 'email']);
                $userIds = $targets->pluck('id')->toArray();
                
                if (empty($userIds)) {
                    return back()->withErrors(['vai_tro_broadcast' => 'Không tìm thấy người dùng với vai trò được chọn.'])->withInput();
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

                Log::info("Internal batch notification dispatched", [
                    'user_count' => count($userIds),
                    'roles' => $roles->toArray(),
                    'channel' => $data['kenh']
                ]);

                return redirect()->route('admin.internal-notifications.index')
                    ->with('success', "Đã gửi thông báo nội bộ cho " . count($userIds) . " người dùng. Thông báo đang được xử lý trong nền.");

            } catch (\Exception $e) {
                Log::error("Failed to dispatch internal batch notification", [
                    'error' => $e->getMessage(),
                    'roles' => $roles->toArray()
                ]);
                
                return back()->withErrors(['vai_tro_broadcast' => 'Có lỗi xảy ra khi gửi thông báo nội bộ: ' . $e->getMessage()])->withInput();
            }
        }

        // Otherwise create single notification for selected user
        $notification = ThongBao::create($data);

        // Dispatch single notification job
        SendNotificationJob::dispatch($notification->id, $notification->nguoi_nhan_id)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(2));

        return redirect()->route('admin.internal-notifications.show', $notification)->with('success', 'Tạo thông báo nội bộ thành công');
    }

    /**
     * Hiển thị chi tiết thông báo nội bộ
     */
    public function show(ThongBao $notification)
    {
        $notification->load('nguoiNhan');
        return view('admin.internal-notifications.show', ['notification' => $notification]);
    }

    /**
     * Hiển thị form chỉnh sửa thông báo nội bộ
     */
    public function edit(ThongBao $notification)
    {
        $staff = User::whereIn('vai_tro', ['admin', 'nhan_vien'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'vai_tro']);
        
        $channels = ['email', 'in_app'];
        return view('admin.internal-notifications.edit', [
            'notification' => $notification,
            'staff' => $staff,
            'channels' => $channels,
        ]);
    }

    /**
     * Cập nhật thông báo nội bộ
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

        return redirect()->route('admin.internal-notifications.show', $notification)->with('success', 'Cập nhật thông báo nội bộ thành công');
    }

    /**
     * Xóa thông báo nội bộ
     */
    public function destroy(ThongBao $notification)
    {
        $notification->delete();
        return redirect()->route('admin.internal-notifications.index')->with('success', 'Đã xóa thông báo nội bộ');
    }

    /**
     * Gửi lại thông báo nội bộ
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

        return back()->with('success', 'Đã gửi lại thông báo nội bộ thành công.');
    }
}




