<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\ThongBaoEmail;
use Illuminate\Support\Facades\Auth;

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
        $statuses = ['pending', 'sent', 'failed', 'read'];
        return view('admin.thong-bao.create', compact('users', 'channels', 'statuses'));
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
            'nguoi_nhan_id' => ['required', Rule::exists('users', 'id')],
            'kenh' => ['required', Rule::in(['email', 'sms', 'in_app'])],
            'ten_template' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'trang_thai' => ['required', Rule::in(['pending', 'sent', 'failed', 'read'])],
            'so_lan_thu' => ['nullable', 'integer', 'min:0'],
            'lan_thu_cuoi' => ['nullable', 'date'],
            'vai_tro_broadcast' => ['sometimes', 'array'],
            'vai_tro_broadcast.*' => ['in:admin,nhan_vien'],
        ]);

        // If broadcast by role is provided, create multiple notifications
        $roles = collect($data['vai_tro_broadcast'] ?? [])->unique()->values();
        if ($roles->isNotEmpty()) {
            $targets = User::whereIn('vai_tro', $roles)->get(['id', 'email']);
            foreach ($targets as $user) {
                $item = ThongBao::create([
                    'nguoi_nhan_id' => $user->id,
                    'kenh' => $data['kenh'],
                    'ten_template' => $data['ten_template'],
                    'payload' => $data['payload'] ?? null,
                    'trang_thai' => $data['trang_thai'],
                    'so_lan_thu' => $data['so_lan_thu'] ?? 0,
                    'lan_thu_cuoi' => $data['lan_thu_cuoi'] ?? null,
                ]);

                if ($item->kenh === 'email' && $user->email) {
                    try {
                        Mail::to($user->email)->send(new ThongBaoEmail($item));
                        $item->update([
                            'trang_thai' => 'sent',
                            'so_lan_thu' => ($item->so_lan_thu ?? 0) + 1,
                            'lan_thu_cuoi' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        $item->update([
                            'trang_thai' => 'failed',
                            'so_lan_thu' => ($item->so_lan_thu ?? 0) + 1,
                            'lan_thu_cuoi' => now(),
                        ]);
                    }
                }
            }

            return redirect()->route('admin.thong-bao.index')->with('success', 'Đã tạo thông báo hàng loạt theo vai trò');
        }

        // Otherwise create single notification for selected user
        $thongBao = ThongBao::create($data);

        if ($thongBao->kenh === 'email') {
            try {
                $user = User::find($thongBao->nguoi_nhan_id);
                if ($user) {
                    Mail::to($user->email)->send(new ThongBaoEmail($thongBao));
                    $thongBao->update([
                        'trang_thai' => 'sent',
                        'so_lan_thu' => ($thongBao->so_lan_thu ?? 0) + 1,
                        'lan_thu_cuoi' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                $thongBao->update([
                    'trang_thai' => 'failed',
                    'so_lan_thu' => ($thongBao->so_lan_thu ?? 0) + 1,
                    'lan_thu_cuoi' => now(),
                ]);
            }
        }

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
        $statuses = ['pending', 'sent', 'failed', 'read'];
        return view('admin.thong-bao.edit', [
            'thongBao' => $thong_bao,
            'users' => $users,
            'channels' => $channels,
            'statuses' => $statuses,
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
            'nguoi_nhan_id' => ['required', Rule::exists('users', 'id')],
            'kenh' => ['required', Rule::in(['email', 'sms', 'in_app'])],
            'ten_template' => ['required', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
            'trang_thai' => ['required', Rule::in(['pending', 'sent', 'failed', 'read'])],
            'so_lan_thu' => ['nullable', 'integer', 'min:0'],
            'lan_thu_cuoi' => ['nullable', 'date'],
        ]);

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
        return back()->with('success', 'Đã đánh dấu đã đọc');
    }
}


