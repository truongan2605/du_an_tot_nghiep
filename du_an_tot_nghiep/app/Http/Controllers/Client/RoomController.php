<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phong;

class RoomController extends Controller
{
    public function show($id)
    {
        // $phong = Phong::with(['loaiPhong', 'tang', 'images', 'tienNghis'])->findOrFail($id);
        // return view('detail-room', compact('phong'));
        // Lấy phòng theo ID
        $phong = Phong::findOrFail($id);
        
        // Trả về view và truyền dữ liệu phòng
        return view('detail-room', compact('phong'));
    }
}
