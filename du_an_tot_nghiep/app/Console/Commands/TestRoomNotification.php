<?php

namespace App\Console\Commands;

use App\Models\Phong;
use App\Models\User;
use App\Models\LoaiPhong;
use App\Models\Tang;
use App\Events\RoomCreated;
use App\Events\RoomUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestRoomNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test-room {action=create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test room notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info("Testing room notification system: {$action}");
        
        // Get admin user for testing
        $admin = User::where('vai_tro', 'admin')->first();
        if (!$admin) {
            $this->error('No admin user found');
            return 1;
        }
        
        // Set authenticated user
        Auth::login($admin);
        
        if ($action === 'create') {
            $this->testRoomCreated();
        } elseif ($action === 'update') {
            $this->testRoomUpdated();
        } else {
            $this->error('Invalid action. Use: create or update');
            return 1;
        }
        
        $this->info('Room notification test completed!');
        return 0;
    }
    
    private function testRoomCreated()
    {
        $this->info('Testing room created notification...');
        
        // Get required data
        $loaiPhong = LoaiPhong::first();
        $tang = Tang::first();
        
        if (!$loaiPhong || !$tang) {
            $this->error('Missing required data (LoaiPhong or Tang)');
            return;
        }
        
        // Create test room
        $phong = Phong::create([
            'ma_phong' => 'TEST_' . time(),
            'name' => 'Phòng Test Thông Báo',
            'loai_phong_id' => $loaiPhong->id,
            'tang_id' => $tang->id,
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 500000,
            'trang_thai' => 'trong'
        ]);
        
        $this->info("Created test room: {$phong->ten_phong} (ID: {$phong->id})");
        
        // Dispatch event
        event(new RoomCreated($phong, Auth::user()));
        
        $this->info('Room created event dispatched');
        
        // Clean up
        $phong->delete();
        $this->info('Test room cleaned up');
    }
    
    private function testRoomUpdated()
    {
        $this->info('Testing room updated notification...');
        
        // Get required data
        $loaiPhong = LoaiPhong::first();
        $tang = Tang::first();
        
        if (!$loaiPhong || !$tang) {
            $this->error('Missing required data (LoaiPhong or Tang)');
            return;
        }
        
        // Create test room
        $phong = Phong::create([
            'ma_phong' => 'TEST_UPDATE_' . time(),
            'name' => 'Phòng Test Cập Nhật',
            'loai_phong_id' => $loaiPhong->id,
            'tang_id' => $tang->id,
            'suc_chua' => 2,
            'so_giuong' => 1,
            'gia_mac_dinh' => 500000,
            'trang_thai' => 'trong'
        ]);
        
        $this->info("Created test room: {$phong->ten_phong} (ID: {$phong->id})");
        
        // Update room
        $changes = [
            'gia_mac_dinh' => [
                'from' => 500000,
                'to' => 600000
            ],
            'suc_chua' => [
                'from' => 2,
                'to' => 4
            ]
        ];
        
        $phong->update([
            'gia_mac_dinh' => 600000,
            'suc_chua' => 4
        ]);
        
        // Dispatch event
        event(new RoomUpdated($phong, Auth::user(), $changes));
        
        $this->info('Room updated event dispatched');
        
        // Clean up
        $phong->delete();
        $this->info('Test room cleaned up');
    }
}
