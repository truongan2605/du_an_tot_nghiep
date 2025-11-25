<?php

namespace App\Models;
use App\Traits\Auditable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DatPhong extends Model
{
    use HasFactory;
     use Auditable;
    protected $table = 'dat_phong';

    protected $fillable = [
        'ma_tham_chieu',
        'nguoi_dung_id',
        'trang_thai',
        'ngay_nhan_phong',
        'checked_in_at',
        'checked_in_by',
        'ngay_tra_phong',
        'so_khach',
        'tong_tien',
        'don_vi_tien',
        'can_thanh_toan',
        'created_by',
        'phuong_thuc',
        'ma_voucher',
        'voucher_code',
        'discount_amount',
        'snapshot_total',
        'snapshot_meta',
        'source',
        'ghi_chu',
        'can_xac_nhan',
        'contact_name',
        'contact_address',
        'contact_phone',
        'deposit_amount',
        'refund_amount',
        'refund_percentage',
        'cancelled_at',
        'cancellation_reason',
    ];


    protected $casts = [
        'ngay_nhan_phong' => 'date',
        'checked_in_at' => 'datetime',
        'checkout_at'     => 'datetime',
        'ngay_tra_phong' => 'date',
        'so_khach' => 'integer',
        'tong_tien' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'snapshot_total' => 'decimal:2',
        'snapshot_meta' => 'array',
        'can_thanh_toan' => 'boolean',
        'can_xac_nhan' => 'boolean',
        'checked_in_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nguoi_dung_id');
    }
    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'nguoi_dung_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'created_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'checked_in_by');
    }

    public function datPhongItems()
    {
        return $this->hasMany(DatPhongItem::class, 'dat_phong_id');
    }

    public function refundRequests()
    {
        return $this->hasMany(\App\Models\RefundRequest::class, 'dat_phong_id');
    }

    public function datPhongAddons(): HasMany
    {
        return $this->hasMany(DatPhongAddon::class, 'dat_phong_id');
    }

    public function giaoDichs(): HasMany
    {
        return $this->hasMany(GiaoDich::class, 'dat_phong_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGia::class, 'dat_phong_id');
    }

    public function consumptions()
    {
        return $this->hasMany(\App\Models\PhongVatDungConsumption::class, 'dat_phong_id');
    }

    public function vatDungIncidents()
    {
        return $this->hasMany(\App\Models\VatDungIncident::class, 'dat_phong_id');
    }

    public function computeVatDungTotal(): float
    {
        // consumptions
        $consumptionSum = $this->consumptions()->with('vatDung')->get()->sum(function ($c) {
            $unit = $c->unit_price !== null ? (float)$c->unit_price : (float) ($c->vatDung->gia ?? 0);
            return $unit * (int)$c->quantity;
        });

        // incidents
        $incidentSum = $this->vatDungIncidents()->sum('fee');

        return (float) ($consumptionSum + $incidentSum);
    }


    public function computeVatDungConsumablesBreakdown(): array
    {
        // nếu chưa check-in thì trả về rỗng
        if (! $this->checked_in_at) {
            return ['items' => [], 'total' => 0.0];
        }

        $consumptions = $this->consumptions()
            ->whereNull('billed_at')
            ->whereNotNull('consumed_at')
            ->where('consumed_at', '>=', $this->checked_in_at)
            ->with('vatDung')
            ->get()
            ->map(function ($c) {
                $unit = $c->unit_price !== null ? (float)$c->unit_price : (float) ($c->vatDung->gia ?? 0);
                $qty = (int) $c->quantity;
                return [
                    'id' => $c->id,
                    'vat_dung_id' => $c->vat_dung_id,
                    'name' => $c->vatDung->ten ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'subtotal' => round($unit * $qty, 2),
                    'note' => $c->note ?? null,
                ];
            })->toArray();

        $total = array_sum(array_column($consumptions, 'subtotal'));

        return [
            'items' => $consumptions,
            'total' => round($total, 2),
        ];
    }

    public function pendingDurableIncidents(): array
    {
        if (! $this->checked_in_at) {
            return ['items' => [], 'total' => 0.0];
        }

        $incidents = $this->vatDungIncidents()
            ->whereNull('billed_at')
            ->where('created_at', '>=', $this->checked_in_at)
            ->with('vatDung', 'instance')
            ->get()
            ->map(function ($inc) {
                return [
                    'id' => $inc->id,
                    'vat_dung_id' => $inc->vat_dung_id,
                    'name' => $inc->vatDung?->ten ?? ('Sự cố #' . $inc->id),
                    'description' => $inc->description,
                    'fee' => (float) ($inc->fee ?? 0),
                ];
            })->toArray();

        $total = array_sum(array_column($incidents, 'fee'));

        return [
            'items' => $incidents,
            'total' => round($total, 2),
        ];
    }



    public function createHoaDonForVatDungAtCheckout($operator = null)
    {
        $cons = $this->computeVatDungConsumablesBreakdown();
        $inc = $this->pendingDurableIncidents();

        $total = $cons['total'] + $inc['total'];
        if ($total <= 0) {
            return null;
        }

        return DB::transaction(function () use ($cons, $inc, $operator, $total) {
            $hoaDon = \App\Models\HoaDon::create([
                'dat_phong_id' => $this->id,
                'so_hoa_don' => 'HD' . time() . rand(100, 999),
                'tong_thuc_thu' => $total,
                'don_vi' => $this->don_vi_tien ?? 'VND',
                'trang_thai' => 'da_xuat',
            ]);

            foreach ($cons['items'] as $it) {
                \App\Models\HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'consumption',
                    'ref_id' => $it['id'],
                    'vat_dung_id' => $it['vat_dung_id'],
                    'name' => $it['name'],
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'],
                    'amount' => $it['subtotal'],
                ]);
                \App\Models\PhongVatDungConsumption::where('id', $it['id'])->update(['billed_at' => now()]);
            }

            // incidents -> hoa_don_items
            foreach ($inc['items'] as $it) {
                \App\Models\HoaDonItem::create([
                    'hoa_don_id' => $hoaDon->id,
                    'type' => 'incident',
                    'ref_id' => $it['id'],
                    'vat_dung_id' => $it['vat_dung_id'],
                    'name' => $it['name'],
                    'quantity' => 1,
                    'unit_price' => $it['fee'],
                    'amount' => $it['fee'],
                    'note' => $it['description'] ?? null
                ]);
                \App\Models\VatDungIncident::where('id', $it['id'])->update(['billed_at' => now()]);
            }

            return $hoaDon;
        });
    }


    public function giuPhongs(): HasMany
    {
        return $this->hasMany(GiuPhong::class, 'dat_phong_id');
    }

    public function hoaDons(): HasMany
    {
        return $this->hasMany(HoaDon::class, 'dat_phong_id');
    }

    public function voucherUsages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class, 'dat_phong_id');
    }

    public function phongDaDats()
    {
        return $this->hasManyThrough(
            PhongDaDat::class,
            DatPhongItem::class,
            'dat_phong_id',
            'dat_phong_item_id',
            'id',
            'id'
        );
    }

    public const ALLOWED_FOR_CONSUMPTION = ['dang_cho_xac_nhan', 'da_xac_nhan', 'dang_su_dung'];

    public function canSetupConsumables(): bool
    {
        return in_array($this->trang_thai, self::ALLOWED_FOR_CONSUMPTION);
    }


    // Scopes
    public function scopeDangCho($query)
    {
        return $query->where('trang_thai', 'dang_cho');
    }

    public function scopeDaXacNhan($query)
    {
        return $query->where('trang_thai', 'da_xac_nhan');
    }

    public function scopeDaGanPhong($query)
    {
        return $query->where('trang_thai', 'da_gan_phong');
    }

    public function scopeDaNhanPhong($query)
    {
        return $query->where('trang_thai', 'da_nhan_phong');
    }

    public function scopeHoanThanh($query)
    {
        return $query->where('trang_thai', 'hoan_thanh');
    }

    public function scopeDaHuy($query)
    {
        return $query->where('trang_thai', 'da_huy');
    }
}
