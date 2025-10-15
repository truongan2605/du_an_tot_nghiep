<?php

namespace App\Http\Controllers\Client;

use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Mail\PaymentFail;
use App\Mail\PaymentSuccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * ✅ Tạo URL thanh toán VNPAY (Client gửi lên)
     */
    public function initiateVNPay(Request $request)
    {
        $validated = $request->validate([
            'dat_phong_id' => 'required|exists:dat_phong,id',
            'amount' => 'required|numeric|min:1',
            'order_info' => 'required|string|max:255',
        ]);

        $dat_phong = DatPhong::findOrFail($validated['dat_phong_id']);

        // Kiểm tra quyền và tính hợp lệ
        if ($dat_phong->nguoi_dung_id !== Auth::id()) {
            return response()->json(['error' => 'Booking không thuộc bạn'], 403);
        }

        if ($dat_phong->trang_thai !== 'dang_cho') {
            return response()->json(['error' => 'Booking không ở trạng thái chờ'], 400);
        }

        if ($validated['amount'] != $dat_phong->tong_tien) {
            return response()->json(['error' => 'Số tiền không khớp'], 400);
        }

        // Tạo bản ghi giao dịch
        $giao_dich = DB::transaction(function () use ($validated, $dat_phong) {
            return GiaoDich::create([
                'dat_phong_id' => $dat_phong->id,
                'nha_cung_cap' => 'vnpay',
                'so_tien' => $validated['amount'],
                'don_vi' => $dat_phong->don_vi_tien ?? 'VND',
                'trang_thai' => 'dang_cho',
                'ghi_chu' => $validated['order_info'],
            ]);
        });

        if (!$giao_dich) {
            return response()->json(['error' => 'Tạo giao dịch thất bại'], 500);
        }

        // 🔹 Cấu hình thông tin VNPay
        $vnp_Url = env('VNPAY_URL');
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $validated['amount'] * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $request->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $validated['order_info'],
            "vnp_OrderType" => "250000",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => (string)$giao_dich->id,
        ];

        ksort($inputData);
        $hashData = urldecode(http_build_query($inputData));
        $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnp_SecureHash;

        return response()->json(['redirect_url' => $vnp_Url]);
    }

    /**
     * ✅ Callback từ VNPay (người dùng quay lại web sau thanh toán)
     */
    public function handleVNPayCallback(Request $request)
    {
        Log::info('VNPAY Callback Received', $request->all());

        $inputData = collect($request->all())
            ->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))
            ->toArray();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = collect($inputData)
            ->map(fn($v, $k) => rawurlencode($k) . '=' . rawurlencode($v))
            ->implode('&');
        $secureHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        if ($secureHash !== strtoupper($vnp_SecureHash)) {
            Log::error('Invalid VNPAY Signature');
            return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) {
            return view('payment.fail', ['code' => '01', 'message' => 'Không tìm thấy giao dịch']);
        }

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) {
            return view('payment.fail', ['code' => '02', 'message' => 'Không tìm thấy đơn đặt phòng']);
        }

        // ✅ Cập nhật giao dịch và đơn phòng
        DB::transaction(function () use ($vnp_ResponseCode, $vnp_Amount, $inputData, $giao_dich, $dat_phong) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'dang_cho_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)->queue(new PaymentSuccess($dat_phong, $dat_phong->nguoiDung->name));
                }
            } else {
                $giao_dich->update([
                    'trang_thai' => 'that_bai',
                    'ghi_chu' => 'Mã lỗi: ' . $vnp_ResponseCode,
                ]);

                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)->queue(new PaymentFail($dat_phong, $vnp_ResponseCode));
                }
            }
        });

        return $vnp_ResponseCode === '00'
            ? view('payment.success', compact('dat_phong'))
            : view('payment.fail', ['code' => $vnp_ResponseCode]);
    }

    /**
     * ✅ IPN (Server-to-Server callback từ VNPay)
     */
    public function handleIpn(Request $request)
    {
        $inputData = collect($request->all())->toArray();
        $receivedSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = collect($inputData)->map(fn($v, $k) => rawurlencode($k) . '=' . rawurlencode($v))->implode('&');
        $calculatedHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        if ($calculatedHash !== strtoupper($receivedSecureHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) {
            return response()->json(['RspCode' => '01', 'Message' => 'Transaction not found']);
        }

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) {
            return response()->json(['RspCode' => '02', 'Message' => 'Booking not found']);
        }

        if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
            DB::transaction(function () use ($giao_dich, $dat_phong, $inputData) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'dang_cho_xac_nhan',
                    'can_xac_nhan' => true,
                ]);
            });
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
        }

        $giao_dich->update(['trang_thai' => 'that_bai']);
        return response()->json(['RspCode' => '99', 'Message' => 'Payment failed']);
    }

    /**
     * ✅ Trang hiển thị danh sách thanh toán đang chờ xác nhận
     */
   public function pendingPayments()
{
    $pendingPayments = DatPhong::with(['nguoiDung', 'giaoDichs'])
        ->whereIn('trang_thai', ['dang_cho_xac_nhan', 'dang_cho'])
        ->where(function ($q) {
            $q->where('can_xac_nhan', true)
              ->orWhere('can_thanh_toan', true);
        })
        ->whereHas('giaoDichs', function ($q) {
            $q->whereIn('trang_thai', ['thanh_cong', 'dang_cho']);
        })
        ->orderByDesc('updated_at')
        ->get();

    return view('payment.pending_payments', compact('pendingPayments'));
}

}
