<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhongVatDungInstance extends Model
{
    use HasFactory;

    protected $table = 'phong_vat_dung_instances';

    protected $fillable = [
        'phong_id',
        'vat_dung_id',
        'serial',
        'status',
        'note',
        'created_by',
    ];

    public function phong()
    {
        return $this->belongsTo(Phong::class);
    }

    public function vatDung()
    {
        return $this->belongsTo(VatDung::class, 'vat_dung_id');
    }

    public function incidents()
    {
        return $this->hasMany(VatDungIncident::class, 'phong_vat_dung_instance_id');
    }
}
