<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HoaDon;

class HoaDonController extends Controller
{
    /**
     * Hiển thị danh sách hóa đơn (filter, search, paginate).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $q = $request->input('q');
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = HoaDon::with(['datPhong.nguoiDung', 'hoaDonItems'])->orderByDesc('created_at');

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('so_hoa_don', 'like', "%{$q}%")
                    ->orWhere('id', $q)
                    ->orWhereHas('datPhong', function ($q2) use ($q) {
                        $q2->where('ma_tham_chieu', 'like', "%{$q}%")
                            ->orWhere('id', $q);
                    });
            });
        }

        if (!empty($status)) {
            $query->where('trang_thai', $status);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $invoices = $query->paginate($perPage)->withQueryString();

        return view('staff.hoa_don.index', compact('invoices', 'q', 'status', 'dateFrom', 'dateTo'));
    }

    /**
     * Hiển thị chi tiết 1 hoá đơn.
     */
    public function show(HoaDon $hoaDon)
    {
        $hoaDon->load([
            'datPhong.nguoiDung',
            'hoaDonItems.phong',
            'hoaDonItems.loaiPhong',
            'hoaDonItems.vatDung',
        ]);

        return view('staff.hoa_don.show', compact('hoaDon'));
    }
}
