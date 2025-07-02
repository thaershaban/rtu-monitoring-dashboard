<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RtuArchive; // استيراد الموديل

class RtuArchiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
{
    $this->call(RtuArchiveSeeder::class);

        // حذف البيانات القديمة (للتجربة)
        DB::table('rtu_archive')->truncate();

        // إدخال بيانات تجريبية
        RtuArchive::create([
            'address' => '192.168.1.10',
            'total_value' => 10.5,
            'status_code' => 1, // Normal
        ]);

        RtuArchive::create([
            'address' => '192.168.1.11',
            'total_value' => 5.2,
            'status_code' => 2, // Failed
        ]);

        RtuArchive::create([
            'address' => '192.168.1.12',
            'total_value' => 7.8,
            'status_code' => 3, // Margin
        ]);

        RtuArchive::create([
            'address' => '192.168.1.13',
            'total_value' => 1.0,
            'status_code' => 4, // Alarm
        ]);
    }
}