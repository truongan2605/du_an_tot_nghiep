<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Tính lại total_spent và cập nhật hạng nếu cần
        $user->refreshLoyaltyStatus();
        $user->refresh();
        
        $currentLevel = $user->getMemberLevelName();
        $currentDiscount = $user->getMemberDiscountPercent();
        $totalSpent = (float) ($user->total_spent ?? 0);
        $nextLevelInfo = $user->getNextLevelInfo();
        
        // Tính phần trăm tiến độ
        $progressPercent = 0;
        if ($nextLevelInfo['required'] && $nextLevelInfo['required'] > 0) {
            $progressPercent = min(100, ($totalSpent / $nextLevelInfo['required']) * 100);
        }
        
        return view('account.rewards', compact(
            'currentLevel',
            'currentDiscount',
            'totalSpent',
            'nextLevelInfo',
            'progressPercent'
        ));
    }
}
