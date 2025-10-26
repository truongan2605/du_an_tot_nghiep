<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tang extends Model
{
    use HasFactory;

    protected $table = 'tang';

    protected $fillable = [
        'so_tang',
        'ten',
        'ghi_chu',
    ];

    // Relationships
    public function phongs()
    {
        return $this->hasMany(Phong::class, 'tang_id');
    }
}