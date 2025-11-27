<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PhongTienNghiOverride extends Model
{   
    use Auditable;
    protected $table = 'phong_tien_nghi_override';
    protected $fillable = ['phong_id', 'tien_nghi_id', 'applies_to_dat_phong_id', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
}
