<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefToneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tones = [
            [
                'nama' => 'Positif',
                'kode' => '1', // Menggunakan angka sebagai kode
                'deskripsi' => 'Tone positif',
                'warna' => '#28a745', // Warna hijau
                'urutan' => 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'nama' => 'Negatif',
                'kode' => '2', // Menggunakan angka sebagai kode
                'deskripsi' => 'Tone negatif',
                'warna' => '#dc3545', // Warna merah
                'urutan' => 2,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        // Insert data
        DB::table('ref_tone')->insert($tones);
    }
}