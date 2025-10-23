<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CleanupExpiredHolds extends Command
{
    protected $signature = 'cleanup:expired-holds {--auto-cancel : If set, automatically mark expired bookings as da_huy when hold expires}';
    protected $description = 'Process giu_phong holds: finalize holds whose dat_phong is confirmed, and clean expired holds';

    protected $defaultAutoCancel = true;

    public function handle()
    {
        $this->info('CleanupExpiredHolds run: ' . now()->toDateTimeString());
        $autoCancel = $this->option('auto-cancel') ? true : $this->defaultAutoCancel;
        $now = Carbon::now();

        if (!Schema::hasTable('giu_phong')) {
            $this->info('giu_phong table not present, nothing to do.');
            return 0;
        }

        $holds = DB::table('giu_phong')->where('released', false)->get();

        if ($holds->isEmpty()) {
            $this->info('No active holds found.');
            return 0;
        }

        foreach ($holds as $hold) {
            try {
                DB::beginTransaction();

                $this->info("Processing giu_phong id={$hold->id}, dat_phong_id={$hold->dat_phong_id}");
                $datPhong = null;
                if ($hold->dat_phong_id) {
                    $datPhong = DB::table('dat_phong')->where('id', $hold->dat_phong_id)->first();
                }

                if (!$datPhong) {
                    DB::table('giu_phong')->where('id', $hold->id)->delete();
                    Log::info('CleanupExpiredHolds: deleted hold because dat_phong missing', ['giu_phong_id' => $hold->id, 'dat_phong_id' => $hold->dat_phong_id]);
                    DB::commit();
                    continue;
                }

                if (isset($datPhong->trang_thai) && $datPhong->trang_thai === 'da_xac_nhan') {
                    $this->info("dat_phong id={$datPhong->id} is da_xac_nhan — finalizing items/addons and deleting hold.");

                    $meta = $this->parseMetaColumn($hold);
                    $roomsCount = (int) ($meta['rooms_count'] ?? ($hold->so_luong ?? 1));
                    $nights = (int) ($meta['nights'] ?? 1);
                    $gia_tren_dem = isset($meta['final_per_night']) ? (float)$meta['final_per_night'] : null;
                    $tong_item = isset($meta['snapshot_total']) ? (float)$meta['snapshot_total'] : null;
                    $spec_signature_hash = $hold->spec_signature_hash ?? ($meta['spec_signature_hash'] ?? null);

                    if (Schema::hasTable('dat_phong_item')) {
                        // CASE 1: this hold row has an explicit phong_id -> create dat_phong_item for that specific room if not exists
                        if (isset($hold->phong_id) && $hold->phong_id) {
                            $exists = DB::table('dat_phong_item')
                                ->where('dat_phong_id', $datPhong->id)
                                ->where('phong_id', $hold->phong_id)
                                ->exists();

                            if (!$exists) {
                                DB::table('dat_phong_item')->insert([
                                    'dat_phong_id' => $datPhong->id,
                                    'phong_id' => $hold->phong_id,
                                    'loai_phong_id' => $hold->loai_phong_id ?? null,
                                    'spec_signature_hash' => $spec_signature_hash,
                                    'so_luong' => 1,
                                    'gia_tren_dem' => $gia_tren_dem ?? 0,
                                    'so_dem' => $nights,
                                    'tong_item' => ($gia_tren_dem !== null ? ($gia_tren_dem * $nights) : ($tong_item ?? 0)),
                                    'taxes_amount' => 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                Log::info('CleanupExpiredHolds: created dat_phong_item for specific phong_id', ['dat_phong_id' => $datPhong->id, 'phong_id' => $hold->phong_id]);
                            } else {
                                Log::info('CleanupExpiredHolds: dat_phong_item for this phong_id already exists, skipping', ['dat_phong_id' => $datPhong->id, 'phong_id' => $hold->phong_id]);
                            }
                        }
                        // CASE 2: meta contains selected_phong_ids => iterate and insert any missing ones
                        else {
                            $selectedIds = $meta['selected_phong_ids'] ?? null;
                            if (is_array($selectedIds) && count($selectedIds) > 0) {
                                foreach ($selectedIds as $phongId) {
                                    $phongId = (int)$phongId;
                                    if ($phongId <= 0) continue;
                                    $exists = DB::table('dat_phong_item')
                                        ->where('dat_phong_id', $datPhong->id)
                                        ->where('phong_id', $phongId)
                                        ->exists();
                                    if (!$exists) {
                                        DB::table('dat_phong_item')->insert([
                                            'dat_phong_id' => $datPhong->id,
                                            'phong_id' => $phongId,
                                            'loai_phong_id' => $hold->loai_phong_id ?? null,
                                            'spec_signature_hash' => $spec_signature_hash,
                                            'so_luong' => 1,
                                            'gia_tren_dem' => $gia_tren_dem ?? 0,
                                            'so_dem' => $nights,
                                            'tong_item' => ($gia_tren_dem !== null ? ($gia_tren_dem * $nights) : ($tong_item ?? 0)),
                                            'taxes_amount' => 0,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        Log::info('CleanupExpiredHolds: created dat_phong_item for selected_phong_id', ['dat_phong_id' => $datPhong->id, 'phong_id' => $phongId]);
                                    } else {
                                        Log::info('CleanupExpiredHolds: dat_phong_item for selected_phong_id already exists, skipping', ['dat_phong_id' => $datPhong->id, 'phong_id' => $phongId]);
                                    }
                                }
                            }
                            // CASE 3: aggregate hold (no specific ids) -> insert remaining count if any
                            else {
                                if (Schema::hasColumn('dat_phong_item', 'so_luong')) {
                                    $existingCount = (int) DB::table('dat_phong_item')
                                        ->where('dat_phong_id', $datPhong->id)
                                        ->sum('so_luong');
                                } else {
                                    $existingCount = (int) DB::table('dat_phong_item')
                                        ->where('dat_phong_id', $datPhong->id)
                                        ->count();
                                }

                                $remaining = max(0, $roomsCount - $existingCount);
                                if ($remaining > 0) {
                                    DB::table('dat_phong_item')->insert([
                                        'dat_phong_id' => $datPhong->id,
                                        'loai_phong_id' => $hold->loai_phong_id ?? null,
                                        'spec_signature_hash' => $spec_signature_hash,
                                        'so_luong' => $remaining,
                                        'gia_tren_dem' => $gia_tren_dem ?? 0,
                                        'so_dem' => $nights,
                                        'tong_item' => $tong_item ?? (($gia_tren_dem ?? 0) * $remaining * $nights),
                                        'taxes_amount' => 0,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    Log::info('CleanupExpiredHolds: created aggregate dat_phong_item for dat_phong (remaining)', ['dat_phong_id' => $datPhong->id, 'so_luong' => $remaining]);
                                } else {
                                    Log::info('CleanupExpiredHolds: no remaining rooms to create in dat_phong_item for this dat_phong', ['dat_phong_id' => $datPhong->id, 'existingCount' => $existingCount, 'roomsCount' => $roomsCount]);
                                }
                            }
                        }
                    } else {
                        Log::info('CleanupExpiredHolds: dat_phong_item table missing, skipping items creation', ['dat_phong_id' => $datPhong->id]);
                    }

                    // only create if not exists for dat_phong
                    $addonsExist = Schema::hasTable('dat_phong_addon') ? DB::table('dat_phong_addon')->where('dat_phong_id', $datPhong->id)->exists() : false;
                    if (Schema::hasTable('dat_phong_addon') && !$addonsExist) {
                        $addonsList = $meta['addons'] ?? [];
                        $roomsCountForAddon = (int) ($meta['rooms_count'] ?? ($hold->so_luong ?? 1));
                        foreach ($addonsList as $a) {
                            $pricePerRoom = isset($a['gia']) ? (float)$a['gia'] : (float)($a['price'] ?? 0);
                            $insertAddon = [
                                'dat_phong_id' => $datPhong->id,
                                'name' => $a['ten'] ?? $a['name'] ?? 'Addon',
                                'price' => $pricePerRoom * $roomsCountForAddon,
                                'qty' => $roomsCountForAddon,
                                'total_price' => $pricePerRoom * $roomsCountForAddon,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            if (Schema::hasColumn('dat_phong_addon', 'phong_id') && isset($hold->phong_id) && $hold->phong_id) {
                                $insertAddon['phong_id'] = $hold->phong_id;
                            }
                            DB::table('dat_phong_addon')->insert($insertAddon);
                        }
                        Log::info('CleanupExpiredHolds: created dat_phong_addon(s) for dat_phong', ['dat_phong_id' => $datPhong->id]);
                    } else {
                        Log::info('CleanupExpiredHolds: dat_phong_addon exists or table missing, skipping', ['dat_phong_id' => $datPhong->id, 'addonsExist' => $addonsExist]);
                    }

                    // remove the processed hold row (we processed this hold's contribution)
                    DB::table('giu_phong')->where('id', $hold->id)->delete();
                    Log::info('CleanupExpiredHolds: removed giu_phong after finalizing dat_phong', ['giu_phong_id' => $hold->id, 'dat_phong_id' => $datPhong->id]);

                    DB::commit();
                    continue;
                }

                // release expired holds if still 'dang_cho'
                if (isset($hold->het_han_luc) && Carbon::parse($hold->het_han_luc)->lt($now) && isset($datPhong->trang_thai) && $datPhong->trang_thai === 'dang_cho') {
                    DB::table('giu_phong')->where('id', $hold->id)->delete();
                    Log::info('CleanupExpiredHolds: expired hold removed (dat_phong still dang_cho)', ['giu_phong_id' => $hold->id, 'dat_phong_id' => $datPhong->id]);

                    if ($autoCancel) {
                        DB::table('dat_phong')->where('id', $datPhong->id)->update(['trang_thai' => 'da_huy', 'updated_at' => now()]);
                        Log::info('CleanupExpiredHolds: dat_phong auto-cancelled (da_huy)', ['dat_phong_id' => $datPhong->id]);
                    }

                    DB::commit();
                    continue;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('CleanupExpiredHolds: exception processing hold', ['giu_phong_id' => $hold->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        $this->info('CleanupExpiredHolds: finished run at ' . now()->toDateTimeString());
        return 0;
    }

    protected function parseMetaColumn($hold)
    {
        $meta = [];
        if (isset($hold->meta) && $hold->meta) {
            if (is_string($hold->meta)) {
                $decoded = json_decode($hold->meta, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $meta = $decoded;
                }
            } elseif (is_array($hold->meta)) {
                $meta = $hold->meta;
            }
        }
        return $meta;
    }
}
