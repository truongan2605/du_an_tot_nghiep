@php
    $editing = isset($thongBao);
@endphp

<div class="space-y-4">
    @if(!$editing)
    <div>
        <label class="block mb-1">Gửi theo vai trò (tùy chọn)</label>
        <select name="vai_tro_broadcast[]" class="border px-3 py-2 rounded w-full" multiple>
            <option value="admin" @selected(collect(old('vai_tro_broadcast', []))->contains('admin'))>Admin</option>
            <option value="nhan_vien" @selected(collect(old('vai_tro_broadcast', []))->contains('nhan_vien'))>Nhân viên</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">Nếu chọn vai trò, hệ thống sẽ tạo thông báo cho tất cả người dùng thuộc vai trò đó. Trường "Người nhận" phía dưới sẽ bị bỏ qua.</p>
        @error('vai_tro_broadcast')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        @error('vai_tro_broadcast.*')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    @endif
    <div>
        <label class="block mb-1">Người nhận</label>
        <select name="nguoi_nhan_id" class="border px-3 py-2 rounded w-full" required>
            <option value="">-- Chọn người nhận --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('nguoi_nhan_id', $thongBao->nguoi_nhan_id ?? '') == $user->id)>
                    {{ $user->name }} - {{ $user->email }}
                </option>
            @endforeach
        </select>
        @error('nguoi_nhan_id')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block mb-1">Kênh</label>
            <select name="kenh" class="border px-3 py-2 rounded w-full" required>
                @foreach($channels as $c)
                    <option value="{{ $c }}" @selected(old('kenh', $thongBao->kenh ?? '') == $c)>{{ $c }}</option>
                @endforeach
            </select>
            @error('kenh')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block mb-1">Trạng thái</label>
            <select name="trang_thai" class="border px-3 py-2 rounded w-full" required>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected(old('trang_thai', $thongBao->trang_thai ?? '') == $s)>{{ $s }}</option>
                @endforeach
            </select>
            @error('trang_thai')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block mb-1">Template</label>
            <input type="text" name="ten_template" class="border px-3 py-2 rounded w-full" value="{{ old('ten_template', $thongBao->ten_template ?? '') }}" required>
            @error('ten_template')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>
    </div>

    <div>
        <label class="block mb-1">Payload (JSON)</label>
        <textarea name="payload" class="border px-3 py-2 rounded w-full" rows="6" placeholder='{"title":"..."}'>{{ old('payload', isset($thongBao) && is_array($thongBao->payload) ? json_encode($thongBao->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">Nhập JSON hợp lệ. Sẽ được parse thành mảng.</p>
        @error('payload')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block mb-1">Số lần thử</label>
            <input type="number" min="0" name="so_lan_thu" class="border px-3 py-2 rounded w-full" value="{{ old('so_lan_thu', $thongBao->so_lan_thu ?? 0) }}">
            @error('so_lan_thu')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="block mb-1">Lần thử cuối</label>
            <input type="datetime-local" name="lan_thu_cuoi" class="border px-3 py-2 rounded w-full" value="{{ old('lan_thu_cuoi', isset($thongBao->lan_thu_cuoi) ? $thongBao->lan_thu_cuoi->format('Y-m-d\TH:i') : '') }}">
            @error('lan_thu_cuoi')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
        </div>
    </div>
</div>


