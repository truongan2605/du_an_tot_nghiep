<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoaiPhong;

class LoaiPhongGoiYController extends Controller
{
    /**
     * GET /goi-y/loai-phong?q=phong
     * Trả về danh sách gợi ý {id, ten} (tối đa 10).
     */
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $items = LoaiPhong::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('ten', 'LIKE', '%'.$q.'%');
            })
            ->orderBy('ten')
            ->limit(10)
            ->get(['id', 'ten']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }
}
