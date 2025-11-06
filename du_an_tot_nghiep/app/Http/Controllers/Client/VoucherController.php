<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Lọc theo trạng thái
        if ($request->filter === 'valid') {
            $query->where('end_date', '>=', now());
        } elseif ($request->filter === 'expired') {
            $query->where('end_date', '<', now());
        }

        // Tìm kiếm theo code
        if ($request->search) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        $vouchers = $query->where('active', 1)->latest()->paginate(6);
        return view('client.vouchers.index', compact('vouchers'));
    }

    public function apply(Request $request)
    {
        $code = $request->input('code');
        $total = floatval($request->input('total', 0));

        $voucher = Voucher::where('code', $code)->where('active', 1)->first();

        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Mã không tồn tại hoặc không hoạt động.']);
        }

        $today = Carbon::today();
        if ($voucher->start_date > $today || $voucher->end_date < $today) {
            return response()->json(['success' => false, 'message' => 'Mã đã hết hạn hoặc chưa có hiệu lực.']);
        }

        if ($voucher->min_order_amount && $total < $voucher->min_order_amount) {
            return response()->json(['success' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu.']);
        }

        // Tính toán giá trị giảm
        $discount = $voucher->type === 'percent'
            ? $total * ($voucher->value / 100)
            : $voucher->value;

        return response()->json([
            'success' => true,
            'discount' => round($discount, 0),
            'new_total' => max($total - $discount, 0),
            'message' => "Áp dụng mã thành công!"
        ]);
    }
}
