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

    public function index()
    {
        $pendingPayments = DatPhong::where('can_xac_nhan', true)->with('nguoiDung', 'giaoDichs')->get();
        return view('payment.pending_payments', compact('pendingPayments'));
    }
    /**
     * Tạo URL thanh toán VNPAY
     */
   public function initiateVNPay(Request $request)
{
    $validated = $request->validate([
        'dat_phong_id' => 'required|exists:dat_phong,id',
        'amount' => 'required|numeric|min:1',
        'order_info' => 'required|string|max:255',
    ]);

    $dat_phong = DatPhong::findOrFail($validated['dat_phong_id']);
    if ($dat_phong->nguoi_dung_id !== Auth::id()) {
        return response()->json(['error' => 'Booking không thuộc bạn'], 403);
    }
    if ($dat_phong->trang_thai !== 'dang_cho') {
        return response()->json(['error' => 'Booking không ở trạng thái chờ'], 400);
    }
    if ($validated['amount'] != $dat_phong->tong_tien) {
        return response()->json(['error' => 'Số tiền không khớp'], 400);
    }

    DB::transaction(function () use ($validated, $dat_phong) {
        GiaoDich::create([
            'dat_phong_id' => $dat_phong->id,
            'nha_cung_cap' => 'vnpay',
            'so_tien' => $validated['amount'],
            'don_vi' => $dat_phong->don_vi_tien,
            'trang_thai' => 'dang_cho',
            'ghi_chu' => $validated['order_info'],
        ]);
    });

    // Tạo URL VNPay
    $vnp_Url = env('VNPAY_URL');
    $vnp_TmnCode = env('VNPAY_TMN_CODE');
    $vnp_HashSecret = env('VNPAY_HASH_SECRET');
    $vnp_ReturnUrl = env('VNPAY_RETURN_URL');
    $vnp_IpnUrl = env('VNPAY_IPN_URL');
    $vnp_TxnRef = time(); // Mã giao dịch unique
    $vnp_OrderInfo = $validated['order_info'];
    $vnp_OrderType = '250000'; // Loại hàng hóa (khách sạn)
    $vnp_Amount = $validated['amount'] * 100; // VNPay dùng đơn vị nhỏ (đơn vị 1 = 0.01 VND)
    $vnp_Locale = 'vn';
    $vnp_CurrCode = 'VND';
    $vnp_IpAddr = $request->ip();

    $inputData = [
        "vnp_Version" => '2.1.0',
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => 'pay',
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode" => $vnp_CurrCode,
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_ReturnUrl,
        "vnp_TxnRef" => $vnp_TxnRef,
    ];

    ksort($inputData);
    $query = http_build_query($inputData, '', '&');
    $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
    $vnp_Url = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

    return response()->json(['redirect_url' => $vnp_Url]);
}

    public function createPayment()
    {
        $vnp_TmnCode = env('VNPAY_TMN_CODE'); 
        $vnp_HashSecret = env('VNPAY_HASH_SECRET'); 
        $vnp_Url = env('VNPAY_URL');
        $vnp_Returnurl = env('VNPAY_RETURN_URL');

        $vnp_TxnRef = 'TEST' . time(); // mã đơn hàng
        $vnp_OrderInfo = 'Thanh toán đơn hàng test';
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = 2000000 * 100; // nhân 100
        $vnp_Locale = 'vn';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = request()->ip();

        $inputData = array(
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
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );

        // Sắp xếp tham số theo key
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

        return redirect($vnp_Url);
    }
    /**
     * Xử lý callback từ VNPAY
     */
    public function handleVNPayCallback(Request $request)
    {
        Log::info('VNPAY Callback Received', $request->all());

        // Lọc các tham số bắt đầu bằng 'vnp_'
        $inputData = collect($request->all())
            ->filter(fn($value, $key) => str_starts_with($key, 'vnp_'))
            ->toArray();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash']);

        // Tạo chuỗi hash để kiểm tra chữ ký
        ksort($inputData);
        $hashData = collect($inputData)
            ->map(fn($v, $k) => urlencode($k) . '=' . urlencode($v))
            ->implode('&');

        $secureHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        // Kiểm tra chữ ký
        if ($secureHash !== $vnp_SecureHash) {
            Log::error('Invalid Secure Hash', [
                'calculated' => $secureHash,
                'received' => $vnp_SecureHash,
            ]);
            return view('payment.return_fail', [
                'code' => '97',
                'message' => 'Chữ ký không hợp lệ',
            ]);
        }

        // Lấy thông tin giao dịch theo ID (vnp_TxnRef)
        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '99';

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) {
            return view('payment.return_fail', [
                'code' => '01',
                'message' => 'Đơn hàng không tồn tại',
            ]);
        }

        if ($giao_dich->trang_thai !== 'dang_cho') {
            return view('payment.return_success', [
                'message' => 'Đơn hàng đã được xác nhận trước đó',
            ]);
        }

        // Kiểm tra số tiền
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;
        if ($vnp_Amount != $giao_dich->so_tien) {
            $giao_dich->update(['trang_thai' => 'that_bai']);
            return view('payment.return_fail', [
                'code' => '04',
                'message' => 'Số tiền không khớp',
            ]);
        }

        // Xử lý cập nhật giao dịch
        DB::transaction(function () use ($giao_dich, $inputData) {
            $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '99';
            if ($vnp_ResponseCode === '00') {
                $giao_dich->trang_thai = 'thanh_cong';
                $giao_dich->provider_txn_ref = $inputData['vnp_TransactionNo'] ?? '';
                $giao_dich->save();

                $dat_phong = $giao_dich->dat_phong;
                if (!$dat_phong) {
                    throw new \Exception('DatPhong not found for GiaoDich');
                }
                $dat_phong->trang_thai = 'dang_cho_xac_nhan';
                $dat_phong->can_xac_nhan = true;
                $dat_phong->save();

                $user = $dat_phong->nguoiDung;
                if ($user && $user->vai_tro === 'khach_hang') {
                    Mail::to($user->email)->queue(new \App\Mail\PaymentSuccess($dat_phong, $user->name));
                }
            } else {
                $giao_dich->trang_thai = 'that_bai';
                $giao_dich->ghi_chu = 'Mã lỗi VNPAY: ' . $vnp_ResponseCode;
                $giao_dich->save();

                $dat_phong = $giao_dich->dat_phong;
                if ($dat_phong) {
                    $user = $dat_phong->nguoiDung;
                    if ($user) {
                        Mail::to($user->email)->queue(new \App\Mail\PaymentFail($dat_phong, $vnp_ResponseCode));
                    }
                }
            }
        });



      
        if ($vnp_ResponseCode === '00') {
            return view('payment.return_success', [
                'dat_phong' => $giao_dich->dat_phong,
            ]);
        }

        return view('payment.return_fail', [
            'code' => $vnp_ResponseCode,
        ]);
    }

public function pendingPayments()
    {
        $pendingPayments = DatPhong::where('can_xac_nhan', true)->with('nguoiDung', 'giaoDichs')->get();
        return view('payment.pending_payments', compact('pendingPayments'));
    }

    public function handleReturn(Request $request)
    {
        $vnp_ResponseCode = $request->input('vnp_ResponseCode');
        $vnp_TxnRef = $request->input('vnp_TxnRef');

        if ($vnp_ResponseCode == '00') {
            $dat_phong = DatPhong::where('ma_tham_chieu', $vnp_TxnRef)->first();
            if ($dat_phong) {
               return view('payment.return_success', ['dat_phong' => $dat_phong])->with('redirect_url', route('pending.payments'));
            }
        }

        return view('payment.return_fail');
    }

    public function handleIpn(Request $request)
    {
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        $hashData = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET'));

        if ($secureHash === $vnp_SecureHash) {
            $vnp_ResponseCode = $inputData['vnp_ResponseCode'];
            $vnp_TxnRef = $inputData['vnp_TxnRef'];
            $vnp_Amount = $inputData['vnp_Amount'] / 100;

            if ($vnp_ResponseCode == '00') {
                $dat_phong = DatPhong::where('ma_tham_chieu', $vnp_TxnRef)->first();
                if ($dat_phong && $dat_phong->tong_tien == $vnp_Amount) {
                    DB::transaction(function () use ($dat_phong, $vnp_Amount, $inputData) {
                        GiaoDich::create([
                            'dat_phong_id' => $dat_phong->id,
                            'so_tien' => $vnp_Amount,
                            'trang_thai' => 'thanh_cong',
                            'provider_txn_ref' => $inputData['vnp_TransactionNo'],
                            'ghi_chu' => $inputData['vnp_OrderInfo'],
                        ]);

                        $dat_phong->trang_thai = 'dang_cho_xac_nhan';
                        $dat_phong->can_xac_nhan = true;
                        $dat_phong->save();
                    });
                }
            }
        }

        return response()->json(['rspCode' => '00', 'message' => 'OK']);
    }
}
