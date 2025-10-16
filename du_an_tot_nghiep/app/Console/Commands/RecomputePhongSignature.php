<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Phong;
use Illuminate\Support\Facades\Log;

class RecomputePhongSignature extends Command
{
    protected $signature = 'phong:recompute-signatures {--chunk=200}';
    protected $description = 'Recompute spec_signature_hash for existing phong rows';

    public function handle()
    {
        $chunk = (int) $this->option('chunk');

        $this->info("Recomputing spec_signature_hash for Phong in chunks of {$chunk}...");

        Phong::with(['loaiPhong.tienNghis', 'tienNghis', 'bedTypes'])
            ->chunk($chunk, function ($rows) {
                foreach ($rows as $r) {
                    try {
                        $r->spec_signature_hash = $r->specSignatureHash();
                        $r->saveQuietly();
                    } catch (\Throwable $e) {
                        Log::error("Failed recompute signature for Phong id={$r->id}: " . $e->getMessage());
                        $this->warn("Failed id={$r->id}: " . $e->getMessage());
                    }
                }
            });

        $this->info("Done.");
        return 0;
    }
}
