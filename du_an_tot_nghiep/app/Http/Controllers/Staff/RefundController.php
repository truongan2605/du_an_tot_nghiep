<?php

namespace App\Http\Controllers\Staff;

use App\Models\RefundRequest;
use App\Models\DatPhong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class RefundController extends Controller
{
    /**
     * Display a listing of refund requests
     */
    public function index(Request $request)
    {
        $query = RefundRequest::with(['datPhong.nguoiDung', 'processedBy'])
            ->orderByDesc('created_at');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by refund type (full_booking or single_room)
        if ($request->filled('refund_type')) {
            $query->where('refund_type', $request->input('refund_type'));
        }

        // Search by booking reference
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->whereHas('datPhong', function($q) use ($search) {
                $q->where('ma_tham_chieu', 'like', "%{$search}%");
            });
        }

        $refunds = $query->paginate(20)->withQueryString();

        $stats = [
            'pending' => RefundRequest::where('status', 'pending')->count(),
            'approved' => RefundRequest::where('status', 'approved')->count(),
            'completed' => RefundRequest::where('status', 'completed')->count(),
            'rejected' => RefundRequest::where('status', 'rejected')->count(),
        ];

        return view('staff.refunds.index', compact('refunds', 'stats'));
    }

    /**
     * Approve a refund request
     */
    public function approve(Request $request, $id)
    {
        $refund = RefundRequest::with('datPhong')->findOrFail($id);

        if ($refund->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể duyệt yêu cầu đang chờ xử lý.');
        }

        try {
            DB::beginTransaction();

            // Giữ lại admin_note gốc (chứa thông tin phòng đã hủy) và thêm ghi chú mới
            $existingNote = $refund->admin_note ?? '';
            $newNote = $request->input('note') ? "\n[Ghi chú duyệt]: " . $request->input('note') : '';
            
            $refund->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => Auth::id(),
                'admin_note' => $existingNote . $newNote,
            ]);

            DB::commit();

            Log::info('Refund request approved', [
                'refund_id' => $refund->id,
                'booking_id' => $refund->dat_phong_id,
                'amount' => $refund->amount,
                'approved_by' => Auth::id(),
            ]);

            return back()->with('success', sprintf(
                'Đã duyệt yêu cầu hoàn tiền %s ₫. Vui lòng xử lý chuyển tiền cho khách.',
                number_format($refund->amount, 0, ',', '.')
            ));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving refund', [
                'refund_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Có lỗi xảy ra khi duyệt yêu cầu.');
        }
    }

    /**
     * Reject a refund request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $refund = RefundRequest::with('datPhong')->findOrFail($id);

        if ($refund->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể từ chối yêu cầu đang chờ xử lý.');
        }

        try {
            DB::beginTransaction();

            $refund->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'processed_by' => Auth::id(),
                'admin_note' => $request->input('reason'),
            ]);

            DB::commit();

            Log::info('Refund request rejected', [
                'refund_id' => $refund->id,
                'booking_id' => $refund->dat_phong_id,
                'rejected_by' => Auth::id(),
                'reason' => $request->input('reason'),
            ]);

            return back()->with('success', 'Đã từ chối yêu cầu hoàn tiền.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting refund', [
                'refund_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Có lỗi xảy ra khi từ chối yêu cầu.');
        }
    }

    /**
     * Mark refund as completed (after manual transfer)
     */
    public function complete(Request $request, $id)
    {
        // Validate proof option
        $request->validate([
            'proof_option' => 'required|in:upload,generate',
            'note' => 'nullable|string|max:500',
        ]);

        // Conditional validation for upload
        if ($request->input('proof_option') === 'upload') {
            $request->validate([
                'proof_image' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            ], [
                'proof_image.required' => 'Vui lòng tải lên ảnh chứng minh hoàn tiền.',
                'proof_image.image' => 'File phải là ảnh.',
                'proof_image.mimes' => 'Ảnh phải có định dạng: JPG, JPEG, PNG.',
                'proof_image.max' => 'Ảnh không được vượt quá 5MB.',
            ]);
        }

        $refund = RefundRequest::findOrFail($id);

        if ($refund->status !== 'approved') {
            return back()->with('error', 'Chỉ có thể hoàn thành yêu cầu đã được duyệt.');
        }

        try {
            $imagePath = null;

            if ($request->input('proof_option') === 'upload') {
                // Handle manual upload
                if ($request->hasFile('proof_image')) {
                    $image = $request->file('proof_image');
                    $filename = 'refund_' . $refund->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('refund_proofs', $filename, 'public');
                }
            } else {
                // Auto-generate receipt
                $generator = new \App\Services\RefundReceiptGenerator();
                $imagePath = $generator->generate($refund);
                
                if (!$imagePath) {
                    return back()->with('error', 'Không thể tạo ảnh chứng minh. Vui lòng thử lại.');
                }
            }

            $refund->update([
                'status' => 'completed',
                'admin_note' => ($refund->admin_note ?? '') . "\n" . $request->input('note', 'Đã chuyển tiền hoàn cho khách.'),
                'proof_image_path' => $imagePath,
            ]);

            Log::info('Refund marked as completed with proof image', [
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
                'completed_by' => Auth::id(),
                'proof_image' => $imagePath,
                'proof_method' => $request->input('proof_option'),
            ]);

            $message = $request->input('proof_option') === 'upload' 
                ? 'Đã đánh dấu hoàn tiền thành công và lưu ảnh chứng minh.'
                : 'Đã đánh dấu hoàn tiền thành công và tạo biên nhận tự động.';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error completing refund', [
                'refund_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
