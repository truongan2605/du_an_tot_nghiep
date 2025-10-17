<?php

namespace App\Http\Controllers\Client;

use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Mail\PaymentFail;
use Illuminate\Support\Str;
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
     * ✅ Tạo URL thanh toán VNPAY (Sandbox)
     */
public function initiateVNPay(Request $request)
    {
        Log::info('🔹 initiateVNPay request:', $request->all());

        try {
            $validated = $request->validate([
                'phong_id'   => 'required|exists:phong,id',
                'ngay_nhan'  => 'required|date',
                'ngay_tra'   => 'required|date|after:ngay_nhan',
                'amount'     => 'required|numeric|min:1',
                'so_khach'   => 'nullable|integer|min:1',
            ]);

            $maThamChieu = 'DP' . strtoupper(Str::random(8));

            $dat_phong = DatPhong::create([
                'ma_tham_chieu'   => $maThamChieu,
                'nguoi_dung_id'   => Auth::id(),
                'phong_id'        => $validated['phong_id'],
                'ngay_nhan_phong' => $validated['ngay_nhan'],
                'ngay_tra_phong'  => $validated['ngay_tra'],
                'tong_tien'       => $validated['amount'],
                'so_khach'        => $validated['so_khach'] ?? 1,
                'trang_thai'      => 'dang_cho',
                'can_thanh_toan'  => true,
                'can_xac_nhan'    => false,
                'created_by'      => Auth::id(),
            ]);

            $giao_dich = GiaoDich::create([
                'dat_phong_id' => $dat_phong->id,
                'nha_cung_cap' => 'vnpay',
                'so_tien'      => $validated['amount'],
                'don_vi'       => 'VND',
                'trang_thai'   => 'dang_cho',
                'ghi_chu'      => "Thanh toán đặt phòng #{$dat_phong->ma_tham_chieu}",
            ]);

            // ✅ Dùng các biến trong .env (VNPAY_)
            $vnp_Url        = env('VNPAY_URL');
            $vnp_TmnCode    = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $vnp_ReturnUrl  = env('VNPAY_RETURN_URL');

            $inputData = [
                "vnp_Version"   => "2.1.0",
                "vnp_TmnCode"   => $vnp_TmnCode,
                "vnp_Amount"    => $validated['amount'] * 100,
                "vnp_Command"   => "pay",
                "vnp_CreateDate"=> date('YmdHis'),
                "vnp_CurrCode"  => "VND",
                "vnp_IpAddr"    => $request->ip(),
                "vnp_Locale"    => "vn",
                "vnp_OrderInfo" => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                "vnp_OrderType" => "billpayment",
                "vnp_ReturnUrl" => $vnp_ReturnUrl,
                "vnp_TxnRef"    => (string)$giao_dich->id,
            ];

            ksort($inputData);
            $query = http_build_query($inputData, '', '&', 1);

            $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $redirectUrl = $vnp_Url . '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnp_SecureHash;

            return response()->json(['redirect_url' => $redirectUrl]);
        } catch (\Throwable $e) {
            Log::error('🔥 VNPay initiate error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
    /**
     * ✅ Callback từ VNPAY khi người dùng quay lại
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

        $hashData = http_build_query($inputData, '', '&', 1);
        $localHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY Signature Check', [
            'hashData' => $hashData,
            'localHash' => $localHash,
            'remoteHash' => strtoupper($vnp_SecureHash),
            'match' => ($localHash === strtoupper($vnp_SecureHash)),
        ]);

        if ($localHash !== strtoupper($vnp_SecureHash)) {
            return view('payment.fail', ['code' => '97', 'message' => 'Chữ ký không hợp lệ']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) return view('payment.fail', ['code' => '01', 'message' => 'Không tìm thấy giao dịch']);

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) return view('payment.fail', ['code' => '02', 'message' => 'Không tìm thấy đơn đặt phòng']);

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
                    Mail::to($dat_phong->nguoiDung->email)
                        ->queue(new PaymentSuccess($dat_phong, $dat_phong->nguoiDung->name));
                }
            } else {
                $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Mã lỗi: ' . $vnp_ResponseCode]);
                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)
                        ->queue(new PaymentFail($dat_phong, $vnp_ResponseCode));
                }
            }
        });

        return $vnp_ResponseCode === '00'
            ? view('payment.success', compact('dat_phong'))
            : view('payment.fail', ['code' => $vnp_ResponseCode]);
    }

    
    /**
     * ✅ IPN (Server-to-Server)
     */
  public function handleIpn(Request $request)
    {
        $inputData = collect($request->all())->toArray();
        $receivedSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
         $hashData = http_build_query($inputData, '', '&', 1);
        $calculatedHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        if ($calculatedHash !== strtoupper($receivedSecureHash)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) return response()->json(['RspCode' => '01', 'Message' => 'Transaction not found']);

        $dat_phong = $giao_dich->dat_phong;
        if (!$dat_phong) return response()->json(['RspCode' => '02', 'Message' => 'Booking not found']);

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
     * ✅ Danh sách thanh toán đang chờ
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
    public function simulateCallback()
{
  
    $testData = [
        "vnp_Amount" => 200000000, 
        "vnp_BankCode" => "NCB",
        "vnp_Command" => "pay",
        "vnp_CreateDate" => now()->format('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => request()->ip(),
        "vnp_Locale" => "vn",
        "vnp_OrderInfo" => "Thanh toan don hang test",
        "vnp_OrderType" => "billpayment",
        "vnp_TmnCode" => env('VNPAY_TMN_CODE'),
        "vnp_TxnRef" => "TESTSIMULATE001",
        "vnp_ResponseCode" => "00", 
        "vnp_TransactionNo" => "999999",
        "vnp_PayDate" => now()->format('YmdHis'),
    ];

   
    ksort($testData);
    $hashData = urldecode(http_build_query($testData));
    $testData["vnp_SecureHash"] = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

    return redirect()->route('payment.callback', $testData);
}

public function createPayment(Request $request)
{
    // ✅ Lấy booking (DatPhong) từ request hoặc query
    $dat_phong_id = $request->input('dat_phong_id');
    $dat_phong = DatPhong::findOrFail($dat_phong_id);

    // Kiểm tra quyền sở hữu & trạng thái
    if ($dat_phong->nguoi_dung_id !== Auth::id()) {
        abort(403, 'Bạn không có quyền thanh toán đơn này.');
    }
    if ($dat_phong->trang_thai !== 'dang_cho') {
        abort(400, 'Đơn này không ở trạng thái chờ thanh toán.');
    }

    // ✅ Cấu hình VNPay
    $vnp_TmnCode    = env('VNPAY_TMN_CODE'); 
    $vnp_HashSecret = env('VNPAY_HASH_SECRET'); 
    $vnp_Url        = env('VNPAY_URL');
    $vnp_ReturnUrl  = env('VNPAY_RETURN_URL');

    // ✅ Tạo giao dịch trong DB
    $giao_dich = DB::transaction(function () use ($dat_phong) {
        return GiaoDich::create([
            'dat_phong_id' => $dat_phong->id,
            'nha_cung_cap' => 'vnpay',
            'so_tien' => $dat_phong->tong_tien,
            'don_vi' => $dat_phong->don_vi_tien ?? 'VND',
            'trang_thai' => 'dang_cho',
            'ghi_chu' => 'Thanh toán đặt phòng #' . $dat_phong->id,
        ]);
    });

    if (!$giao_dich) {
        abort(500, 'Không thể khởi tạo giao dịch.');
    }

    // ✅ Tạo dữ liệu gửi sang VNPay
    $vnp_TxnRef = (string)$giao_dich->id;
    $vnp_OrderInfo = 'Thanh toán đơn đặt phòng #' . $dat_phong->id;
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = $dat_phong->tong_tien * 100; // VNPay tính theo đồng xu
    $vnp_Locale = 'vn';
    $vnp_IpAddr = $request->ip();

    $inputData = [
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_ReturnUrl,
        "vnp_TxnRef" => $vnp_TxnRef,
    ];

   
    $inputData['vnp_BankCode'] = 'NCB';


    ksort($inputData);


    $hashData = urldecode(http_build_query($inputData));
    $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

    $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnp_SecureHash;

 
    return redirect()->away($vnp_Url);
}



}
