<?php

namespace App\Http\Controllers;

use App\Models\Phong;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $phongs = Phong::with(['loaiPhong', 'tang', 'images'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $favoriteIds = [];
        if (Auth::check()) {
            $favoriteIds = Wishlist::where('user_id', Auth::id())->pluck('phong_id')->toArray();
        }

        return view('home', compact('phongs', 'favoriteIds'));
    }
}
