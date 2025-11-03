<?php

namespace App\Http\Controllers\Client;

use Carbon\Carbon;
use App\Models\Phong;
use App\Models\DatPhong;
use App\Models\GiaoDich;
use App\Models\GiuPhong;
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

    public function initiateVNPay(Request $request)
    {
        Log::info('🔹 initiateVNPay request:', $request->all());

        try {
           
            $validated = $request->validate([
                'phong_id' => 'required|exists:phong,id',
                'ngay_nhan_phong' => 'required|date|after_or_equal:today',  
                'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',  
                'amount' => 'required|numeric|min:1',
                'total_amount' => 'required|numeric|min:1|gte:amount',
                'so_khach' => 'nullable|integer|min:1',
                'adults' => 'required|integer|min:1',
                'children' => 'nullable|integer|min:0',
                'children_ages' => 'nullable|array',
                'children_ages.*' => 'integer|min:0|max:12',
                'addons' => 'nullable|array',
                'rooms_count' => 'required|integer|min:1',
            ]);

            $expectedDeposit = $validated['total_amount'] * 0.2;
            if (abs($validated['amount'] - $expectedDeposit) > 1000) {
                return response()->json(['error' => 'Deposit không hợp lệ (phải khoảng 20% tổng)'], 400);
            }

            $phong = Phong::findOrFail($validated['phong_id']);

         
            $maThamChieu = 'DP' . strtoupper(Str::random(8));

          
            $snapshotMeta = [
                'phong_id' => $validated['phong_id'],
                'loai_phong_id' => $phong->loai_phong_id,
                'adults' => $validated['adults'],
                'children' => $validated['children'] ?? 0,
                'children_ages' => $validated['children_ages'] ?? [],
                'addons' => $validated['addons'] ?? [],
                'rooms_count' => $validated['rooms_count'],
                'tong_tien' => $validated['amount'],
                'nights' => $this->calculateNights($validated['ngay_nhan_phong'], $validated['ngay_tra_phong']),  // SỬA: Truyền tên đúng
            ];

       
            return DB::transaction(function () use ($validated, $maThamChieu, $snapshotMeta, $phong, $request) {
             
                $dat_phong = DatPhong::create([
                    'ma_tham_chieu' => $maThamChieu,
                    'nguoi_dung_id' => Auth::id(),
                    'phong_id' => $validated['phong_id'],
                    'ngay_nhan_phong' => $validated['ngay_nhan_phong'],  
                    'ngay_tra_phong' => $validated['ngay_tra_phong'],   
                    'tong_tien' => $validated['total_amount'],
                    'deposit_amount' => $validated['amount'],
                    'so_khach' => $validated['so_khach'] ?? ($validated['adults'] + ($validated['children'] ?? 0)),
                    'trang_thai' => 'dang_cho',
                    'can_thanh_toan' => true,
                    'can_xac_nhan' => false,
                    'created_by' => Auth::id(),
                    'snapshot_meta' => json_encode($snapshotMeta),
                ]);

              
                GiuPhong::create([
                    'dat_phong_id' => $dat_phong->id,
                    'loai_phong_id' => $phong->loai_phong_id,
                    'phong_id' => $validated['phong_id'],
                    'so_luong' => $validated['rooms_count'],
                    'het_han_luc' => now()->addMinutes(15),
                    'released' => false,
                    'meta' => json_encode([
                        'price_per_night' => $validated['amount'] / $this->calculateNights($validated['ngay_nhan_phong'], $validated['ngay_tra_phong']),  
                        'nights' => $this->calculateNights($validated['ngay_nhan_phong'], $validated['ngay_tra_phong']), 
                        'addons' => $validated['addons'] ?? [],
                    ]),
                    'spec_signature_hash' => $this->generateSpecSignatureHash($validated), 
                ]);

        
                $giao_dich = GiaoDich::create([
                    'dat_phong_id' => $dat_phong->id,
                    'nha_cung_cap' => 'vnpay',
                    'so_tien' => $validated['amount'],
                    'don_vi' => 'VND',
                    'trang_thai' => 'dang_cho',
                    'ghi_chu' => "Thanh toán đặt cọc phòng:{$dat_phong->ma_tham_chieu}",
                ]);

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
                    "vnp_OrderInfo" => "Thanh toán đặt phòng {$dat_phong->ma_tham_chieu}",
                    "vnp_OrderType" => "billpayment",
                    "vnp_ReturnUrl" => $vnp_ReturnUrl,
                    "vnp_TxnRef" => (string)$giao_dich->id,
                ];

                ksort($inputData);
                $query = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
                $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
                $redirectUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

                return response()->json(['redirect_url' => $redirectUrl, 'dat_phong_id' => $dat_phong->id]);
            });
        } catch (\Throwable $e) {
            Log::error('VNPay initiate error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Callback từ VNPAY khi người dùng quay lại
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

        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
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

        return DB::transaction(function () use ($vnp_ResponseCode, $vnp_Amount, $inputData, $giao_dich, $dat_phong) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'dang_cho_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

             
                $giu_phong = GiuPhong::where('dat_phong_id', $dat_phong->id)->first();
                if ($giu_phong) {
                    $meta = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                    Log::debug('GiuPhong Meta', [
                        'dat_phong_id' => $dat_phong->id,
                        'meta' => $meta,
                        'is_array' => is_array($meta),
                        'meta_type' => gettype($meta),
                    ]);

                    if (!is_array($meta)) {
                        $meta = [];
                    }

                    $nights = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    $price_per_night = $meta['price_per_night'] ?? ($dat_phong->tong_tien / max(1, $nights));

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $giu_phong->phong_id,
                        'loai_phong_id' => $giu_phong->loai_phong_id,
                        'so_dem' => $nights,
                        'so_luong' => $giu_phong->so_luong ?? 1,
                        'gia_tren_dem' => $price_per_night,
                        'tong_item' => $dat_phong->tong_tien,
                    ];
                    Log::debug('Inserting dat_phong_item', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload' => $itemPayload,
                    ]);
                    \App\Models\DatPhongItem::create($itemPayload);

                 
                    Phong::where('id', $giu_phong->phong_id)->update(['trang_thai' => 'dang_o']);

                    $giu_phong->delete();
                }

                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)
                        ->queue(new PaymentSuccess($dat_phong, $dat_phong->nguoiDung->name));
                }

                return view('payment.success', compact('dat_phong'));
            } else {
                $giao_dich->update(['trang_thai' => 'that_bai', 'ghi_chu' => 'Mã lỗi: ' . $vnp_ResponseCode]);
                if ($dat_phong->nguoiDung) {
                    Mail::to($dat_phong->nguoiDung->email)
                        ->queue(new PaymentFail($dat_phong, $vnp_ResponseCode));
                }
                return view('payment.fail', ['code' => $vnp_ResponseCode]);
            }
        });
    }

    public function handleIpn(Request $request)
    {
        Log::info('VNPAY IPN Received', $request->all());

        $inputData = collect($request->all())->toArray();
        $receivedSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);
        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $calculatedHash = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        Log::info('VNPAY IPN Signature Check', [
            'hashData' => $hashData,
            'calculatedHash' => $calculatedHash,
            'receivedHash' => strtoupper($receivedSecureHash),
            'match' => ($calculatedHash === strtoupper($receivedSecureHash)),
        ]);

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

        return DB::transaction(function () use ($giao_dich, $dat_phong, $vnp_ResponseCode, $vnp_Amount, $inputData) {
            if ($vnp_ResponseCode === '00' && $giao_dich->so_tien == $vnp_Amount) {
                $giao_dich->update([
                    'trang_thai' => 'thanh_cong',
                    'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? '',
                ]);
                $dat_phong->update([
                    'trang_thai' => 'dang_cho_xac_nhan',
                    'can_xac_nhan' => true,
                ]);

             
                $giu_phong = GiuPhong::where('dat_phong_id', $dat_phong->id)->first();
                if ($giu_phong) {
                    $meta = is_string($giu_phong->meta) ? json_decode($giu_phong->meta, true) : $giu_phong->meta;
                    Log::debug('GiuPhong Meta', [
                        'dat_phong_id' => $dat_phong->id,
                        'meta' => $meta,
                        'is_array' => is_array($meta),
                        'meta_type' => gettype($meta),
                    ]);

                    if (!is_array($meta)) {
                        $meta = [];
                    }

                    $nights = $meta['nights'] ?? $this->calculateNights($dat_phong->ngay_nhan_phong, $dat_phong->ngay_tra_phong);
                    $price_per_night = $meta['price_per_night'] ?? ($dat_phong->tong_tien / max(1, $nights));

                    $itemPayload = [
                        'dat_phong_id' => $dat_phong->id,
                        'phong_id' => $giu_phong->phong_id,
                        'loai_phong_id' => $giu_phong->loai_phong_id,
                        'so_dem' => $nights,
                        'so_luong' => $giu_phong->so_luong ?? 1,
                        'gia_tren_dem' => $price_per_night,
                        'tong_item' => $dat_phong->tong_tien,
                    ];
                    Log::debug('Inserting dat_phong_item', [
                        'dat_phong_id' => $dat_phong->id,
                        'payload' => $itemPayload,
                    ]);
                    \App\Models\DatPhongItem::create($itemPayload);

            
                    Phong::where('id', $giu_phong->phong_id)->update(['trang_thai' => 'dang_o']);

                    $giu_phong->delete();
                }

                return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
            }

            $giao_dich->update(['trang_thai' => 'that_bai']);
            return response()->json(['RspCode' => '99', 'Message' => 'Payment failed']);
        });
    }
    /**
     * Danh sách thanh toán đang chờ
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

    /**
     * Mô phỏng callback VNPAY
     */
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
        $hashData = http_build_query($testData, '', '&', PHP_QUERY_RFC1738);
        $testData["vnp_SecureHash"] = strtoupper(hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET')));

        return redirect()->route('payment.callback', $testData);
    }

    /**
     * Tạo thanh toán cho đặt phòng hiện có
     */
    public function createPayment(Request $request)
    {
        $dat_phong_id = $request->input('dat_phong_id');
        $dat_phong = DatPhong::findOrFail($dat_phong_id);

        if ($dat_phong->nguoi_dung_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền thanh toán đơn này.');
        }
        if ($dat_phong->trang_thai !== 'dang_cho') {
            abort(400, 'Đơn này không ở trạng thái chờ thanh toán.');
        }

        return DB::transaction(function () use ($dat_phong, $request) {
            $giao_dich = GiaoDich::create([
                'dat_phong_id' => $dat_phong->id,
                'nha_cung_cap' => 'vnpay',
                'so_tien' => $dat_phong->tong_tien,
                'don_vi' => $dat_phong->don_vi_tien ?? 'VND',
                'trang_thai' => 'dang_cho',
                'ghi_chu' => 'Thanh toán đặt cọc phòng:' . $dat_phong->id,
            ]);

            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $vnp_Url = env('VNPAY_URL');
            $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

            $vnp_TxnRef = (string)$giao_dich->id;
            $vnp_OrderInfo = 'Thanh toán đơn đặt phòng #' . $dat_phong->id;
            $vnp_OrderType = 'billpayment';
            $vnp_Amount = $dat_phong->tong_tien * 100;
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
                "vnp_BankCode" => 'NCB',
            ];

            ksort($inputData);
            $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
            $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

            $vnp_Url .= '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;

            return redirect()->away($vnp_Url);
        });
    }

    /**
     * Tính số đêm
     */
    private function calculateNights($ngayNhanPhong, $ngayTraPhong)  
    {
        $from = new \DateTime($ngayNhanPhong);
        $to = new \DateTime($ngayTraPhong);
        return max(1, $from->diff($to)->days);
    }

    /**
     * Tạo hash cho spec_signature_hash
     */
    private function generateSpecSignatureHash($data)
    {
      
        return md5(
            $data['phong_id'] . 
            $data['ngay_nhan_phong'] .  
            $data['ngay_tra_phong'] .   
            json_encode($data['addons'] ?? [])
        );
    }

public function initiateRemainingPayment(Request $request, $dat_phong_id)
{
    $request->validate([
        'nha_cung_cap' => 'required|in:tien_mat,vnpay'
    ]);

    $booking = DatPhong::with(['giaoDichs', 'nguoiDung'])
        ->lockForUpdate()
        ->findOrFail($dat_phong_id);

    if (!in_array($booking->trang_thai, ['da_xac_nhan', 'da_gan_phong'])) {
        return back()->with('error', 'Booking không hợp lệ để thanh toán phần còn lại.');
    }

    $paid = $booking->giaoDichs()->where('trang_thai', 'thanh_cong')->sum('so_tien');
    $remaining = $booking->tong_tien - $paid;

    if ($remaining <= 0) {
        return back()->with('error', 'Đã thanh toán đủ, không cần thanh toán thêm.');
    }

    $transaction = DB::transaction(function () use ($booking, $remaining, $request) {
        $nhaCungCap = $request->nha_cung_cap;
        $trangThai = $request->nha_cung_cap === 'tien_mat' ? 'thanh_cong' : 'dang_cho';

        $giaoDich = GiaoDich::create([
            'dat_phong_id'     => $booking->id,
            'nha_cung_cap'     => $nhaCungCap,
            'so_tien'          => $remaining,
            'don_vi'           => 'VND',
            'trang_thai'       => $trangThai,
            'provider_txn_ref' => null,
            'ghi_chu'          => "Thanh toán phần còn lại booking: {$booking->ma_tham_chieu}",
        ]);

        Log::info('Created remaining payment transaction', [
            'giao_dich_id' => $giaoDich->id,
            'nha_cung_cap' => $giaoDich->nha_cung_cap,
            'so_tien' => $giaoDich->so_tien,
            'trang_thai' => $giaoDich->trang_thai,
        ]);

        if ($request->nha_cung_cap === 'tien_mat') {
            $booking->update([
                'trang_thai'    => 'dang_su_dung',
                'checked_in_at' => now(),
            ]);
        }

        return $giaoDich;
    });

    if ($request->nha_cung_cap === 'vnpay') {
        return $this->redirectToVNPay($transaction, $remaining);
    }

    return redirect()->route('staff.checkin')
        ->with('success', 'Thanh toán tiền mặt thành công. Phòng đã được đưa vào sử dụng.');
}

public function handleRemainingCallback(Request $request)
{
    Log::info('VNPAY Remaining Payment Callback', $request->all());

    $vnp_HashSecret = env('VNPAY_HASH_SECRET');

    $inputData = collect($request->all())
        ->filter(fn($v, $k) => str_starts_with($k, 'vnp_'))
        ->toArray();

    $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
    unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);
    
    ksort($inputData);
    $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
    $localHash = strtoupper(hash_hmac('sha512', $hashData, $vnp_HashSecret));

    Log::info('VNPAY Signature Check', [
        'hashData' => $hashData,
        'localHash' => $localHash,
        'remoteHash' => strtoupper($vnp_SecureHash),
        'match' => ($localHash === strtoupper($vnp_SecureHash)),
    ]);

    if ($localHash !== strtoupper($vnp_SecureHash)) {
        Log::error('VNPAY signature mismatch');
        return redirect()->route('staff.checkin')->with('error', 'Chữ ký không hợp lệ.');
    }

    $vnp_TxnRef = $inputData['vnp_TxnRef'] ?? '';
    $vnp_ResponseCode = $inputData['vnp_ResponseCode'] ?? '';
    $vnp_Amount = ($inputData['vnp_Amount'] ?? 0) / 100;

    Log::info('Looking for transaction', ['vnp_TxnRef' => $vnp_TxnRef]);

    $transaction = GiaoDich::find($vnp_TxnRef);

    if (!$transaction) {
        Log::error('Transaction not found', ['vnp_TxnRef' => $vnp_TxnRef]);
        return redirect()->route('staff.checkin')->with('error', 'Không tìm thấy giao dịch hợp lệ.');
    }

    if ($transaction->nha_cung_cap !== 'vnpay') {
        Log::error('Invalid payment provider', [
            'nha_cung_cap' => $transaction->nha_cung_cap,
            'transaction_id' => $transaction->id,
        ]);
        return redirect()->route('staff.checkin')->with('error', 'Nhà cung cấp thanh toán không hợp lệ.');
    }

    if ($transaction->trang_thai === 'thanh_cong') {
        return redirect()->route('staff.checkin')->with('success', 'Thanh toán đã được xử lý trước đó.');
    }

    if ($transaction->trang_thai !== 'dang_cho') {
        Log::warning('Transaction not pending', ['status' => $transaction->trang_thai]);
        return redirect()->route('staff.checkin')->with('error', 'Giao dịch không ở trạng thái chờ xử lý.');
    }

    if ($vnp_ResponseCode !== '00') {
        $transaction->update([
            'trang_thai' => 'that_bai',
            'ghi_chu'    => 'VNPay lỗi: ' . $vnp_ResponseCode,
        ]);
        
        Log::warning('Payment failed', ['response_code' => $vnp_ResponseCode]);
        
        return redirect()->route('staff.checkin')
            ->with('error', 'Thanh toán thất bại. Mã lỗi: ' . $vnp_ResponseCode);
    }

    if (abs($transaction->so_tien - $vnp_Amount) > 1) {
        Log::error('Amount mismatch', [
            'expected' => $transaction->so_tien,
            'received' => $vnp_Amount,
        ]);
        return redirect()->route('staff.checkin')->with('error', 'Số tiền không khớp.');
    }

    return DB::transaction(function () use ($transaction, $inputData) {
       
        $transaction->update([
            'trang_thai'       => 'thanh_cong',
            'provider_txn_ref' => $inputData['vnp_TransactionNo'] ?? null,
            'ghi_chu'          => 'Thanh toán phần còn lại thành công qua VNPAY',
        ]);

        Log::info('Transaction updated to success', [
            'transaction_id' => $transaction->id,
            'provider_txn_ref' => $transaction->provider_txn_ref,
        ]);

        $booking = $transaction->datPhong;

        if (!$booking) {
            Log::error('Booking not found for transaction', ['transaction_id' => $transaction->id]);
            return redirect()->route('staff.checkin')->with('success', 'Thanh toán đặt phòng thành công.');
        }

        Log::info('Current booking status BEFORE update', [
            'booking_id' => $booking->id,
            'current_status' => $booking->trang_thai,
            'ma_tham_chieu' => $booking->ma_tham_chieu,
        ]);
        $totalPaid = $booking->giaoDichs()
            ->where('trang_thai', 'thanh_cong')
            ->sum('so_tien');

        Log::info('Payment calculation', [
            'booking_id' => $booking->id,
            'total_paid' => $totalPaid,
            'total_required' => $booking->tong_tien,
            'fully_paid' => ($totalPaid >= $booking->tong_tien),
            'remaining' => $booking->tong_tien - $totalPaid,
        ]);

      
        if ($totalPaid >= $booking->tong_tien) {
            
      
            $oldStatus = $booking->trang_thai;
            $booking->trang_thai = 'dang_su_dung';
            $booking->checked_in_at = now();
            $booking->save();
            
            Log::info('Booking status updated AFTER save', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $booking->trang_thai,
                'checked_in_at' => $booking->checked_in_at,
            ]);

      
            $phongIds = $booking->datPhongItems()->pluck('phong_id')->filter()->toArray();
            if (!empty($phongIds)) {
                Phong::whereIn('id', $phongIds)->update(['trang_thai' => 'dang_o']);
                
                Log::info('Room status updated', [
                    'phong_ids' => $phongIds,
                    'new_status' => 'dang_o',
                ]);
            }

            return redirect()->route('staff.checkin')
                ->with('success', 'Thanh toán thành công! Phòng đã được chuyển sang trạng thái đang sử dụng.');
        }

        Log::warning('Payment not complete yet', [
            'booking_id' => $booking->id,
            'paid' => $totalPaid,
            'required' => $booking->tong_tien,
        ]);

        return redirect()->route('staff.checkin')
            ->with('success', 'Thanh toán thành công! Còn thiếu ' . number_format($booking->tong_tien - $totalPaid) . ' VND.');
    });
}
private function redirectToVNPay(GiaoDich $transaction, float $amount)
{
    $vnp_TmnCode    = env('VNPAY_TMN_CODE');
    $vnp_HashSecret = env('VNPAY_HASH_SECRET');
    $vnp_Url        = env('VNPAY_URL'); 
    $vnp_ReturnUrl  = route('payment.remaining.callback');

    $vnp_TxnRef = (string)$transaction->id;
    $vnp_OrderInfo = 'Thanh toán phần còn lại booking #' . $transaction->dat_phong_id;
    $vnp_Amount = $amount * 100; 

    $inputData = [
        "vnp_Version"    => "2.1.0",
        "vnp_TmnCode"    => $vnp_TmnCode,
        "vnp_Amount"     => $vnp_Amount,
        "vnp_Command"    => "pay",
        "vnp_CreateDate" => date('YmdHis'),
        "vnp_CurrCode"   => "VND",
        "vnp_IpAddr"     => request()->ip(),
        "vnp_Locale"     => "vn",
        "vnp_OrderInfo"  => $vnp_OrderInfo,
        "vnp_OrderType"  => "billpayment",
        "vnp_ReturnUrl"  => $vnp_ReturnUrl,
        "vnp_TxnRef"     => $vnp_TxnRef,
    ];

    ksort($inputData);
    $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
    $vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

    $paymentUrl = $vnp_Url . '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;

    return redirect()->away($paymentUrl);
}
}
