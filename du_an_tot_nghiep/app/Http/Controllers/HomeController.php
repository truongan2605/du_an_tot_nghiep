<?php

namespace App\Http\Controllers;

use App\Models\Phong;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Lấy danh sách phòng kèm loại phòng và tầng
        $phongs = Phong::with(['loaiPhong', 'tang'])->get();

        return view('home', compact('phongs'));
    }
}