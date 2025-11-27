<?php

namespace App\Http\Controllers\Client;

use Carbon\Carbon;
use App\Models\Phong;
use App\Models\Voucher;
use App\Models\DatPhong;
use Illuminate\Support\Str;
use App\Models\VoucherUsage;
use Illuminate\Http\Request;
use App\Events\BookingCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BookingController extends Controller
{
    // Giá tiền với mỗi người quá số người mặc định của mỗi phòng
    public const ADULT_PRICE = 150000;
    public const CHILD_PRICE = 60000;
    public const CHILD_FREE_AGE = 6;

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $upcoming = DatPhong::where('nguoi_dung_id', $user->id)
            ->whereIn('trang_thai', ['dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan', 'dang_su_dung'])
            ->with(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'giaoDichs'])
            ->orderBy('ngay_nhan_phong', 'asc')
            ->get();

        $cancelled = DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'da_huy')
            ->with(['datPhongItems.phong', 'datPhongItems.loaiPhong', 'refundRequests'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $completed = DatPhong::where('nguoi_dung_id', $user->id)
            ->where('trang_thai', 'hoan_thanh')
            ->with([
                'datPhongItems.phong.tang',
                'datPhongItems.loaiPhong',
                'hoaDons.hoaDonItems.phong.tang',
                'hoaDons.hoaDonItems.loaiPhong',
            ])
            ->orderBy('ngay_nhan_phong', 'desc')
            ->get();

        return view('account.bookings', compact('upcoming', 'cancelled', 'completed', 'user'));
    }

    public function show(DatPhong $dat_phong, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($dat_phong->nguoi_dung_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $dat_phong->load([
            'datPhongItems.phong', 
            'datPhongItems.loaiPhong', 
            'datPhongAddons', 
            'voucherUsages', 
            'datPhongItems.datPhong',
            'roomChanges.oldRoom',
            'roomChanges.newRoom'
        ]);

        $meta = is_array($dat_phong->snapshot_meta) ? $dat_phong->snapshot_meta : (json_decode($dat_phong->snapshot_meta, true) ?: []);

        // Calculate days until check-in for room change button visibility (using 14:00 ch eck-in time)
        $checkInDateTime = Carbon::parse($dat_phong->ngay_nhan_phong)->setTime(14, 0, 0);
        $now = Carbon::now();
        $daysUntilCheckIn = $now->diffInDays($checkInDateTime, false);

        // Calculate refund amount for cancel modal
        $depositType = $meta['deposit_percentage'] ?? 50;
        $refundPercentage = $this->calculateRefundPercentage($daysUntilCheckIn, $depositType);
        $paidAmount = $dat_phong->deposit_amount ?? 0;
        $refundAmount = $paidAmount * ($refundPercentage / 100);

        return view('account.booking_show', [
            'booking' => $dat_phong,
            'meta' => $meta,
            'user' => $user,
            'daysUntilCheckIn' => $daysUntilCheckIn,
            'refundAmount' => $refundAmount,
        ]);
    }

    /**
     * Get available rooms for room change
     * Returns list of available rooms of same type during booking dates
     */
    public function getAvailableRooms(Request $request, DatPhong $booking)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Verify ownership
        if ($booking->nguoi_dung_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        // Get current room (support multi-room bookings)
        $oldRoomId = $request->get('old_room_id'); // OLD_ROOM_ID from frontend
        
        // Cast to integer to avoid type mismatch in query
        if ($oldRoomId) {
            $oldRoomId = (int) $oldRoomId;
        }
        
        \Log::info('🔍 Available rooms API called', [
            'booking_id' => $booking->id,
            'old_room_id_param' => $oldRoomId,
            'old_room_id_type' => gettype($oldRoomId),
            'all_booking_room_ids' => $booking->datPhongItems->pluck('phong_id')->toArray()
        ]);
        
        if ($oldRoomId) {
            // Find specific room for multi-room bookings
            $currentItem = $booking->datPhongItems()
                ->where('phong_id', $oldRoomId)
                ->first();
                
            \Log::info('🎯 Found specific room', [
                'current_item_id' => $currentItem ? $currentItem->id : null,
                'current_room_id' => $currentItem ? $currentItem->phong_id : null,
                'query_phong_id' => $oldRoomId
            ]);
        } else {
            // Fallback to first room for backward compatibility
            $currentItem = $booking->datPhongItems->first();
            
            \Log::info('⚠️ No old_room_id, using first room', [
                'current_item_id' => $currentItem ? $currentItem->id : null,
                'current_room_id' => $currentItem ? $currentItem->phong_id : null
            ]);
        }
        
        if (!$currentItem || !$currentItem->phong) {
            return response()->json(['success' => false, 'message' => 'No room assigned'], 404);
        }

        $currentRoom = $currentItem->phong;
        $currentRoomType = $currentItem->loaiPhong;
        $currentPrice = $currentItem->gia_tren_dem ?? 0;

        // Get dates
        $checkIn = Carbon::parse($booking->ngay_nhan_phong);
        $checkOut = Carbon::parse($booking->ngay_tra_phong);

        // Get ALL available rooms (not limited to same type - allow upgrade/downgrade)
        $allRooms = Phong::where('trang_thai', '!=', 'bao_tri')
            ->pluck('id')
            ->toArray();

        // Get booked room IDs in this date range (ANY type)
        $fromStartStr = $checkIn->copy()->setTime(14, 0)->toDateTimeString();
        $toEndStr = $checkOut->copy()->setTime(12, 0)->toDateTimeString();

        $bookedRoomIds = DB::table('dat_phong_item')
            ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
            ->whereNotNull('dat_phong_item.phong_id')
            ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
            ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$toEndStr, $fromStartStr])
            ->pluck('dat_phong_item.phong_id')
            ->toArray();

        // Available = All - Booked - Current
        $availableRoomIds = array_diff($allRooms, $bookedRoomIds, [$currentRoom->id]);

        // Load room details
        $availableRooms = Phong::whereIn('id', $availableRoomIds)
            ->with(['loaiPhong', 'images'])
            ->get()
            ->map(function ($room) use ($currentPrice) {
                $roomPrice = $room->tong_gia ?? $room->gia_mac_dinh ?? 0;
                $priceDiff = $roomPrice - $currentPrice;
                
                // Get image - try multiple sources
                $imagePath = '/images/room-placeholder.jpg'; // Default fallback
                if ($room->images && $room->images->count() > 0) {
                    $firstImage = $room->images->first();
                    if ($firstImage->image_url) {
                        $imagePath = $firstImage->image_url;
                    } elseif ($firstImage->image_path) {
                        $imagePath = asset('storage/' . $firstImage->image_path);
                    }
                }

                return [
                    'id' => $room->id,
                    'code' => $room->ma_phong,
                    'name' => $room->loaiPhong->ten ?? 'Room', // Changed 'name' to 'ten'
                    'type' => $room->loaiPhong->slug ?? 'standard',
                    'price' => $roomPrice, // Using 'price' to match frontend
                    'price_difference' => $priceDiff,
                    'image' => $imagePath,
                    'capacity' => $room->loaiPhong->so_nguoi ?? 2
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'available_rooms' => $availableRooms
        ]);
    }

    /**
     * Process room change request
     * POST /account/bookings/{booking}/change-room
     */
    public function changeRoom(Request $request, DatPhong $booking)
    {
        $user = $request->user();
        
        // 1. Ownership check
        if ($booking->nguoi_dung_id !== $user->id) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }
        
        // 2. Status check
        if (!in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan'])) {
            return back()->with('error', 'Không thể đổi phòng với trạng thái hiện tại.');
        }
        
        // 3. Time check (>= 24h before check-in)
        $checkInDate = Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0, 0);
        $daysUntil = Carbon::now()->diffInDays($checkInDate, false);
        
        if ($daysUntil < 1) {
            return back()->with('error', 'Chỉ có thể đổi phòng trước 24 giờ check-in.');
        }
        
        // 4. Validate request
        $request->validate([
            'old_room_id' => 'required|exists:phong,id',
            'new_room_id' => 'required|exists:phong,id|different:old_room_id'
        ]);
        
        // 5. Get current room item (find specific room in booking)
        $currentItem = $booking->datPhongItems()
            ->where('phong_id', $request->old_room_id)
            ->first();
            
        if (!$currentItem) {
            return back()->with('error', 'Phòng không thuộc booking này.');
        }
        
        $newRoom = Phong::find($request->new_room_id);
        if (!$newRoom) {
            return back()->with('error', 'Phòng mới không tồn tại.');
        }
        
        // 6. Check max changes (limit 2 changes per booking)
        $changeCount = \App\Models\RoomChange::where('dat_phong_id', $booking->id)
            ->where('status', 'completed')
            ->count();
        
        if ($changeCount >= 2) {
            return back()->with('error', 'Đã đạt giới hạn đổi phòng (tối đa 2 lần).');
        }
        
        // 7. Get current room info (already have $currentItem from step 5)
        $currentRoom = $currentItem->phong;
        $currentPrice = $currentItem->gia_tren_dem;
        
        // 8. Calculate prices
        $newPrice = $newRoom->tong_gia ?? $newRoom->gia_mac_dinh ?? 0;
        $checkIn = Carbon::parse($booking->ngay_nhan_phong);
        $checkOut = Carbon::parse($booking->ngay_tra_phong);
        $nights = $checkIn->diffInDays($checkOut);
        
        // Calculate for THIS room change only
        $oldRoomTotal = $currentPrice * $nights;
        $newRoomTotal = $newPrice * $nights;
        $priceDiff = $newRoomTotal - $oldRoomTotal;
        
        // CRITICAL: Calculate FULL BOOKING total after change (for multi-room support)
        $currentBookingTotal = $booking->tong_tien;  // Current total of ALL rooms
        $newBookingTotal = $currentBookingTotal - $oldRoomTotal + $newRoomTotal;  // Remove old, add new
        
        \Log::info('💰 Room change payment calculation', [
            'old_room_total' => $oldRoomTotal,
            'new_room_total' => $newRoomTotal,
            'price_diff' => $priceDiff,
            'current_booking_total' => $currentBookingTotal,
            'new_booking_total' => $newBookingTotal
        ]);
        
        // 9. Create room change record
        $roomChange = \App\Models\RoomChange::create([
            'dat_phong_id' => $booking->id,
            'old_room_id' => $currentRoom->id,
            'new_room_id' => $newRoom->id,
            'old_price' => $currentPrice,
            'new_price' => $newPrice,
            'price_difference' => $priceDiff,
            'nights' => $nights,
            'changed_by_type' => 'customer',
            'changed_by_user_id' => $user->id,
            'status' => 'pending'
        ]);
        
        // 10. Handle payment based on price difference
        if ($priceDiff > 0) {
            // UPGRADE - Need payment
            $depositPct = $booking->snapshot_meta['deposit_percentage'] ?? 50;
            
            // Calculate new deposit based on FULL BOOKING total (not just changed room)
            $newDepositRequired = $newBookingTotal * ($depositPct / 100);
            $paymentNeeded = $newDepositRequired - $booking->deposit_amount;
            
            \Log::info('💳 Upgrade payment calculation', [
                'deposit_pct' => $depositPct,
                'new_booking_total' => $newBookingTotal,
                'new_deposit_required' => $newDepositRequired,
                'already_paid' => $booking->deposit_amount,
                'payment_needed' => $paymentNeeded
            ]);
            
            // Store room_change_id in session for callback
            session(['room_change_id' => $roomChange->id]);
            
            // Redirect to VNPay
            return $this->redirectToVNPayForRoomChange($booking, $roomChange, $paymentNeeded);
            
        } elseif ($priceDiff < 0) {
            // DOWNGRADE - Auto refund via voucher
            $result = $this->completeRoomChange($roomChange);
            
            if ($result) {
                // Calculate refund amount
                $depositPct = $booking->snapshot_meta['deposit_percentage'] ?? 50;
                $newDepositRequired = $newBookingTotal * ($depositPct / 100);
                $refundAmount = $booking->deposit_amount - $newDepositRequired;
                
                // Create voucher for refund
                $voucher = $this->createRefundVoucher($booking, $refundAmount, $roomChange);
                
                $oldRoom = $roomChange->oldRoom;
                $newRoom = $roomChange->newRoom;
                
                return redirect('/account/bookings/' . $roomChange->dat_phong_id)
                    ->with('room_change_success', [
                        'old_room' => $oldRoom->ma_phong ?? 'N/A',
                        'new_room' => $newRoom->ma_phong ?? 'N/A',
                        'price_difference' => $priceDiff,
                        'refund_amount' => $refundAmount,
                        'voucher_code' => $voucher->code
                    ])
                    ->with('success', 'Đổi phòng thành công! Voucher hoàn tiền đã được tạo.');
            } else {
                return back()->with('error', 'Có lỗi khi cập nhật thông tin phòng.');
            }
            
        } else {
            // SAME PRICE - Direct update
            $result = $this->completeRoomChange($roomChange);
            
            if ($result) {
                $oldRoom = $roomChange->oldRoom;
                $newRoom = $roomChange->newRoom;
                
                return redirect('/account/bookings/' . $roomChange->dat_phong_id)
                    ->with('room_change_success', [
                        'old_room' => $oldRoom->ma_phong ?? 'N/A',
                        'new_room' => $newRoom->ma_phong ?? 'N/A',
                        'price_difference' => 0,
                        'payment_amount' => 0
                    ])
                    ->with('success', 'Đổi phòng thành công! Không cần thanh toán thêm (cùng giá).');
            } else {
                return back()->with('error', 'Có lỗi khi cập nhật thông tin phòng.');
            }
        }
    }
    
    /**
     * Redirect to VNPay for room change payment
     */
    private function redirectToVNPayForRoomChange($booking, $roomChange, $amount)
    {
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_ReturnUrl = route('booking.change-room.callback');
        
        $vnp_TxnRef = 'RC' . $roomChange->id . '_' . time(); // Room Change prefix
        $vnp_OrderInfo = 'Thanh toán đổi phòng #' . $booking->ma_tham_chieu;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $amount * 100; // VNPay uses smallest unit
        $vnp_Locale = 'vn';
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
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );
        
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
     * VNPay callback handler for room change
     * GET /account/bookings/change-room/callback
     */
    public function changeRoomCallback(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        
        // Verify signature
        if ($secureHash !== $vnp_SecureHash) {
            return redirect('/account/bookings')->with('error', 'Chữ ký không hợp lệ.');
        }
        
        // Get room change from session
        $roomChangeId = session('room_change_id');
        if (!$roomChangeId) {
            return redirect('/account/bookings')->with('error', 'Không tìm thấy thông tin đổi phòng.');
        }
        
        $roomChange = \App\Models\RoomChange::find($roomChangeId);
        if (!$roomChange) {
            return redirect('/account/bookings')->with('error', 'Không tìm thấy yêu cầu đổi phòng.');
        }
        
        // Check payment result
        if ($request->vnp_ResponseCode == '00') {
            // Payment successful
            $roomChange->payment_info = [
                'vnp_TxnRef' => $request->vnp_TxnRef,
                'vnp_TransactionNo' => $request->vnp_TransactionNo,
                'vnp_Amount' => $request->vnp_Amount / 100,
                'vnp_BankCode' => $request->vnp_BankCode,
                'vnp_PayDate' => $request->vnp_PayDate,
            ];
            $roomChange->save();
            
            // Complete room change
            $result = $this->completeRoomChange($roomChange);
            
            // Clear session
            session()->forget('room_change_id');
            
            if ($result) {
                $oldRoom = $roomChange->oldRoom;
                $newRoom = $roomChange->newRoom;
                $priceDiff = $roomChange->price_difference;
                
                return redirect('/account/bookings/' . $roomChange->dat_phong_id)
                    ->with('room_change_success', [
                        'old_room' => $oldRoom->ma_phong ?? 'N/A',
                        'new_room' => $newRoom->ma_phong ?? 'N/A',
                        'price_difference' => $priceDiff,
                        'payment_amount' => $roomChange->payment_info['vnp_Amount'] ?? 0
                    ])
                    ->with('success', 'Đổi phòng thành công! Thanh toán đã được xác nhận.');
            } else {
                return redirect('/account/bookings/' . $roomChange->dat_phong_id)
                    ->with('error', 'Có lỗi khi cập nhật thông tin phòng.');
            }
        } else {
            // Payment failed
            $roomChange->status = 'failed';
            $roomChange->payment_info = ['error_code' => $request->vnp_ResponseCode];
            $roomChange->save();
            
            session()->forget('room_change_id');
            
            return redirect('/account/bookings/' . $roomChange->dat_phong_id)
                ->with('error', 'Thanh toán không thành công. Vui lòng thử lại.');
        }
    }
    
    /**
     * Complete room change (update booking)
     */
    private function completeRoomChange($roomChange)
    {
        try {
            DB::beginTransaction();
            
            $booking = $roomChange->booking;
            
            // Find the specific room item that was changed (not just first!)
            $currentItem = $booking->datPhongItems()
                ->where('phong_id', $roomChange->old_room_id)
                ->first();
                
            if (!$currentItem) {
                throw new \Exception('Room item not found for room change');
            }
            
            // 1. Update dat_phong_item
            $newRoom = $roomChange->newRoom;
            $currentItem->phong_id = $roomChange->new_room_id;
            $currentItem->loai_phong_id = $newRoom->loai_phong_id; // CRITICAL: Update room type
            $currentItem->gia_tren_dem = $roomChange->new_price;
            $currentItem->save();
            
            // 2. Update dat_phong totals (MULTI-ROOM SUPPORT)
            $oldRoomTotal = $roomChange->old_price * $roomChange->nights;
            $newRoomTotal = $roomChange->new_price * $roomChange->nights;
            
            // Calculate NEW booking total by removing old room and adding new room
            $currentBookingTotal = $booking->tong_tien;  // All rooms current total
            $newBookingTotal = $currentBookingTotal - $oldRoomTotal + $newRoomTotal;
            
            // Calculate new deposit required based on FULL booking
            $depositPct = $booking->snapshot_meta['deposit_percentage'] ?? 50;
            $newDepositRequired = $newBookingTotal * ($depositPct / 100);
            
            // Calculate how much was just paid for THIS change
            $paymentMade = 0;
            if ($roomChange->payment_info && is_array($roomChange->payment_info) && isset($roomChange->payment_info['vnp_Amount'])) {
                $paymentMade = $roomChange->payment_info['vnp_Amount'];
            }
            
            // Update booking totals
            $booking->tong_tien = $newBookingTotal;
            $booking->snapshot_total = $newBookingTotal;
            $booking->deposit_amount = $booking->deposit_amount + $paymentMade;  // Add payment to existing deposit
            $booking->save();
            
            \Log::info('✅ Booking totals updated after room change', [
                'old_room_total' => $oldRoomTotal,
                'new_room_total' => $newRoomTotal,
                'current_booking_total' => $currentBookingTotal,
                'new_booking_total' => $newBookingTotal,
                'old_deposit' => $booking->deposit_amount - $paymentMade,
                'payment_made' => $paymentMade,
                'new_deposit' => $booking->deposit_amount
            ]);
            
            // 3. Create giao_dich record if payment was made
            // Reload room_change to get fresh payment_info
            $roomChange->refresh();
            
            if ($roomChange->payment_info && is_array($roomChange->payment_info) && isset($roomChange->payment_info['vnp_Amount'])) {
                \App\Models\GiaoDich::create([
                    'dat_phong_id' => $booking->id,
                    'nha_cung_cap' => 'vnpay',
                    'provider_txn_ref' => $roomChange->payment_info['vnp_TransactionNo'] ?? null,
                    'so_tien' => $roomChange->payment_info['vnp_Amount'],
                    'don_vi' => 'VND',
                    'trang_thai' => 'thanh_cong',
                    'ghi_chu' => 'Thanh toán đổi phòng - Chênh lệch giá',
                ]);
            } else {
                // No payment needed (same price) - still create a record for audit
                \App\Models\GiaoDich::create([
                    'dat_phong_id' => $booking->id,
                    'nha_cung_cap' => 'system',
                    'provider_txn_ref' => null,
                    'so_tien' => 0,
                    'don_vi' => 'VND',
                    'trang_thai' => 'thanh_cong',
                    'ghi_chu' => 'Đổi phòng cùng giá - Không cần thanh toán',
                ]);
            }
            
            // 4. Update room change status
            $roomChange->status = 'completed';
            $roomChange->save();
            
            // 5. Send email notification
            // TODO: Implement email notification
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Room change completion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create refund voucher for downgrade room change
     */
    private function createRefundVoucher($booking, $refundAmount, $roomChange)
    {
        $code = 'DOWNGRADE' . strtoupper(\Str::random(8));
        $expiryDate = \Carbon\Carbon::now()->addDays(30); // 30 days validity
        
        $voucher = \App\Models\Voucher::create([
            'code' => $code,
            'name' => "Hoàn tiền đổi phòng - {$code}",
            'type' => 'fixed',  // Fixed amount type (not 'tien')
            'value' => $refundAmount,
            'qty' => 1,
            'start_date' => \Carbon\Carbon::now(),
            'end_date' => $expiryDate,
            'min_order_amount' => 0,
            'applicable_to' => 'all',
            'note' => "Hoàn tiền đổi phòng từ {$roomChange->oldRoom->ma_phong} sang {$roomChange->newRoom->ma_phong}",
            'usage_limit_per_user' => 1,
            'active' => true  // Set true so voucher can be used
        ]);
        
        // Link voucher to user using relationship
        $voucher->users()->attach($booking->nguoi_dung_id, [
            'claimed_at' => \Carbon\Carbon::now()
        ]);
        
        \Log::info('🎫 Refund voucher created for downgrade', [
            'voucher_code' => $code,
            'amount' => $refundAmount,
            'booking_id' => $booking->id,
            'room_change_id' => $roomChange->id
        ]);
        
        return $voucher;
    }

    public function create(Phong $phong)
    {
        $phong->load(['loaiPhong', 'tienNghis', 'images', 'bedTypes', 'activeOverrides']);
        $user = Auth::user();

        $typeAmenityIds = $phong->loaiPhong ? $phong->loaiPhong->tienNghis->pluck('id')->toArray() : [];
        $roomAmenityIds = $phong->tienNghis ? $phong->tienNghis->pluck('id')->toArray() : [];
        $allAmenityIds = array_values(array_unique(array_merge($typeAmenityIds, $roomAmenityIds)));

        $availableAddons = \App\Models\TienNghi::where('active', true)
            ->when(!empty($allAmenityIds), function ($q) use ($allAmenityIds) {
                $q->whereNotIn('id', $allAmenityIds);
            })->orderBy('ten')->get();

        $fromDefault = Carbon::today();
        $toDefault = Carbon::tomorrow();

        $availableRoomsDefault = $this->computeAvailableRoomsCount(
            $phong->loai_phong_id,
            $fromDefault,
            $toDefault,
            $phong->spec_signature_hash ?? $phong->specSignatureHash()
        );

        return view('account.booking.create', compact('phong', 'user', 'availableAddons', 'availableRoomsDefault', 'fromDefault', 'toDefault'));
    }

    public function availability(Request $request)
    {
        $request->validate([
            'loai_phong_id' => 'required|integer|exists:loai_phong,id',
            'from' => 'required|date',
            'to' => 'required|date|after:from',
            'phong_id' => 'nullable|integer|exists:phong,id',
        ]);

        $loaiId = (int) $request->input('loai_phong_id');
        $from = Carbon::parse($request->input('from'))->startOfDay();
        $to = Carbon::parse($request->input('to'))->startOfDay();

        $requiredSignature = null;
        if ($request->filled('phong_id')) {
            $phong = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])->find($request->input('phong_id'));
            if ($phong) {
                $requiredSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();
            }
        }

        $available = $this->computeAvailableRoomsCount($loaiId, $from, $to, $requiredSignature);

        if ($request->boolean('debug')) {
            $candidates = Phong::with(['tienNghis', 'bedTypes', 'activeOverrides'])
                ->where('loai_phong_id', $loaiId)
                ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
                ->get();

            $roomSignatures = $candidates->mapWithKeys(function ($r) {
                return [$r->id => $r->specSignatureHash()];
            });

            return response()->json([
                'available' => (int)$available,
                'required_signature' => $requiredSignature,
                'room_signatures' => $roomSignatures,
            ]);
        }

        return response()->json(['available' => (int)$available]);
    }

    private function computeAvailableRoomsCount(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, ?string $requiredSignature = null): int
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        $matchingRoomIds = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
            ->pluck('id')->toArray();

        if (empty($matchingRoomIds)) {
            return 0;
        }

        // 1) Specific booked rooms (dat_phong_item with phong_id) that overlap interval
        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        // 2) Holds that explicitly target rooms (giu_phong.phong_id) where the underlying dat_phong overlaps
        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        // 3) meta-based holds same as prior (unchanged)
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'meta')) {
            $holdsWithMeta = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.meta')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) continue;
                $decoded = null;
                if (is_string($metaRaw)) {
                    $decoded = json_decode($metaRaw, true);
                } elseif (is_array($metaRaw)) {
                    $decoded = $metaRaw;
                }
                if (is_array($decoded) && !empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
        }

        $occupiedSpecificIds = array_unique(array_merge($bookedRoomIds, $heldRoomIds));
        $matchingAvailableIds = array_values(array_diff($matchingRoomIds, $occupiedSpecificIds));
        $matchingAvailableCount = count($matchingAvailableIds);

        // 4) Aggregate booked from dat_phong_item (rows without phong_id) overlapping the interval
        $aggregateBooked = 0;
        if (Schema::hasTable('dat_phong_item')) {
            $q = DB::table('dat_phong')
                ->join('dat_phong_item', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->whereNull('dat_phong_item.phong_id');

            if (Schema::hasColumn('dat_phong_item', 'so_luong')) {
                $aggregateBooked = (int) $q->sum('dat_phong_item.so_luong');
            } else {
                $aggregateBooked = (int) $q->count();
            }
        }

        // 5) Aggregate holds (giu_phong rows without phong_id) that overlap the same dat_phong interval and match signature when available
        $aggregateHoldsForSignature = 0;
        if (Schema::hasTable('giu_phong')) {
            $qg = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr]);

            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                $qg = $qg->where('giu_phong.spec_signature_hash', $requiredSignature);
                if (Schema::hasColumn('giu_phong', 'so_luong')) {
                    $aggregateHoldsForSignature = (int) $qg->sum('giu_phong.so_luong');
                } else {
                    $aggregateHoldsForSignature = (int) $qg->count();
                }
            } else {
                $holdsMeta = $qg->whereNotNull('giu_phong.meta')->pluck('giu_phong.meta');
                foreach ($holdsMeta as $metaRaw) {
                    if (!$metaRaw) continue;
                    $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                    if (!is_array($decoded)) continue;
                    if (isset($decoded['spec_signature_hash']) && $decoded['spec_signature_hash'] === $requiredSignature) {
                        $aggregateHoldsForSignature += (isset($decoded['rooms_count']) ? (int)$decoded['rooms_count'] : 1);
                    }
                }
            }
        }

        $totalRoomsOfType = 0;
        if (Schema::hasTable('loai_phong') && Schema::hasColumn('loai_phong', 'so_luong_thuc_te')) {
            $totalRoomsOfType = (int) DB::table('loai_phong')->where('id', $loaiPhongId)->value('so_luong_thuc_te');
            $unavailableCount = Phong::where('loai_phong_id', $loaiPhongId)
                ->whereIn('trang_thai', ['bao_tri', 'khong_su_dung'])
                ->count();
            $totalRoomsOfType = max(0, $totalRoomsOfType - $unavailableCount);
        } else {
            $totalRoomsOfType = Phong::where('loai_phong_id', $loaiPhongId)
                ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
                ->count();
        }

        $remainingAcrossType = max(0, $totalRoomsOfType - $aggregateBooked - $aggregateHoldsForSignature);
        $availableForSignature = max(0, min($matchingAvailableCount, $remainingAcrossType));

        return (int) $availableForSignature;
    }

    private function computeAvailableRoomIds(int $loaiPhongId, Carbon $fromDate, Carbon $toDate, int $limit = 1, ?string $requiredSignature = null): array
    {
        $requestedStart = $fromDate->copy()->setTime(14, 0, 0);
        $requestedEnd = $toDate->copy()->setTime(12, 0, 0);
        $reqStartStr = $requestedStart->toDateTimeString();
        $reqEndStr = $requestedEnd->toDateTimeString();

        // 1) specific booked room ids (dat_phong_item with phong_id) overlapping
        $bookedRoomIds = [];
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'phong_id')) {
            $bookedRoomIds = DB::table('dat_phong_item')
                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                ->where('dat_phong_item.loai_phong_id', $loaiPhongId)
                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('dat_phong_item.phong_id')->filter()->unique()->toArray();
        }

        // 2) specific holds targeting rooms (giu_phong.phong_id) where dat_phong overlaps
        $heldRoomIds = [];
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'phong_id')) {
            $heldRoomIds = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.phong_id')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.phong_id')
                ->filter()
                ->unique()
                ->toArray();
        }

        // 3) meta-based selected_phong_ids for holds whose dat_phong overlaps
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'meta')) {
            $holdsWithMeta = DB::table('giu_phong')
                ->join('dat_phong', 'giu_phong.dat_phong_id', '=', 'dat_phong.id')
                ->where('giu_phong.released', false)
                ->where('giu_phong.loai_phong_id', $loaiPhongId)
                ->where('giu_phong.het_han_luc', '>', now())
                ->whereNotNull('giu_phong.meta')
                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$reqEndStr, $reqStartStr])
                ->pluck('giu_phong.meta');

            foreach ($holdsWithMeta as $metaRaw) {
                if (!$metaRaw) continue;
                $decoded = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;
                if (!is_array($decoded)) continue;
                if (!empty($decoded['selected_phong_ids'])) {
                    foreach ($decoded['selected_phong_ids'] as $pid) {
                        $heldRoomIds[] = (int)$pid;
                    }
                }
            }
        }

        $excluded = array_unique(array_merge($bookedRoomIds, $heldRoomIds));

        $query = Phong::where('loai_phong_id', $loaiPhongId)
            ->where('spec_signature_hash', $requiredSignature)
            ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
            ->when(!empty($excluded), function ($q) use ($excluded) {
                $q->whereNotIn('id', $excluded);
            })
            ->lockForUpdate()
            ->limit((int)$limit);

        $rows = $query->get(['id']);

        return $rows->pluck('id')->toArray();
    }


    public function store(Request $request)
    {
        Log::debug('Booking.store called', [
            'url' => url()->current(),
            'session_id' => session()->getId(),
            'cookies' => request()->cookies->all(),
            'input_keys' => array_keys($request->all()),
            'raw_input' => $request->all()
        ]);

        $user = $request->user();
        if (!$user) {
            Log::debug('Booking.store: no authenticated user');
            return redirect()->route('login')->with('error', 'You must be logged in to make a booking.');
        }

        $validated = $request->validate([
            'phong_id' => 'required|exists:phong,id',
            'ngay_nhan_phong' => 'required|date',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'nullable|integer|min:0|max:12',
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:tien_nghi,id',
            'ghi_chu' => 'nullable|string|max:1000',
            'phuong_thuc' => 'nullable|string|max:100',
            'rooms_count' => 'nullable|integer|min:1',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'deposit_amount' => 'required|numeric|min:1',
            'tong_tien' => 'required|numeric|gte:deposit_amount',

        ]);
        $expectedDeposit = $validated['tong_tien'] * 0.5;
        if (abs($validated['deposit_amount'] - $expectedDeposit) > 1000) {
            return back()->withErrors(['deposit_amount' => 'Deposit không hợp lệ (phải khoảng 20% tổng)']);
        }

        Log::debug('Booking: validation passed');

        $phong = Phong::with(['loaiPhong', 'tienNghis', 'bedTypes', 'activeOverrides'])->findOrFail($request->input('phong_id'));
        Log::debug('Booking: loaded phong', ['phong_id' => $phong->id]);


        $from = Carbon::parse($request->input('ngay_nhan_phong'))->startOfDay();
        $to = Carbon::parse($request->input('ngay_tra_phong'))->startOfDay();
        $nights = $from->diffInDays($to);
        if ($nights <= 0) {
            return back()->withInput()->withErrors(['ngay_tra_phong' => 'Check-out date must be after check-in date.']);
        }

        $adultsInput = (int)$request->input('adults', 1);
        $childrenInput = (int)$request->input('children', 0);
        $childrenAges = $request->input('children_ages', []);

        if ($childrenInput > 0) {
            $provided = is_array($childrenAges) ? count($childrenAges) : 0;
            if ($provided !== $childrenInput) {
                return back()->withInput()->withErrors(['children_ages' => 'Please provide ages for each child.']);
            }
        }

        $computedAdults = $adultsInput;
        $chargeableChildren = 0;
        foreach ($childrenAges as $age) {
            $age = (int)$age;
            if ($age >= 13) $computedAdults++;
            elseif ($age >= 7) $chargeableChildren++;
        }

        $roomCapacity = 0;
        if ($phong->bedTypes && $phong->bedTypes->count()) {
            foreach ($phong->bedTypes as $bt) {
                $qty = (int) ($bt->pivot->quantity ?? 0);
                $cap = (int) ($bt->capacity ?? 1);
                $roomCapacity += $qty * $cap;
            }
        }
        if ($roomCapacity <= 0) $roomCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));

        $roomsCount = max(1, (int)$request->input('rooms_count', 1));

        $selectedAddonIds = $request->input('addons', []);
        $selectedAddons = collect();
        if (is_array($selectedAddonIds) && count($selectedAddonIds) > 0) {
            $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
        }
        $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
        $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;

        $childrenMaxAllowed = 2 * $roomsCount;
        if ($childrenInput > $childrenMaxAllowed) {
            return back()->withInput()->withErrors(['children' => "Maximum {$childrenMaxAllowed} children allowed for {$roomsCount} room(s)."]);
        }

        $totalRoomCapacity = $roomCapacity * $roomsCount;
        $countedPersons = $computedAdults + $chargeableChildren;
        $totalMaxAllowed = $totalRoomCapacity + (2 * $roomsCount);
        if ($countedPersons > $totalMaxAllowed) {
            return back()->withInput()->withErrors(['error' => "Maximum allowed guests for {$roomsCount} room(s) is {$totalMaxAllowed}. You provided {$countedPersons}."]);
        }

        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);
        $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
        $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
        $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
        $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
        $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);

        $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
        $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;

        $finalPerNightServer = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
        $snapshotTotalServer = $finalPerNightServer * $nights;

        $maThamChieu = 'BK' . Str::upper(Str::random(8));

        $payload = [
            'ma_tham_chieu' => $maThamChieu,
            'nguoi_dung_id' => $user->id,
            'created_by' => $user->id,
            'ngay_nhan_phong' => $from->toDateString(),
            'ngay_tra_phong' => $to->toDateString(),
            'so_khach' => ($adultsInput + $childrenInput),
            'trang_thai' => 'dang_cho',
            'tong_tien' => $snapshotTotalServer,
            'snapshot_total' => $snapshotTotalServer,
            'ghi_chu' => $request->input('ghi_chu', null),
            'phuong_thuc' => $request->input('phuong_thuc'),
            'created_at' => now(),
            'updated_at' => now(),
            'contact_name'    => $request->input('name'),
            'contact_address' => $request->input('address'),
            'contact_phone'   => $request->input('phone', $user->so_dien_thoai ?? null),
            'snapshot_meta' => json_encode([
                'rooms_count' => $roomsCount,
                'adults_input' => $adultsInput,
                'children_input' => $childrenInput,
                'children_ages' => $childrenAges,
                'computed_adults' => $computedAdults,
                'chargeable_children' => $chargeableChildren,
                'room_capacity_single' => $roomCapacity,
                'total_room_capacity' => $totalRoomCapacity,
                'counted_persons' => $countedPersons,
                'extra_count_total' => $extraCountTotal,
                'adult_extra_total' => $adultExtraTotal,
                'children_extra_total' => $childrenExtraTotal,
                'room_base_per_night' => $basePerNight,
                'adults_charge_per_night' => $adultsChargePerNight,
                'children_charge_per_night' => $childrenChargePerNight,
                'addons_per_night' => $addonsPerNight,
                'addons' => $selectedAddons->map(function ($a) {
                    return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                })->toArray(),
                'final_per_night' => $finalPerNightServer,
                'nights' => $nights,
            ]),
        ];

        try {
            $datPhongId = null;
            DB::transaction(function () use ($phong, $from, $to, $roomsCount, &$datPhongId, $payload, $selectedAddons, $finalPerNightServer, $snapshotTotalServer, $nights, $request) {

                if (Schema::hasTable('loai_phong')) {
                    DB::table('loai_phong')->where('id', $phong->loai_phong_id)->lockForUpdate()->first();
                }

                $requiredSignature = $phong->specSignatureHash();
                $availableNow = $this->computeAvailableRoomsCount($phong->loai_phong_id, $from, $to, $requiredSignature);

                if ($roomsCount > $availableNow) {
                    throw new \Exception("Only {$availableNow} room(s) available.");
                }

                $allowedPayload = [];
                foreach ($payload as $k => $v) {
                    if (Schema::hasColumn('dat_phong', $k)) $allowedPayload[$k] = $v;
                }
                $allowedPayload['deposit_amount'] = $request->deposit_amount;
                $allowedPayload['trang_thai'] = 'deposited';
                $allowedPayload['tong_tien'] = $snapshotTotalServer;

                $datPhongId = DB::table('dat_phong')->insertGetId($allowedPayload);

                // Dispatch booking created event
                $booking = DatPhong::find($datPhongId);
                if ($booking) {
                    Log::info("Dispatching BookingCreated event", [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->ma_dat_phong
                    ]);
                    event(new BookingCreated($booking));
                }

                if (Schema::hasTable('giu_phong')) {
                    $holdBase = [
                        'dat_phong_id' => $datPhongId,
                        'loai_phong_id' => $phong->loai_phong_id,
                        'so_luong' => $roomsCount,
                        'het_han_luc' => now()->addMinutes(15),
                        'released' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $baseSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();

                    $baseTienNghi = method_exists($phong, 'effectiveTienNghiIds') ? $phong->effectiveTienNghiIds() : [];
                    $selectedAddonIdsArr = $selectedAddons->pluck('id')->map('intval')->toArray();
                    $mergedTienNghi = array_values(array_unique(array_merge($baseTienNghi, $selectedAddonIdsArr)));
                    sort($mergedTienNghi, SORT_NUMERIC);
                    $bedSpec = method_exists($phong, 'effectiveBedSpec') ? $phong->effectiveBedSpec() : [];

                    $specArray = [
                        'loai_phong_id' => (int)$phong->loai_phong_id,
                        'tien_nghi' => $mergedTienNghi,
                        'beds' => $bedSpec,
                    ];

                    ksort($specArray);
                    $requestedSpecSignature = md5(json_encode($specArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    Log::debug('Booking: signatures', [
                        'phong_id' => $phong->id,
                        'phong_db_signature' => $phong->spec_signature_hash ?? null,
                        'requestedSpecSignature' => $requestedSpecSignature,
                        'specArray' => $specArray,
                    ]);

                    $meta = [
                        'final_per_night' => (float)$finalPerNightServer,
                        'snapshot_total' => (float)$snapshotTotalServer,
                        'nights' => $nights,
                        'rooms_count' => $roomsCount,
                        'addons' => $selectedAddons->map(function ($a) {
                            return ['id' => $a->id, 'ten' => $a->ten, 'gia' => $a->gia];
                        })->toArray(),
                        'spec_signature_hash' => $requestedSpecSignature,
                        'requested_spec_signature' => $requestedSpecSignature,
                        'base_spec_signature' => $baseSignature,
                    ];

                    $requestedPhongId = $phong->id ?? null;
                    $requestedReserved = 0;

                    if ($requestedPhongId && Schema::hasColumn('giu_phong', 'phong_id')) {
                        $dbRoomSignature = $phong->spec_signature_hash ?? $phong->specSignatureHash();

                        $isBooked = false;
                        if (Schema::hasTable('dat_phong_item')) {
                            $fromStartStr = $from->copy()->setTime(14, 0)->toDateTimeString();
                            $toEndStr = $to->copy()->setTime(12, 0)->toDateTimeString();
                            $isBooked = DB::table('dat_phong_item')
                                ->join('dat_phong', 'dat_phong_item.dat_phong_id', '=', 'dat_phong.id')
                                ->where('dat_phong_item.phong_id', $requestedPhongId)
                                ->whereNotIn('dat_phong.trang_thai', ['da_huy', 'huy'])
                                ->whereRaw("CONCAT(dat_phong.ngay_nhan_phong,' 14:00:00') < ? AND CONCAT(dat_phong.ngay_tra_phong,' 12:00:00') > ?", [$toEndStr, $fromStartStr])
                                ->exists();
                        }

                        $isHeld = false;
                        if (!$isBooked && Schema::hasTable('giu_phong')) {
                            $isHeld = DB::table('giu_phong')
                                ->where('phong_id', $requestedPhongId)
                                ->where('released', false)
                                ->where('het_han_luc', '>', now())
                                ->exists();
                        }

                        if (!$isBooked && !$isHeld) {
                            $locked = Phong::where('id', $requestedPhongId)
                                ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
                                ->lockForUpdate()
                                ->first();

                            if ($locked) {
                                $row = $holdBase;
                                $row['so_luong'] = 1;
                                $row['phong_id'] = $requestedPhongId;

                                if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                    $row['spec_signature_hash'] = $dbRoomSignature;
                                }

                                $row['meta'] = json_encode(array_merge($meta, ['selected_phong_id' => $requestedPhongId, 'selected_phong_ids' => [$requestedPhongId]]), JSON_UNESCAPED_UNICODE);
                                DB::table('giu_phong')->insert($row);

                                $requestedReserved = 1;
                                Log::debug('Booking: giu_phong inserted per-phong (requested room reserved)', ['phong_id' => $requestedPhongId, 'dat_phong_id' => $datPhongId]);
                            } else {
                                Log::debug('Booking: requested room could not be locked', ['phong_id' => $requestedPhongId]);
                            }
                        } else {
                            Log::debug('Booking: requested room not available to reserve', ['phong_id' => $requestedPhongId, 'isBooked' => $isBooked, 'isHeld' => $isHeld]);
                        }
                    }

                    if (Schema::hasColumn('giu_phong', 'phong_id')) {
                        $stillNeeded = max(0, $roomsCount - $requestedReserved);

                        $selectedIds = [];
                        if ($stillNeeded > 0) {
                            $selectedIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $stillNeeded, $requestedSpecSignature);

                            if (empty($selectedIds) || count($selectedIds) < $stillNeeded) {
                                $need = $stillNeeded - count($selectedIds);
                                $fallbackIds = $this->computeAvailableRoomIds($phong->loai_phong_id, $from, $to, $need, null);
                                $selectedIds = array_values(array_unique(array_merge($selectedIds, $fallbackIds)));
                            }

                            if ($requestedReserved && !empty($selectedIds)) {
                                $selectedIds = array_values(array_diff($selectedIds, [$requestedPhongId]));
                            }
                        }

                        if (!empty($selectedIds)) {
                            $locked = Phong::whereIn('id', $selectedIds)
                                ->whereNotIn('trang_thai', ['bao_tri', 'khong_su_dung'])
                                ->lockForUpdate()
                                ->get(['id'])
                                ->pluck('id')
                                ->toArray();

                            $selectedIds = array_values(array_intersect($selectedIds, $locked));
                        }

                        $reservedCount = $requestedReserved;
                        if (!empty($selectedIds)) {
                            foreach ($selectedIds as $pid) {
                                if ($reservedCount >= $roomsCount) break;
                                $row = $holdBase;
                                $row['so_luong'] = 1;
                                $row['phong_id'] = $pid;
                                if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                    $row['spec_signature_hash'] = $baseSignature;
                                }
                                $row['meta'] = json_encode(array_merge($meta, ['selected_phong_id' => $pid, 'selected_phong_ids' => $selectedIds]), JSON_UNESCAPED_UNICODE);
                                DB::table('giu_phong')->insert($row);
                                $reservedCount++;
                                Log::debug('Booking: giu_phong inserted per-phong', ['phong_id' => $pid, 'dat_phong_id' => $datPhongId]);
                            }
                        }

                        if ($roomsCount - $reservedCount > 0) {
                            $aggRow = $holdBase;
                            $aggRow['so_luong'] = $roomsCount - $reservedCount;
                            if (Schema::hasColumn('giu_phong', 'spec_signature_hash')) {
                                $aggRow['spec_signature_hash'] = $baseSignature;
                            }
                            $aggRow['meta'] = json_encode(array_merge($meta, ['reserved_count' => $reservedCount]), JSON_UNESCAPED_UNICODE);
                            DB::table('giu_phong')->insert($aggRow);
                            Log::debug('Booking: giu_phong inserted aggregate for remaining', ['remaining' => $roomsCount - $reservedCount, 'dat_phong_id' => $datPhongId]);
                        }
                    } else {
                        $aggRow = $holdBase;
                        $aggRow['spec_signature_hash'] = $requestedSpecSignature;
                        $aggRow['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
                        DB::table('giu_phong')->insert($aggRow);
                        Log::debug('Booking: giu_phong inserted (no phong_id column)', ['so_luong' => $roomsCount, 'dat_phong_id' => $datPhongId]);
                    }
                }
            });

            return redirect()->route('account.booking.create', $phong->id)
                ->with('success', 'Room(s) held for 15 minutes. Please proceed to payment to confirm the booking.')
                ->with('dat_phong_id', $datPhongId);
        } catch (\Throwable $e) {
            Log::error('Booking.store exception: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->withErrors(['error' => 'Could not create booking: ' . $e->getMessage()]);
        }
    }
    
    public function validateVoucher(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50',
            'phong_id' => 'required|integer|exists:phong,id',
            'ngay_nhan_phong' => 'required|date',
            'ngay_tra_phong' => 'required|date|after:ngay_nhan_phong',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'children_ages' => 'nullable|array',
            'children_ages.*' => 'integer|min:0|max:12',
            'addons' => 'nullable|array',
            'rooms_count' => 'required|integer|min:1',
        ]);

        $code = strtoupper(trim($request->code));
        $voucher = Voucher::where('code', $code)
            ->where('active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('qty', '>', 0)
            ->first();

        if (!$voucher) {
            return response()->json(['error' => 'Mã voucher không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // Kiểm tra usage limit per user
        $userId = Auth::id();
        $usageCount = VoucherUsage::where('voucher_id', $voucher->id)
            ->where('user_id', $userId)
            ->count();
        if ($voucher->usage_limit_per_user && $usageCount >= $voucher->usage_limit_per_user) {
            return response()->json(['error' => 'Bạn đã sử dụng hết lượt cho voucher này.'], 400);
        }

        // Kiểm tra min_order_amount
        $phong = Phong::findOrFail($request->phong_id);
        $nights = $this->calculateNights($request->ngay_nhan_phong, $request->ngay_tra_phong);
        $basePerNight = (float) ($phong->tong_gia ?? $phong->gia_mac_dinh ?? 0);
        $roomsCount = $request->rooms_count;
        $adultsInput = $request->adults;
        $childrenInput = $request->children ?? 0;
        $childrenAges = $request->children_ages ?? [];
        $computedAdults = $adultsInput;
        $chargeableChildren = 0;
        foreach ($childrenAges as $age) {
            $age = (int)$age;
            if ($age >= 13) $computedAdults++;
            elseif ($age >= 7) $chargeableChildren++;
        }
        $roomCapacity = 0;
        if ($phong->bedTypes && $phong->bedTypes->count()) {
            foreach ($phong->bedTypes as $bt) {
                $qty = (int) ($bt->pivot->quantity ?? 0);
                $cap = (int) ($bt->capacity ?? 1);
                $roomCapacity += $qty * $cap;
            }
        }
        if ($roomCapacity <= 0) $roomCapacity = (int) ($phong->suc_chua ?? ($phong->loaiPhong->suc_chua ?? 1));
        $totalRoomCapacity = $roomCapacity * $roomsCount;
        $countedPersons = $computedAdults + $chargeableChildren;
        $extraCountTotal = max(0, $countedPersons - $totalRoomCapacity);
        $adultBeyondBaseTotal = max(0, $computedAdults - $totalRoomCapacity);
        $adultExtraTotal = min($adultBeyondBaseTotal, $extraCountTotal);
        $childrenExtraTotal = max(0, $extraCountTotal - $adultExtraTotal);
        $childrenExtraTotal = min($childrenExtraTotal, $chargeableChildren);
        $adultsChargePerNight = $adultExtraTotal * self::ADULT_PRICE;
        $childrenChargePerNight = $childrenExtraTotal * self::CHILD_PRICE;
        $selectedAddonIds = $request->addons ?? [];
        $selectedAddons = \App\Models\TienNghi::whereIn('id', $selectedAddonIds)->get();
        $addonsPerNightPerRoom = (float) ($selectedAddons->sum('gia') ?? 0.0);
        $addonsPerNight = $addonsPerNightPerRoom * $roomsCount;
        $finalPerNight = ($basePerNight * $roomsCount) + $adultsChargePerNight + $childrenChargePerNight + $addonsPerNight;
        $totalBeforeDiscount = $finalPerNight * $nights;

        if ($voucher->min_order_amount && $totalBeforeDiscount < $voucher->min_order_amount) {
            return response()->json(['error' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng voucher.'], 400);
        }

        // Tính discount_amount
        $discountAmount = $voucher->type === 'phan_tram'
            ? ($totalBeforeDiscount * $voucher->value / 100)
            : $voucher->value;
        $discountAmount = min($discountAmount, $totalBeforeDiscount); // Không giảm quá total

        return response()->json([
            'success' => true,
            'discount_amount' => $discountAmount,
            'voucher_id' => $voucher->id,
            'message' => 'Voucher áp dụng thành công! Giảm ' . number_format($discountAmount) . ' VND.',
        ]);
    }

    private function calculateNights($from, $to)
    {
        return Carbon::parse($from)->diffInDays(Carbon::parse($to));
    }
    public function applyVoucher(Request $request)
    {
        try {
            $code = strtoupper(trim($request->input('code')));
            $totalRaw = (string) $request->input('total', '0');
            $total = (int) preg_replace('/\D/', '', $totalRaw);

            if ($total <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giá trị đơn hàng không hợp lệ.',
                ]);
            }

            $voucher = Voucher::where('code', $code)->first();
            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá không tồn tại.',
                ]);
            }

            $today = Carbon::today()->toDateString();
            $start = $voucher->start_date ? Carbon::parse($voucher->start_date)->toDateString() : null;
            $end   = $voucher->end_date ? Carbon::parse($voucher->end_date)->toDateString() : null;

            if (
                !$voucher->active ||
                ($start && $start > $today) ||
                ($end && $end < $today) ||
                ($voucher->qty !== null && $voucher->qty <= 0)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã giảm giá đã hết hạn, chưa có hiệu lực hoặc đã hết lượt.',
                ]);
            }

            // ===== Giới hạn lượt dùng / user (nếu cấu trúc bảng cho phép) =====
            $userId = Auth::id();
            if (!empty($voucher->usage_limit_per_user) && $userId) {
                if (class_exists(VoucherUsage::class)) {
                    $usageModel = new VoucherUsage();
                    $table = $usageModel->getTable();

                    if (Schema::hasTable($table)) {
                        // Tự tìm cột user: ưu tiên user_id, fallback nguoi_dung_id
                        $userCol = null;
                        if (Schema::hasColumn($table, 'user_id')) {
                            $userCol = 'user_id';
                        } elseif (Schema::hasColumn($table, 'nguoi_dung_id')) {
                            $userCol = 'nguoi_dung_id';
                        }

                        if ($userCol) {
                            $usageCount = VoucherUsage::where('voucher_id', $voucher->id)
                                ->where($userCol, $userId)
                                ->count();

                            if ($usageCount >= $voucher->usage_limit_per_user) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Bạn đã sử dụng hết lượt cho mã giảm giá này.',
                                ]);
                            }
                        }
                        // Nếu không có cột user -> bỏ qua check per-user, không được thì sau này bổ sung schema.
                    }
                }
            }

            // ===== Đơn tối thiểu =====
            if (!empty($voucher->min_order_amount) && $total < $voucher->min_order_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã này.',
                ]);
            }

            // ===== Tính giảm giá: percent / pixed =====
            $type = strtolower(trim($voucher->type));
            $value = (float) $voucher->value;
            $discount = 0;

            if ($type === 'percent') {
                if ($value <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Giá trị phần trăm giảm giá không hợp lệ.',
                    ]);
                }
                $discount = (int) round($total * ($value / 100));
            } elseif ($type === 'fixed') {
                if ($value <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Giá trị giảm giá không hợp lệ.',
                    ]);
                }
                $discount = (int) $value;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Loại mã giảm giá không hợp lệ (chỉ hỗ trợ percent hoặc fixed).',
                ]);
            }

            if ($discount > $total) {
                $discount = $total;
            }

            $finalTotal = $total - $discount;
            $deposit = (int) round($finalTotal * 0.5);

            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công.',
                'voucher_name' => $voucher->name,
                'type' => $type,
                'value' => $value,
                'discount' => $discount,
                'final_total' => $finalTotal,
                'deposit' => $deposit,
                'discount_display' => number_format($discount, 0, ',', '.'),
                'final_total_display' => number_format($finalTotal, 0, ',', '.'),
                'deposit_display' => number_format($deposit, 0, ',', '.'),
            ]);
        } catch (\Throwable $e) {
            Log::error('applyVoucher error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi nội bộ khi áp dụng mã giảm giá.',
            ], 500);
        }
    }

    /**
     * Cancel a booking (client-side) with advanced refund policy
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để hủy đặt phòng.');
        }

        // Find the booking and verify ownership
        $booking = DatPhong::where('id', $id)
            ->where('nguoi_dung_id', $user->id)
            ->first();

        if (!$booking) {
            return back()->with('error', 'Không tìm thấy đặt phòng hoặc bạn không có quyền hủy đặt phòng này.');
        }

        // Check if the booking status allows cancellation
        if (!in_array($booking->trang_thai, ['dang_cho', 'da_xac_nhan'])) {
            return back()->with('error', 'Không thể hủy đặt phòng với trạng thái hiện tại: ' . $booking->trang_thai);
        }

        try {
            DB::beginTransaction();

            // Calculate refund based on advanced policy (Option B)
            // Use actual check-in time (14:00) for accurate calculation
            $checkInDateTime = Carbon::parse($booking->ngay_nhan_phong)->setTime(14, 0, 0);
            $now = Carbon::now();
            $daysUntilCheckIn = $now->diffInDays($checkInDateTime, false); // Calculates full days until 14:00 check-in time

            // Determine deposit type from snapshot_meta
            $meta = $booking->snapshot_meta ?? [];
            $depositType = $meta['deposit_percentage'] ?? 50;
            
            // Calculate refund percentage using Option B logic
            $refundPercentage = $this->calculateRefundPercentage($daysUntilCheckIn, $depositType);
            
            // Calculate refund amount
            $paidAmount = $booking->deposit_amount ?? 0;
            $refundAmount = $paidAmount * ($refundPercentage / 100);

            // Update booking status to cancelled with refund info
            $booking->update([
                'trang_thai' => 'da_huy',
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason', 'Khách hàng hủy đặt phòng')
            ]);

            // Delete/release giu_phong records associated with this booking
            if (Schema::hasTable('giu_phong')) {
                DB::table('giu_phong')
                    ->where('dat_phong_id', $booking->id)
                    ->delete();
            }

            // NOTE: Do NOT change existing successful transactions to 'that_bai'
            // Keep audit trail of actual money received
            // If refund needed, create NEW refund transaction instead
            
            // Create refund transaction if refund amount > 0
            if ($refundAmount > 0) {
                \App\Models\GiaoDich::create([
                    'dat_phong_id' => $booking->id,
                    'so_tien' => $refundAmount,
                    'trang_thai' => 'da_hoan',
                    'nha_cung_cap' => 'Hoàn tiền hủy phòng',
                    'ghi_chu' => "Hoàn {$refundPercentage}% tiền cọc do hủy booking",
                ]);
            }

            Log::info('Booking cancelled with refund transaction (client)', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'refund_amount' => $refundAmount,
                'refund_percentage' => $refundPercentage,
            ]);

            // Delete dat_phong_items (booking items)
            $deletedItems = \App\Models\DatPhongItem::where('dat_phong_id', $booking->id)->delete();
            
            Log::info('Deleted dat_phong_items (client cancel)', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'deleted_count' => $deletedItems,
            ]);

            // Create refund request if refund amount > 0
            if ($refundAmount > 0) {
                \App\Models\RefundRequest::create([
                    'dat_phong_id' => $booking->id,
                    'amount' => $refundAmount,
                    'percentage' => $refundPercentage,
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);

                Log::info('Refund request created', [
                    'booking_id' => $booking->id,
                    'amount' => $refundAmount,
                    'percentage' => $refundPercentage,
                ]);
            }

            DB::commit();

            Log::info('Booking cancelled by client with refund', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'ma_tham_chieu' => $booking->ma_tham_chieu,
                'days_until_checkin' => $daysUntilCheckIn,
                'deposit_type' => $depositType,
                'refund_percentage' => $refundPercentage,
                'refund_amount' => $refundAmount,
            ]);

            // Build success message
            $message = 'Đã hủy đặt phòng thành công. ';
            if ($refundAmount > 0) {
                $message .= sprintf(
                    'Số tiền hoàn: %s ₫ (%d%% của %s ₫). Yêu cầu hoàn tiền đang được xử lý.',
                    number_format($refundAmount, 0, ',', '.'),
                    $refundPercentage,
                    number_format($paidAmount, 0, ',', '.')
                );
            } else {
                $message .= 'Không được hoàn tiền do hủy muộn (< 24 giờ trước check-in).';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Client booking cancellation error', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Có lỗi xảy ra khi hủy đặt phòng. Vui lòng thử lại sau.');
        }
    }

    /**
     * Calculate refund percentage based on Option B policy:
     * Different refund rates for 50% deposit vs 100% payment
     */
    private function calculateRefundPercentage(int $daysUntilCheckIn, int $depositType): int
    {
        if ($depositType == 100) {
            // Thanh toán 100% - được ưu đãi khi hủy
            if ($daysUntilCheckIn >= 7) {
                return 90;  // Hoàn 90%
            } elseif ($daysUntilCheckIn >= 3) {
                return 60;  // Hoàn 60%
            } elseif ($daysUntilCheckIn >= 1) {
                return 40;  // Hoàn 40%
            } else {
                return 20;  // Hoàn 20%
            }
        } else {
            // Đặt cọc 50% - policy thông thường
            if ($daysUntilCheckIn >= 7) {
                return 100; // Hoàn 100% tiền cọc
            } elseif ($daysUntilCheckIn >= 3) {
                return 70;  // Hoàn 70%
            } elseif ($daysUntilCheckIn >= 1) {
                return 30;  // Hoàn 30%
            } else {
                return 0;   // Không hoàn
            }
        }
    }

    /**
     * Retry payment for a booking with pending transaction
     */
    public function retryPayment(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $booking = DatPhong::where('id', $id)
            ->where('nguoi_dung_id', $user->id)
            ->with('giaoDichs')
            ->firstOrFail();

        // Check booking status
        if ($booking->trang_thai !== 'dang_cho') {
            return back()->with('error', 'Chỉ có thể tiếp tục thanh toán cho đơn đang chờ.');
        }

        // Find pending VNPay transaction
        $pendingTransaction = $booking->giaoDichs()
            ->where('trang_thai', 'dang_cho')
            ->where('nha_cung_cap', 'vnpay')
            ->first();

        if (!$pendingTransaction) {
            return back()->with('error', 'Không tìm thấy giao dịch đang chờ.');
        }

        try {
            // Generate new VNPay URL using existing transaction
            $vnp_Url = env('VNPAY_URL');
            $vnp_TmnCode = env('VNPAY_TMN_CODE');
            $vnp_HashSecret = env('VNPAY_HASH_SECRET');
            $vnp_ReturnUrl = env('VNPAY_RETURN_URL');

            // Use existing transaction ID with new timestamp
            $merchantTxnRef = $pendingTransaction->id . '-' . time();

            $inputData = [
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $pendingTransaction->so_tien * 100,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => "Thanh toán đặt phòng {$booking->ma_tham_chieu}",
                "vnp_OrderType" => "billpayment",
                "vnp_ReturnUrl" => $vnp_ReturnUrl,
                "vnp_TxnRef" => $merchantTxnRef,
            ];

            ksort($inputData);
            $query = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
            $vnp_SecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $redirectUrl = $vnp_Url . '?' . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

            Log::info('Retry payment for booking', [
                'booking_id' => $booking->id,
                'transaction_id' => $pendingTransaction->id,
                'user_id' => $user->id,
            ]);

            return redirect()->away($redirectUrl);

        } catch (\Exception $e) {
            Log::error('Error retrying payment', [
                'booking_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Có lỗi xảy ra khi tạo link thanh toán. Vui lòng thử lại sau.');
        }
    }
}
