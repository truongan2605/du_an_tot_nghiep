<?php

namespace App\Http\Controllers;

use App\Models\LoaiPhong;
use App\Models\Phong;
use App\Models\Wishlist;
use App\Models\BlogPost; // <-- thêm
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Phòng: chỉ lấy một phòng đại diện cho mỗi loại phòng
        $phongs = Phong::with(['loaiPhong', 'tang', 'images'])
            ->orderByDesc('created_at')
            ->get()
            ->unique('loai_phong_id')
            ->take(8);

        $loaiPhongs = LoaiPhong::all();

        $favoriteIds = [];
        if (Auth::check()) {
            $favoriteIds = Wishlist::where('user_id', Auth::id())
                ->pluck('phong_id')
                ->toArray();
        }

        $giaMin = 0;
        $giaMax = Phong::max('gia_cuoi_cung') ?? 1000000;

        // >>> Thêm: bài viết blog cho slider "Best deal" ở trang Home
        $blogPosts = BlogPost::with(['category:id,name,slug','author:id,name'])
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->take(6)
            ->get();

        return view('home', [
            'loaiPhongs'  => $loaiPhongs,
            'phongs'      => $phongs,
            'favoriteIds' => $favoriteIds,
            'giaMin'      => $giaMin,
            'giaMax'      => $giaMax,
            'blogPosts'   => $blogPosts, // <-- truyền sang view
        ]);
    }

    
}
