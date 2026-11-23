<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class DefaultPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'code' => 'OB',
                'name' => 'Pramubhakti (OB)',
                'default_start_time' => '06:30:00',
                'default_end_time' => '18:00:00',
                'is_overnight' => false,
                'tl_group' => 'OB',
            ],
            [
                'code' => 'DRIVER',
                'name' => 'Pengemudi',
                'default_start_time' => '07:00:00',
                'default_end_time' => '18:00:00',
                'is_overnight' => false,
                'tl_group' => 'STANDARD',
            ],
            [
                'code' => 'RECEPTIONIST',
                'name' => 'Receptionist',
                'default_start_time' => '07:00:00',
                'default_end_time' => '18:00:00',
                'is_overnight' => false,
                'tl_group' => 'STANDARD',
            ],
            [
                'code' => 'TECHNICIAN',
                'name' => 'Teknisi Komputer, Jaringan, Listrik',
                'default_start_time' => '07:00:00',
                'default_end_time' => '18:00:00',
                'is_overnight' => false,
                'tl_group' => 'STANDARD',
            ],
            [
                'code' => 'SECURITY_DAY',
                'name' => 'Satpam (Shift Pagi)',
                'default_start_time' => '07:00:00',
                'default_end_time' => '19:00:00',
                'is_overnight' => false,
                'tl_group' => 'SECURITY_DAY',
            ],
            [
                'code' => 'SECURITY_NIGHT',
                'name' => 'Satpam (Shift Malam)',
                'default_start_time' => '19:00:00',
                'default_end_time' => '07:00:00',
                'is_overnight' => true,
                'tl_group' => 'SECURITY_NIGHT',
            ],
        ];

        foreach ($positions as $pos) {
            Position::updateOrCreate(
                ['code' => $pos['code']],
                $pos
            );
        }
    }
}
