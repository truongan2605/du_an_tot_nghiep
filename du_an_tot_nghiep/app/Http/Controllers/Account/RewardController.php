<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index()
    {
        // Chưa xử lý backend → trả UI tĩnh
        return view('account.rewards');
    }
}
