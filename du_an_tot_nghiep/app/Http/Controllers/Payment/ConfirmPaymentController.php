<?php
namespace App\Http\Controllers\Payment;

    
use App\Models\DatPhong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class ConfirmPaymentController extends Controller
{
    public function confirm(Request $request, $dat_phong_id)
    {
        $dat_phong = DatPhong::findOrFail($dat_phong_id);
        $giao_dich = $dat_phong->giaoDichs()->where('trang_thai', 'thanh_cong')->first();

        if (!$giao_dich || !$dat_phong->can_xac_nhan) {
            return response()->json(['error' => 'Invalid payment or already confirmed'], 400);
        }
        Log::info('Payment confirmed', ['dat_phong_id' => $dat_phong_id]);
        DB::transaction(function () use ($dat_phong) {
            $dat_phong->trang_thai = 'da_xac_nhan';
            $dat_phong->can_xac_nhan = false;
            $dat_phong->save();

           
            $user = $dat_phong->nguoiDung;
            if ($user && $user->vai_tro === 'khach_hang || admin || nhan_vien') {
                Mail::to($user->email)->queue(new \App\Mail\PaymentConfirmed($dat_phong, $user->name));
            }
        });

        return response()->json(['confirmed' => true]);
    }
}