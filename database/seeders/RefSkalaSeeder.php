<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefSkalaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $skalas = [
            [
                'nama' => 'Rendah',
                'kode' => '1', // Menggunakan angka sebagai kode
                'deskripsi' => 'Dampak skala rendah',
                'urutan' => 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nama' => 'Sedang',
                'kode' => '2', // Menggunakan angka sebagai kode
                'deskripsi' => 'Dampak skala sedang',
                'urutan' => 2,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nama' => 'Tinggi',
                'kode' => '3', // Menggunakan angka sebagai kode
                'deskripsi' => 'Dampak skala tinggi',
                'urutan' => 3,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        // Insert data
        DB::table('ref_skala')->insert($skalas);
    }
}