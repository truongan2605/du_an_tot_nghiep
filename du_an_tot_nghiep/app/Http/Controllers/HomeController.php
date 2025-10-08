<?php

namespace App\Http\Controllers;

use App\Models\LoaiPhong;
use App\Models\Phong;

class HomeController extends Controller
{
    public function index()
    {
        $phongs = Phong::with(['loaiPhong','tang','images'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();
        $loaiPhongs = LoaiPhong::all();
        return view('home', compact('loaiPhongs','phongs'));
    }
}
