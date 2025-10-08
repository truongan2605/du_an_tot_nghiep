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
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Tạo URL thanh toán VNPAY
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'dat_phong_id' => 'required|integer|exists:dat_phong,id',
            'amount' => 'required|numeric|min:0',
            'return_url' => 'required|url',
            'order_info' => 'required|string',
        ]);

        $datPhong = DatPhong::findOrFail($request->dat_phong_id);

        // ✅ Tạo bản ghi giao dịch mới
        $giao_dich = GiaoDich::create([
            'dat_phong_id' => $datPhong->id,
            'nha_cung_cap' => 'vnpay',
            'so_tien' => $request->amount,
            'trang_thai' => 'dang_cho',
        ]);

        // ✅ Dùng ID làm mã giao dịch (vnp_TxnRef)
        $vnp_TxnRef = $giao_dich->id;
        $vnp_OrderInfo = $request->order_info;
        $vnp_Amount = $request->amount * 100;
        $vnp_Returnurl = $request->return_url;
        $vnp_IpAddr = $request->ip();

        // Cấu hình VNPay
        $vnp_TmnCode = env('VNPAY_TMN_CODE', 'YOUR_VNPAY_TMNCODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET', 'YOUR_VNPAY_HASH_SECRET');
        $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);
        $hashdata = urldecode(http_build_query($inputData));
        $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnp_SecureHash;

        return response()->json([
            'payment_url' => $vnp_Url,
            'txn_ref' => $vnp_TxnRef,
            'message' => 'Tạo URL thanh toán thành công',
        ]);
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
            return view('payment.fail', [
                'code' => '97',
                'message' => 'Chữ ký không hợp lệ',
            ]);
        }

        // Lấy thông tin giao dịch theo ID (vnp_TxnRef)
        $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '99';

        $giao_dich = GiaoDich::find($vnp_TxnRef);
        if (!$giao_dich) {
            return view('payment.fail', [
                'code' => '01',
                'message' => 'Đơn hàng không tồn tại',
            ]);
        }

        if ($giao_dich->trang_thai !== 'dang_cho') {
            return view('payment.success', [
                'message' => 'Đơn hàng đã được xác nhận trước đó',
            ]);
        }

        // Kiểm tra số tiền
        $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;
        if ($vnp_Amount != $giao_dich->so_tien) {
            $giao_dich->update(['trang_thai' => 'that_bai']);
            return view('payment.fail', [
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

        // Trả về view phù hợp
        if ($vnp_ResponseCode === '00') {
            return view('payment.success', [
                'dat_phong' => $giao_dich->dat_phong,
            ]);
        }

        return view('payment.fail', [
            'code' => $vnp_ResponseCode,
        ]);
    }
}
