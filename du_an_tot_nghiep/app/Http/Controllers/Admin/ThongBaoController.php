<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class ThongBaoController extends Controller
{
    public function index(Request $request)
    {
        $query = ThongBao::query()->with('nguoiNhan');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('ten_template', 'like', "%{$q}%")
                    ->orWhere('kenh', 'like', "%{$q}%")
                    ->orWhere('trang_thai', 'like', "%{$q}%");
            });
        }

        $thongBaos = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.thong-bao.index', compact('thongBaos'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $channels = ['email', 'sms', 'in_app'];
        return view('admin.thong-bao.create', compact('users', 'channels'));
    }

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
            'kenh' => ['required', Rule::in(['email', 'sms', 'in_app'])],
            'ten_template' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'vai_tro_broadcast' => ['sometimes', 'array'],
            'vai_tro_broadcast.*' => ['in:admin,nhan_vien'],
        ]);

        // Validate that either nguoi_nhan_id or vai_tro_broadcast is provided
        if (empty($data['nguoi_nhan_id']) && empty($data['vai_tro_broadcast'])) {
            return back()->withErrors(['nguoi_nhan_id' => 'Vui lòng chọn người nhận hoặc vai trò để gửi thông báo.'])->withInput();
        }

        // Set default values - system will handle these automatically
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
                    ->delay(now()->addSeconds(5)); // Small delay to ensure proper queue processing

                Log::info("Batch notification dispatched", [
                    'user_count' => count($userIds),
                    'roles' => $roles->toArray(),
                    'channel' => $data['kenh']
                ]);

                return redirect()->route('admin.thong-bao.index')
                    ->with('success', "Đã gửi thông báo hàng loạt cho " . count($userIds) . " người dùng. Thông báo đang được xử lý trong nền.");

            } catch (\Exception $e) {
                Log::error("Failed to dispatch batch notification", [
                    'error' => $e->getMessage(),
                    'roles' => $roles->toArray()
                ]);
                
                return back()->withErrors(['vai_tro_broadcast' => 'Có lỗi xảy ra khi gửi thông báo hàng loạt: ' . $e->getMessage()])->withInput();
            }
        }

        // Otherwise create single notification for selected user
        $thongBao = ThongBao::create($data);

        // Dispatch single notification job
        SendNotificationJob::dispatch($thongBao->id, $thongBao->nguoi_nhan_id)
            ->onQueue('notifications')
            ->delay(now()->addSeconds(2));

        return redirect()->route('admin.thong-bao.show', $thongBao)->with('success', 'Tạo thông báo thành công');
    }

    public function show(ThongBao $thong_bao)
    {
        $thong_bao->load('nguoiNhan');
        return view('admin.thong-bao.show', ['thongBao' => $thong_bao]);
    }

    public function edit(ThongBao $thong_bao)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $channels = ['email', 'sms', 'in_app'];
        return view('admin.thong-bao.edit', [
            'thongBao' => $thong_bao,
            'users' => $users,
            'channels' => $channels,
        ]);
    }

    public function update(Request $request, ThongBao $thong_bao)
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
            'kenh' => ['required', Rule::in(['email', 'sms', 'in_app'])],
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

        $thong_bao->update($data);

        if ($thong_bao->kenh === 'email' && $thong_bao->trang_thai !== 'read') {
            try {
                $user = User::find($thong_bao->nguoi_nhan_id);
                if ($user) {
                    Mail::to($user->email)->send(new ThongBaoEmail($thong_bao));
                    $thong_bao->update([
                        'trang_thai' => 'sent',
                        'so_lan_thu' => ($thong_bao->so_lan_thu ?? 0) + 1,
                        'lan_thu_cuoi' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $thong_bao->update([
                    'trang_thai' => 'failed',
                    'so_lan_thu' => ($thong_bao->so_lan_thu ?? 0) + 1,
                    'lan_thu_cuoi' => now(),
                ]);
            }
        }

        return redirect()->route('admin.thong-bao.show', $thong_bao)->with('success', 'Cập nhật thông báo thành công');
    }

    public function destroy(ThongBao $thong_bao)
    {
        $thong_bao->delete();
        return redirect()->route('admin.thong-bao.index')->with('success', 'Đã xóa thông báo');
    }

    public function toggleActive(ThongBao $thong_bao)
    {
        // Toggle between pending and failed (simple example), or implement your own logic
        $thong_bao->trang_thai = $thong_bao->trang_thai === 'pending' ? 'sent' : 'pending';
        $thong_bao->save();
        return redirect()->back()->with('success', 'Đã đổi trạng thái');
    }

    public function markRead(ThongBao $thong_bao)
    {
        // Only owner can mark read
        if (Auth::id() !== $thong_bao->nguoi_nhan_id) {
            abort(403);
        }
        $thong_bao->update(['trang_thai' => 'read']);
        
        // Return JSON for AJAX requests
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu đã đọc'
            ]);
        }
        
        return back()->with('success', 'Đã đánh dấu đã đọc');
    }

    public function getUnreadCount()
    {
        $count = ThongBao::where('nguoi_nhan_id', Auth::id())
            ->where('trang_thai', '!=', 'read')
            ->count();
            
        return response()->json(['count' => $count]);
    }

    public function resend(ThongBao $thong_bao)
    {
        // Only allow resending failed notifications
        if ($thong_bao->trang_thai !== 'failed') {
            return back()->with('error', 'Chỉ có thể gửi lại thông báo thất bại.');
        }

        // Reset status to pending and increment retry count
        $thong_bao->update([
            'trang_thai' => 'pending',
            'so_lan_thu' => ($thong_bao->so_lan_thu ?? 0) + 1,
            'lan_thu_cuoi' => now()
        ]);

        // Send email if channel is email
        if ($thong_bao->kenh === 'email') {
            try {
                $user = $thong_bao->nguoiNhan;
                if ($user && $user->email) {
                    Mail::to($user->email)->send(new ThongBaoEmail($thong_bao));
                    $thong_bao->update(['trang_thai' => 'sent']);
                } else {
                    $thong_bao->update(['trang_thai' => 'failed']);
                }
            } catch (\Exception $e) {
                $thong_bao->update(['trang_thai' => 'failed']);
                return back()->with('error', 'Gửi lại thất bại: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Đã gửi lại thông báo thành công.');
    }

    public function modal(ThongBao $thong_bao)
    {
        $thong_bao->load('nguoiNhan');
        
        $html = view('admin.thong-bao.modal-content', compact('thong_bao'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html,
            'editUrl' => route('admin.thong-bao.edit', $thong_bao),
            'resendUrl' => route('admin.thong-bao.resend', $thong_bao),
            'canResend' => $thong_bao->trang_thai === 'failed'
        ]);
    }

    public function clientShow($id)
    {
        try {
            $thong_bao = ThongBao::findOrFail($id);
            
            // Only allow users to view their own notifications
            if ($thong_bao->nguoi_nhan_id !== Auth::id()) {
                abort(403, 'Không có quyền xem thông báo này');
            }
            
            // Mark as read when viewing
            if ($thong_bao->trang_thai !== 'read') {
                $thong_bao->update(['trang_thai' => 'read']);
            }
            
            $thong_bao->load('nguoiNhan');
            
            return view('client.thong-bao.show', compact('thong_bao'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Không tìm thấy thông báo');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in clientShow: ' . $e->getMessage());
            abort(500, 'Lỗi server: ' . $e->getMessage());
        }
    }

    public function markReadOnView($id)
    {
        try {
            $thong_bao = ThongBao::findOrFail($id);
            
            // Only allow users to mark their own notifications as read
            if ($thong_bao->nguoi_nhan_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền đánh dấu thông báo này'
                ], 403);
            }
            
            $thong_bao->update(['trang_thai' => 'read']);
            
            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu thông báo là đã đọc'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in markReadOnView: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clientModal($id)
    {
        try {
            $thong_bao = ThongBao::findOrFail($id);
            
            // Only allow users to view their own notifications
            if ($thong_bao->nguoi_nhan_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có quyền xem thông báo này'
                ], 403);
            }
            
            $thong_bao->load('nguoiNhan');
            
            $html = view('partials.notification-modal-content', compact('thong_bao'))->render();
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'isUnread' => $thong_bao->trang_thai !== 'read'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in clientModal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }
}


